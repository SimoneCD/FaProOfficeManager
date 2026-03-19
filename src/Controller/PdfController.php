<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Order;
use App\Entity\Product;
use App\Service\PHPMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['de' => '/pdf', 'en' => '/pdf'])]
class PdfController extends BaseController
{
    public $managerRegistry;
    private $knp;

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain)
    {
        $binary = $_ENV['WKHTMLTOPDF_PATH'];
        $this->knp = new Pdf($binary);
        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    #[Route(path: ['de' => '/pdfm', 'en' => '/pdfme'], name: 'pdf_e', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function pdfm(): Response
    {
        dd('todo');
        #dump(getcwd());
        $pdfPath = 'hd/app/anmeldung-netzanschluss.pdf';

// PDF-Objekt erstellen
        $pdf = new Fpdi();
        $pdf->setSourceFile($pdfPath);

// Seitenzahl auswählen
        $page = $pdf->importPage(1); // Erste Seite

// Seite hinzufügen
        $pdf->AddPage();
        $pdf->useTemplate($page);
        $pdf->SetFormValue('FieldName', 'Value');
        dd($pdf);
    }

    #[Route(path: ['de' => '/{id}/angebot.pdf', 'en' => '/{id}/offer.pdf'], name: 'pdf_offer', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function offer(Offer $offer): Response
    {
        if (!$this->isGranted('POST_EDIT', $offer)) {
            $this->addFlash('danger', $this->translator->trans('o.error.noAccessTo', ['%offerNumber%' => $offer->getNumber()]));
            $this->redirectToRoute('booking_index');
        }
        $items = [];
        $i = 0;
        foreach ($offer->getOfferItems() as $item) {
            if ($item->getItem() instanceof Product) {
                $items[$item->getItem()->getProductNumber().$i] = $item;
            } else {
                $items['00.'.sprintf('%2d', $i)] = $item;
            }
            ++$i;
        }
        ksort($items);
        $html = $this->renderView('app/offer_pdf/offer.html.twig', [
            'offer' => $offer,
            'items' => $items,
        ]);

        return new Response(
            $this->knp->getOutputFromHtml($html, [
                    // 'orientation'=>'Landscape',
                    'disable-smart-shrinking' => true,
                    'default-header' => false,
                    'margin-top' => '0',
                    'margin-left' => '0',
                    'margin-bottom' => '0',
                    'margin-right' => '0',
                ]
            ),
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }

    #[Route(path: ['de' => '/{id}/aufmass-protokoll.pdf', 'en' => '/{id}/allowance-protocol.pdf'], name: 'pdf_protocol', methods: ['GET'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function protocol(EntityManagerInterface $entityManager, Offer $offer): Response
    {
        // todo
//        if (!$this->isGranted('ROLE_MONTAGE') || !$this->isGranted('POST_EDIT', $offer)) {
//            $this->addFlash('danger', $this->translator->trans('o.error.noAccessTo', ['%offerNumber%' => $offer->getNumber()]));
//            $this->redirectToRoute('booking_index');
//        }

        $html = $this->renderView('offer/montage/intern/pdf/aufmass_pdf.html.twig', [
            'offer' => $offer,
            'montage' => false,
            'auftraege' => null,
        ]);

        return new Response(
            $this->knp->getOutputFromHtml($html, [
                    // 'orientation'=>'Landscape',
                    'disable-smart-shrinking' => true,
                    'default-header' => false,
                    'margin-top' => '0',
                    'margin-left' => '0',
                    'margin-bottom' => '0',
                    'margin-right' => '0',
                ]
            ),
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }

    #[Route(path: ['de' => '/{id}/aufmass-protokoll.html', 'en' => '/{id}/allowance-protocol.html'], name: 'html_protocol', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function aufmassProtocolHtml(EntityManagerInterface $entityManager, Offer $offer): Response
    {
        if (!$this->isGranted('POST_EDIT', $offer)) {
            $this->addFlash('danger', $this->translator->trans('o.error.noAccessTo', ['%offerNumber%' => $offer->getNumber()]));
            $this->redirectToRoute('booking_index');
        }

        return $this->render('offer/montage/index.protocol.html.twig', [
            'offer' => $offer,
            'montage' => false,
            'auftraege' => null,
        ]);
    }

    #[Route(path: ['de' => '/{id}/angebot.html', 'en' => '/{id}/offer.html'], name: 'pdfhtml_offer', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function offerhtml(Offer $offer): Response
    {
        $items = [];
        foreach ($offer->getOfferItems() as $k => $item) {
            $items[$k] = $item;
        }
        ksort($items);

        return $this->render('app/offer_pdf/offer.html.twig', [
            'offer' => $offer,
            'items' => $items,
        ]);
    }

    // #[Route(path: '/angebot-pdf/{accessKey}/angebot.pdf', name: 'pdf_offer_public', methods: ['GET'])]
    public function offerPublic(string $accessKey): Response
    {
        $order = $this->managerRegistry->getRepository(Order::class)->findOneBy([
            'accessKey' => $accessKey,
        ]);

        if (!$order instanceof Order || $accessKey == 'ungueltig') {
            return $this->render('order/no_access.html.twig', ['order' => '']);
        }
        $items = [];
        $offer = $order->getOffer();
        new \DateTime();
        $order->getOffer()->getCustomer();
        foreach ($offer->getOfferItems() as $item) {
            $items[$item->getItem()->getProductNumber()] = $item;
        }
        ksort($items);

        $html = $this->renderView('app/offer_pdf/offer.html.twig', [
            'offer' => $offer,
            'items' => $items,
        ]);

        return new Response(
            $this->knp->getOutputFromHtml($html, [
                    // 'orientation'=>'Landscape',
                    'disable-smart-shrinking' => true,
                    'default-header' => false,
                    'margin-top' => '0',
                    'margin-left' => '0',
                    'margin-bottom' => '0',
                    'margin-right' => '0',
                ]
            ),
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                // 'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    // #[Route(path: 'auftrag/{accessKey}/bestaetigung', name: 'auftrag_offer_ok', methods: ['GET'])]
    // #[Route(path: 'auftrag/bestaetigung', name: 'auftrag_offer_ungueltig', methods: ['GET'])]
    public function orderByKeyPublic(?string $accessKey = 'ungueltig'): Response
    {
        $order = $this->managerRegistry->getRepository(Order::class)->findOneBy([
            'accessKey' => $accessKey,
        ]);
        if (!$order instanceof Order) {
            return $this->render('order/no_access.html.twig', ['order' => '']);
        }
        if ($order->isBestaetigt()) {
            return $this->render('order/bestaetigt.html.twig', ['order' => $order]);
        }

        $order->setStatus('bestaetigt');
        // $order->setAccessKey('hjhhgh-lkJIjkl-fgdrft-7uzh4r');
        $offer = $order->getOffer();
        $offer->setStatus('bestaetigt');
        $order->setBestaetigt(true);

        $this->managerRegistry->getManager()->persist($offer);
        $this->managerRegistry->getManager()->persist($order);
        $this->managerRegistry->getManager()->flush();
        try {
            $fp = fopen('customer_log', 'a+');
            $date = new \DateTime();
            $customer = $order->getOffer()->getCustomer();
            fputcsv($fp, ['BESTÄTIGUNG', $date->format('d.m.Y H:i'), 'Order: '.$order->getId(), 'Kunde: '.$customer->getFullName().'('.$customer->getId().')']);
            fclose($fp);
        } catch (\Exception $exception) {
        }

        return $this->render('order/done.html.twig', ['order' => $order]);
    }
}
