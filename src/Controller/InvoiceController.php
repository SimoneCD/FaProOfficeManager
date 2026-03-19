<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\Offer;
use App\Entity\Order;
use App\Form\InvoiceIndividualType;
use App\Form\InvoiceType;
use App\Repository\InvoiceRepository;
use App\Service\PHPMailerService;
use App\Service\PriceService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['de' => '/rechnungen', 'en' => '/billings'])]
#[IsGranted('ROLE_EMPLOYEE_SERVICE')]
class InvoiceController extends BaseController
{
    use TargetPathTrait;

    private readonly Pdf $knp;

    protected PriceService $priceService;

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, PriceService $priceService, TranslatorInterface $translator, string $subdomain)
    {
        $this->priceService = $priceService;
        $this->knp = new Pdf($_ENV['WKHTMLTOPDF_PATH']);

        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    #[Route(path: '/bsh', name: 'invoice_bsh_index', methods: ['GET', 'POST'])]
    public function indexBsh(InvoiceRepository $invoiceRepository): Response
    {
        $customer = $this->em->getRepository(Customer::class)->find('277');

        return $this->render('app/invoice/bsh.html.twig', [
            'invoices' => $invoiceRepository->findBy([
                'customer' => $customer,
            ]),
        ]);
    }

    #[Route(path: ['de' => '/{id}/neu', 'en' => '/{id}/new'], name: 'invoice_user_new', methods: ['GET', 'POST'])]
    public function individualNew(Request $request, Customer $customer): Response
    {
        $date = new \DateTime();
        $invoice = new Invoice();
        $invoice->setDate($date);
        $invoice->setUser($this->getUser());
        $invoice->setCustomer($customer);
        $invoice->setType('individual');
        $invoice->setPos0Date($date->format('d.m.Y'));
        $invoice->setPos0Text('');
        $invoice->setPos0Price(0);
        $invoice->setBauvorhaben($customer->getAddress().', '.$customer->getZip());
        $number = 1;
        /* @var Invoice $invoice */
        foreach ($customer->getInvoices() as $invoiceX) {
            if ('individual' === $invoiceX->getType()) {
                ++$number;
            }
        }
        $invoice->setNumber('2'.$customer->getId().'.'.$number);
        if (empty($invoice->getContext())) {
            $invoice->setContext([
                'mailText' => '',
                'text' => '',
                'solar' => 19,
            ]);
        }

        $invoice->setText(sprintf('Rechnungsnummer: %s.', $invoice->getNumber()));

        $form = $this->createForm(InvoiceIndividualType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $post = $request->request->all('invoice_individual');
            $emailText = $request->request->get('emailtext');
            if (isset($post['context'])) {
                $data->addContext($post['context']);
                if (isset($post['context']['mailText'])) {
                    $emailText = $post['context']['mailText'];
                }
            }
            $this->em->persist($data);
            $this->em->flush();
            $this->em->refresh($invoice);
            $pdf = $this->getIndividualInvoicePdf($invoice);
            if ('1' === $request->request->get('send')) {
                $invoice->setSendDate(new \DateTime());
                $this->addFlash('success', $this->translator->trans('f.success.invoiceSend', ['%invoiceNumber%' => $invoice->getNumber()]));
                $path = $this->getParameter('kernel.project_dir').'/pdf_AggSF-2/invoices/'.($this->subdomain ?? 'app').'/Invoice_'.$invoice->getNumber().'_'.date('d-m-Y_H').'.pdf';
                @file_put_contents($path, $pdf);
                $this->mailerService->setUserToLog($this->getUser());
                $this->mailerService->sendIndividualInvoice($invoice, $path, $emailText);
                $this->em->persist($invoice);
                $this->em->flush();
            } else {
                $this->addFlash('success', $this->translator->trans('f.success.invoiceSave', ['%invoiceNumber%' => $invoice->getNumber()]));
            }

            return $this->redirectToRoute('invoice_user_edit', ['id' => $invoice->getId()]);
        }

        return $this->render('app/invoice/invoice.html.twig', [
            'customer' => $customer,
            'type' => 'individual',
            'invoice' => $invoice,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: ['de' => '/{id}/abschlagsrechnung', 'en' => '/{id}/part-invoice'], name: 'invoice_part_index', methods: ['GET', 'POST'])]
    #[Route(path: ['de' => '/{order}/teil-rechnung', 'en' => '/{order}/part-plus-invoice'], name: 'invoice_part_plus_index', methods: ['GET', 'POST'])]
    #[Route(path: ['de' => '/{order}/teil-rechnung/{invoice}', 'en' => '/{order}/part-plus-invoice/{invoice}'], name: 'invoice_part_plus_id', methods: ['GET', 'POST'])]
    public function partPreview(Request $request, PriceService $priceService, Order $order, ?Invoice $invoice = null): Response
    {
        $routeName = $request->get('_route');
        $date = new \DateTime();
        $offer = $order->getOffer();
        $partInvoice = $this->em->getRepository(Invoice::class)->findPartInvoice($order);
        $partPlusInvoices = $this->em->getRepository(Invoice::class)->findBy([
            'invoiceOrder' => $order,
            'type' => 'part-plus',
        ]);
        $this->updateInvoiceContext($request, $invoice);
        /* Zusatzangebot editieren */
        if (count($partPlusInvoices) && 'invoice_part_plus_id' == $routeName) {
        } elseif (count($partPlusInvoices) && empty($partPlusInvoices[0]->getSendDate()) && 'invoice_part_plus_index' == $routeName) {
            $this->addFlash('error', $this->translator->trans('ip.error.exist', ['%offerNumber%' => $partPlusInvoices[0]->getNumber()]));

            return $this->redirect($this->getTargetPath($request->getSession(), 'main'));
        } elseif ($partInvoice instanceof Invoice && 'invoice_part_plus_index' == $routeName) {
            $invoice = new Invoice();
            $this->setPartPrice($invoice, $partInvoice, $partPlusInvoices);
            $text = $this->translator->trans('ip.t.a', ['%%offerNumber%' => $offer->getNumber()]);
            $invoice->setText($text);
            $invoice->setInvoiceOrder($order);
            $invoice->setNumber($offer->getNumber().'.'.count($partPlusInvoices) + 2);
            $invoice->setType('part-plus');

            $this->setInvoicePosData($invoice, count($partPlusInvoices) + 1);
        // erste Abschlagsrechnung erstellen
        } else {
            $invoice = $order->getPartInvoice() ?? new Invoice();
            if (empty($invoice->getId())) {
                $invoice->setNumber($offer->getNumber().'.1');
                $invoice->setInvoiceOrder($order);
                $invoice->setType('part');
                $this->setInvoicePosData($invoice, 0);
            }
        }
        $customer = $order->getOffer()->getCustomer();
        if (null === $invoice->getId()) {
            if (isset($offer->getOption()->getContext()['header'])) {
                $invoice->setLadestation($offer->getOption()->getContext()['header']['text']);
            }
            if (empty($invoice->getPos0Date())) {
                $invoice->setPos0Date($date->format('d.m.Y'));
            }
            $invoice->setUser($this->getUser());
            $invoice->setCustomer($customer);
            $invoice->setDate($date);
            $invoice->setInvoiceOrder($order);
            $invoice->setLv('');
        }

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        $c = $invoice->getContext();
        if (empty($c['invoice']) && !$form->isSubmitted()) {
            $this->setKeyValue($invoice, $offer);
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $invoice->setInvoiceOrder($order);
            $invoiceContext = $invoice->getContext();
            $post = $request->request->all();
            $this->addInvoiceContext($post['invoice_option']['context'], $invoiceContext);
            $invoice->setContext($invoiceContext);
            if (empty($invoiceContext) || empty($invoiceContext['invoice']['text'])) {
                $this->addFlash('danger', $this->translator->trans('i.error.data'));

                return $this->redirect($request->getUri());
            }
            if (empty($invoiceContext) && empty($invoiceContext['partMailText'])) {
                $this->addFlash('danger', $this->translator->trans('i.error.partDataMail'));

                return $this->redirect($request->getUri());
            }
            foreach ($invoiceContext['invoice']['text'] as $k => $v) {
                if ((empty(trim($v)) && !empty(trim($invoiceContext['invoice']['name'][$k]))) || 'dd.mm.yyyy' === $v || strstr($v, '##')) {
                    if ('part' === $invoice->getType()) {
                        $invoice->setNumber($offer->getNumber().'.1');
                    }
                    $this->addFlash('danger', $this->translator->trans('i.error.partDataMail'));

                    return $this->redirect($request->getUri());
                }
            }
            $offer = $order->getOffer();
            $this->em->persist($invoice);
            $this->em->flush();
            $this->em->refresh($invoice);

            if ('1' === $request->request->get('send')) {
                $html = $this->renderView('app/invoice/pdf/invoice.html.twig', [
                    'offer' => $order->getOffer(),
                    'invoice' => $invoice,
                    'order' => $order,
                    'text' => $invoice->getText(),
                    'type' => $invoice->getType(),
                ]);
                $pdf = $this->knp->getOutputFromHtml($html, [
                    'disable-smart-shrinking' => true,
                    // 'orientation'=>'Landscape',
                    'default-header' => false,
                    'margin-top' => '0',
                    'margin-left' => '0',
                    'margin-bottom' => '0',
                    'margin-right' => '0',
                ]
                );
                if ('bestaetigt' === $offer->getStatus()) {
                    $order->setStatus('rechnungPartSend');
                    $offer->setStatus('rechnungPartSend');
                }
                $this->em->persist($order);
                $this->em->persist($offer);
                $invoice->setSendDate($date);
                $path = $this->getParameter('kernel.project_dir').'/pdf_AggSF-2/invoices/'.($this->subdomain ?? 'app').'/Invoice_'.$offer->getNumber().'.1_'.date('d-m-Y').'.pdf';
                @file_put_contents($path, $pdf);
                $this->mailerService->setUserToLog($this->getUser());
                if ($this->mailerService->sendInvoice($invoice, $path, $invoice->getType(), $this->translator)) {
                    $invoice->setSendDate($date);
                    $this->em->persist($invoice);
                    $this->em->flush();

                    $this->log(
                        'send',
                        'Rechnung '.$invoice->getNumber().' an ('.$invoice->getCustomer()->getFullName().') gesendet.',
                        $invoice->getNumber().' -> ('.$priceService->calculateNettoPrice($invoice->getInvoiceOrder()->getOffer()).') '.$invoice->getInvoiceOrder()->getOffer()->getCustomer()->getEmail(),
                        $invoice->getCustomer(),
                        $invoice->getInvoiceOrder()->getOffer(),
                    );
                } else {
                    $this->addFlash('danger', 'Die Rechnung '.$invoice->getNumber().' konnte nicht gesendet');

                    return $this->redirectToRoute('invoice_part_plus_id', ['order' => $order, 'invoice' => $invoice]);
                }
            }
            $this->addFlash('success', 'Die Rechnung '.$invoice->getNumber().' wurde'.($request->request->get('send') ? ' gesendet' : ' gespeichert'));

            return $this->redirectToRoute('invoice_part_plus_id', ['order' => $order->getId(), 'invoice' => $invoice->getId()]);
        }

        return $this->render('app/invoice/invoice.html.twig', [
            'invoice' => $invoice,
            'partPlusInvoices' => $partPlusInvoices,
            'order' => $order,
            'form' => $form->createView(),
            'offer' => $order->getOffer(),
            'text' => $invoice->getText(),
            'type' => $invoice->getType(),
        ]);
    }

    #[Route(path: ['de' => '/{id}/schlussrechnung', 'en' => '/{id}/final-invoice'], name: 'invoice_rest_index', methods: ['GET', 'POST'])]
    public function restPreview(Request $request, Offer $offer): Response
    {
        $date = new \DateTime();
        $order = $offer->getOrder();
        $invoice = $order->getRestInvoice() ?? new Invoice();
        $customer = $order->getOffer()->getCustomer();
        $offer = $order->getOffer();
        $partInvoice = $this->em->getRepository(Invoice::class)->findPartInvoice($order);
        $partPlusInvoices = $this->em->getRepository(Invoice::class)->findBy([
            'invoiceOrder' => $order,
            'type' => 'part-plus',
        ]);
        $post = $request->request->all();
        $invoiceContext = $invoice->getContext();
        if (!empty($post['invoice_option'])) {
            $this->addInvoiceContext($post['invoice_option']['context'], $invoiceContext);
        }
        $invoice->setContext($invoiceContext);
        $payed = $this->setPartPrice($invoice, $partInvoice, $partPlusInvoices);
        if (null === $invoice->getId()) {
            $invoice->setNumber($offer->getNumber().'.'.(count($order->getInvoices()) + 1));
            $open = $this->priceService->calculateNettoPrice($offer) - $payed;
            $invoice->setUser($this->getUser());

            $invoice->setType('rest');
            $invoice->setCustomer($customer);
            $invoice->setDate($date);
            $invoice->setInvoiceOrder($order);

            $invoice->setPos0Text($this->translator->trans('ir.billingText', ['%offerNumber%' => $offer->getNumber()]));
            $invoice->setPos0Date($order->getCreatedAt()->format('d.m.Y'));
            $invoice->setPos0Price($open);
        }

        // todo | remove from entity | $this->setLv($invoice);
        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if (false === $form->isSubmitted()) {
            if (empty($invoice->getLadestation())) {
                $invoice->setLadestation(isset($order->getOffer()->getOption()->getContext()['header']) ? $order->getOffer()->getOption()->getContext()['header']['text'] : '');
            }
            $c = $invoice->getContext();
            if (empty($c['invoice'])) {
                $this->setKeyValue($invoice, $offer);
            }
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $invoiceContext = $invoice->getContext();
            if (empty($invoiceContext) && empty($invoiceContext['invoice']['text'])) {
                $this->addFlash('danger', $this->translator->trans('i.error.data'));

                return $this->redirect($request->getUri());
            }
            if (empty($invoiceContext) && empty($invoiceContext['finalMailText'])) {
                $this->addFlash('danger', $this->translator->trans('i.error.email'));

                return $this->redirect($request->getUri());
            }
            foreach ($invoiceContext['invoice']['text'] as $k => $v) {
                if (empty(trim($v)) || 'dd.mm.yyyy' === $v || strstr($v, '##')) {
                    $this->addFlash('danger', $this->translator->trans('i.error.data'));

                    return $this->redirect($request->getUri());
                }
            }
            $this->em->persist($invoice);
            $this->em->flush();
            $this->em->refresh($invoice);

            $order = $invoice->getInvoiceOrder();
            $offer = $order->getOffer();
            $pdf = $this->getInvoicePdf($order, $invoice);
            if ('1' === $request->request->get('send')) {
                $order->setStatus('rechnungEingangSend');
                $offer->setStatus('rechnungEingangSend');
                $invoice->setSendDate(new \DateTime());
                $this->em->persist($order);
                $this->em->persist($offer);
                $path = $this->getParameter('kernel.project_dir').'/pdf_AggSF-2/invoices/'.($this->subdomain ?? 'app').'/Invoice_'.$offer->getNumber().'.'.(count($offer->getOrder()->getInvoices()) + 1).'_'.date('d-m-Y').'.pdf';
                @file_put_contents($path, $pdf);
                $this->mailerService->setUserToLog($this->getUser());
                $this->mailerService->sendInvoice($invoice, $path, 'rest', $this->translator);
                $invoice->setSendDate(new \DateTime());
                $this->em->persist($invoice);
                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('f.success.invoiceSend', ['%invoiceNumber%' => $invoice->getNumber()]));
            } else {
                $this->addFlash('success', $this->translator->trans('f.success.invoiceSave', ['%invoiceNumber%' => $invoice->getNumber()]));
            }

            return $this->redirectToRoute('invoice_rest_index', ['id' => $offer->getId()]);
        }
        if ('done' === $offer->getStatus()) {
            $order->setStatus('rechnungEingangSend');
            $offer->setStatus('rechnungEingangSend');
        }

        return $this->render('app/invoice/invoice.html.twig', [
            'payed' => $payed,
            'partPlusInvoices' => [],
            'invoice' => $invoice,
            'order' => $offer->getOrder(),
            'form' => $form->createView(),
            'text' => $invoice->getText(),
            'offer' => $offer,
            'type' => 'rest',
        ]);
    }

    #[Route(path: ['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'invoice_user_edit', methods: ['GET', 'POST'])]
    public function individualEdit(Request $request, Invoice $invoice): Response
    {
        $date = new \DateTime();

        $form = $this->createForm(InvoiceIndividualType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $request->request->all('invoice_individual');
            /** @var Invoice $data */
            $data = $form->getData();
            if (isset($post['context'])) {
                $data->addContext($post['context']);
            }
            $this->em->persist($data);
            $this->em->flush();
            $this->em->refresh($invoice);
            if ('1' === $request->request->get('send')) {
                $invoice->setSendDate(new \DateTime());
                $pdf = $this->getIndividualInvoicePdf($invoice);
                $this->addFlash('success', $this->translator->trans('f.success.invoiceSend', ['%invoiceNumber%' => $invoice->getNumber()]));
                $path = 'pdf_AggSF-2/invoices/'.($this->subdomain ?? 'app').'/Invoice_'.$invoice->getNumber().'_'.$date->format('d-m-Y').'.pdf';
                @file_put_contents($path, $pdf);
                $this->mailerService->setUserToLog($this->getUser());
                $this->mailerService->sendIndividualInvoice($invoice, $path, $request->request->get('emailtext'));
                $this->em->persist($invoice);
                $this->em->flush();
            } else {
                $this->addFlash('success', $this->translator->trans('f.success.invoiceSave', ['%invoiceNumber%' => $invoice->getNumber()]));
            }

            return $this->redirect($request->getUri());
        }

        return $this->render('app/invoice/invoice.html.twig', [
            'invoice' => $invoice,
            'form' => $form->createView(),
            'text' => $invoice->getText(),
            'type' => $invoice->getType(),
        ]);
    }

    #[Route(path: ['de' => '/pdf/{order}/abschlags-rechnung.pdf', 'en' => '/pdf/{order}/part-invoice.pdf'], name: 'invoice_part_pdf', methods: ['GET'])]
    #[Route(path: ['de' => '/pdf/{order}/teil-rechnung/{invoice}.pdf', 'en' => '/pdf/{order}/part-plus-invoice/{invoice}.pdf'], name: 'invoice_part_plus_pdf_id', methods: ['GET'])]
    public function invoicePartPdf(Request $request, Order $order, ?Invoice $invoice = null): Response
    {
        $routeName = $request->get('_route');
        if ('invoice_part_pdf' == $routeName) {
            $invoice = $order->getPartInvoice();
        }
        if (!$invoice instanceof Invoice) {
            return $this->redirectToRoute('offer_category_index', ['id' => $order->getOffer()->getCategory()->getId()]);
        }

        return new Response(
            $this->getInvoicePdf($order, $invoice),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                // 'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    #[Route(path: ['de' => '/pdf/{order}/abschlags-rechnung.html', 'en' => '/pdf/{order}/part-invoice.html'], name: 'invoice_part_html', methods: ['GET'])]
    #[Route(path: ['de' => '/pdf/{order}/teil-rechnung/{invoice}.html', 'en' => '/pdf/{order}/part-plus-invoice/{invoice}.html'], name: 'invoice_part_plus_html_id', methods: ['GET'])]
    public function invoicePartHtml(Request $request, Order $order, ?Invoice $invoice = null): Response
    {
        $routeName = $request->get('_route');
        if ('invoice_part_html' == $routeName) {
            $invoice = $order->getPartInvoice();
        }
        if (!$invoice instanceof Invoice) {
            return $this->redirectToRoute('offer_category_index', ['id' => $order->getOffer()->getCategory()->getId()]);
        }

        return $this->render('app/invoice/pdf/invoice.html.twig', [
            'offer' => $order->getOffer(),
            'invoice' => $invoice,
            'order' => $order,
            'text' => $invoice->getText(),
            'type' => $invoice->getType(),
        ]);
    }

    #[Route(path: ['de' => '/pdf/{order}/schlussrechnung.pdf', 'en' => '/pdf/{order}/final-invoice.pdf'], name: 'invoice_rest_pdf', methods: ['GET', 'POST'])]
    public function invoiceRestPdf(Order $order): Response
    {
        $invoice = $order->getRestInvoice();
        if (!$invoice instanceof Invoice) {
            return $this->redirectToRoute('offer_category_index', ['id' => $order->getOffer()->getCategory()->getId()]);
        }

        if (empty($invoice->getPos0Date())) {
            $invoice->setPos0Date($order->getCreatedAt()->format('d.m.Y'));
        }

        return new Response(
            $this->getInvoicePdf($order, $invoice),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                // 'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    #[Route(path: 'individual/{id}/pdf', name: 'invoice_individual_pdf', methods: ['GET', 'POST'])]
    public function invoiceIndividualPdf(Invoice $invoice): Response
    {
        if (empty($invoice->getPos0Price())) {
            $this->addFlash('info', 'Positionspreis 1 darf nicht leer sein');

            return $this->redirectToRoute('invoice_user_edit', ['id' => $invoice->getId()]);
        }
        if (empty($invoice->getPos0Text())) {
            $this->addFlash('info', 'Erste Preis-Information darf nicht leer sein');

            return $this->redirectToRoute('invoice_user_edit', ['id' => $invoice->getId()]);
        }
        if (empty($invoice->getPos0Date())) {
            $invoice->setPos0Date('');
        }

        return new Response(
            $this->getIndividualInvoicePdf($invoice),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                // 'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    #[Route(path: 'individual/{id}/html', name: 'invoice_individual_html', methods: ['GET', 'POST'])]
    public function invoiceIndividualHtml(Invoice $invoice): Response
    {
        if (empty($invoice->getPos0Price())) {
            $this->addFlash('info', 'Positionspreis 1 darf nicht leer sein');

            return $this->redirectToRoute('invoice_user_edit', ['id' => $invoice->getId()]);
        }
        if (empty($invoice->getPos0Text())) {
            $this->addFlash('info', 'Erste Preis-Information darf nicht leer sein');

            return $this->redirectToRoute('invoice_user_edit', ['id' => $invoice->getId()]);
        }
        if (empty($invoice->getPos0Date())) {
            $invoice->setPos0Date('');
        }

        return $this->render('app/invoice/pdf_individual/invoice.html.twig', [
            'invoice' => $invoice,
            'text' => $invoice->getText(),
            'type' => $invoice->getType(),
        ]);
    }

    #[Route(path: ['de' => '/{id}/loeschen', 'en' => '/{id}/delete'], name: 'invoice_delete', methods: ['POST'])]
    public function delete(Invoice $invoice, PriceService $priceService): Response
    {
        $this->log(
            'delete',
            'Rechnung '.$invoice->getNumber().' von ('.$invoice->getCustomer()->getFullName().') gelöscht',
            $invoice->getNumber().' -> ('.$priceService->calculateNettoPrice($invoice->getInvoiceOrder()->getOffer()).') '.$invoice->getInvoiceOrder()->getOffer()->getCustomer()->getEmail(),
            $invoice->getCustomer(),
            $invoice->getInvoiceOrder()->getOffer(),
        );

        $invoice->setInvoiceOrder(null);
        $invoice->setUser(null);
        $invoice->setCustomer(null);

        $this->em->remove($invoice);
        $this->em->flush();

        return $this->json(true);
    }

    /**
     * Only for Jeremy.
     */
    private function setKeyValue(Invoice $invoice, Offer $offer)
    {
        $kvInvoiceContext = [];
        $kvInvoiceContext[2] = ['Bauvorhaben:' => $offer->getStationAddress().', '.$offer->getStationZip()];
        $kvInvoiceContext[3] = ['Leistungszeitraum:' => ''];

        // @todo by offerSubCategory
        if (2 == $offer->getCategory()->getId()) {
            $kvInvoiceContext[1] = ['Photovoltaik Anlage:' => '##offerName##'];
            $kvInvoiceContext[4] = ['Batterie:' => '0 kWh'];
            $kvInvoiceContext[5] = ['Jahresertrag:' => '0 kWh'];
            $kvInvoiceContext[6] = ['Jahresverbrauch:' => '0 kWh'];
        } else {
            $kvInvoiceContext[1] = ['Auftrag:' => '##offerName##'];
        }
        $kvs = [];
        sort($kvInvoiceContext, SORT_NUMERIC);

        foreach ($kvInvoiceContext as $k => $kv) {
            $ak = array_key_first($kv);
            $kvs['invoice']['name'][$k] = $ak;
            $kvs['invoice']['text'][$k] = $kv[$ak];
        }
        $parts = array_merge($invoice->getContext() ?? [], $kvs);
        $invoice->setContext($parts);
    }

    /**
     * @return float|int|string
     */
    private function setPartPrice(Invoice $invoice, Invoice $partInvoice, array $partPlusInvoices)
    {
        $kvPayedContext = [];
        $summe = function (Invoice $invoice, bool $formatted = true) {
            if (empty($invoice->getPos0Price())) {
                if ($invoice->getPos1Price() < 0) {
                    $invoice->setPos1Price($invoice->getPos1Price() * -1);
                }
            }

            if (false === $formatted) {
                return ($invoice->getPos0Price() ?? 0) +
                        ($invoice->getPos1Price() ?? 0) +
                        ($invoice->getPos2Price() ?? 0) +
                        ($invoice->getPos3Price() ?? 0);
            }

            return number_format(
                ($invoice->getPos0Price() ?? 0) +
                ($invoice->getPos1Price() ?? 0) +
                ($invoice->getPos2Price() ?? 0) +
                ($invoice->getPos3Price() ?? 0), 2, ',', '.').' €';
        };
        $kvPayedContext['payed']['name'][1] = $this->translator->trans('ip.t.lineNext', [
            '%invoiceNumber%' => $partInvoice->getNumber(),
            '%date%' => (!empty($partInvoice->getSendDate()) ? $partInvoice->getSendDate()->format('d.m.Y') : ''),
        ]);
        $kvPayedContext['payed']['text'][1] = $summe($partInvoice);
        $price = $summe($partInvoice, false);
        $i = 2;
        foreach ($partPlusInvoices as $plusInvoice) {
            if (null !== $plusInvoice->getSendDate()) {
                $kvPayedContext['payed']['name'][$i] = $this->translator->trans('ip.t.lineNext', ['%invoiceNumber%' => $plusInvoice->getNumber(), '%date%' => $plusInvoice->getSendDate()->format('d.m.Y')]);
                $kvPayedContext['payed']['text'][$i] = $summe($plusInvoice);
                $price += $summe($plusInvoice, false);
                ++$i;
            }
        }
        $parts = array_merge($invoice->getContext() ?? [], $kvPayedContext);
        $invoice->setContext($parts);

        return $price;
    }

    private function getInvoicePdf(Order $order, Invoice $invoice): string
    {
        $html = $this->renderView('app/invoice/pdf/invoice.html.twig', [
            'offer' => $order->getOffer(),
            'invoice' => $invoice,
            'order' => $order,
            'text' => $invoice->getText(),
            'type' => $invoice->getType(),
        ]);

        return $this->knp->getOutputFromHtml($html, [
            'disable-smart-shrinking' => true,
            // 'orientation'=>'Landscape',
            'default-header' => false,
            'margin-top' => '0',
            'margin-left' => '0',
            'margin-bottom' => '0',
            'margin-right' => '0',
        ]
        );
    }

    private function getIndividualInvoicePdf(Invoice $invoice): string
    {
        $html = $this->renderView('app/invoice/pdf_individual/invoice.html.twig', [
            'invoice' => $invoice,
            'type' => 'individual',
            'customer' => $invoice->getCustomer(),
        ]);

        return $this->knp->getOutputFromHtml($html, [
            'disable-smart-shrinking' => true,
            // 'orientation'=>'Landscape',
            'default-header' => false,
            'margin-top' => '0',
            'margin-left' => '0',
            'margin-bottom' => '0',
            'margin-right' => '0',
        ]
        );
    }

    private function updateInvoiceContext(Request $request, ?Invoice $invoice = null): JsonResponse
    {
        if (null === $invoice) {
            return $this->json('');
        }
        $post = $request->request->all();
        $invoiceContext = $invoice->getContext();
        if (!empty($post['invoice_option'])) {
            $postContext = $post['invoice_option']['context'];
            $this->addInvoiceContext($postContext, $invoiceContext);
            $invoice->setContext($invoiceContext);
            $this->em->persist($invoice);
            $this->em->flush();
        }

        return $this->json($invoiceContext);
    }

    private function setInvoicePosData(Invoice $invoice, int $key): void
    {
        $order = $invoice->getInvoiceOrder();
        $offer = $order->getOffer();
        $invoice->setPos0Date($order->getCreatedAt()->format('d.m.Y'));
        $context = $offer->getContext();
        if (isset($context['invoice_pay'])) {
            $invoice->setPos0Text($context['invoice_pay'][$key]['name']);
            $partPrice = $this->priceService->calculate70Netto($offer, (int) $context['invoice_pay'][$key]['value']);
            $invoice->setPos0Price($partPrice);
        } else {
            $invoice->setPos0Date($order->getCreatedAt()->format('d.m.Y'));
            $invoice->setPos0Text($offer->getOption() ? $offer->getOption()->getInvoicePercent().'% Material Abschlagszahlung' : '');
            $invoice->setPos0Price(0);
        }
    }
}
