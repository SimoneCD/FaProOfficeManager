<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\CustomerNotes;
use App\Entity\Inquiry;
use App\Entity\Invoice;
use App\Entity\Offer;
use App\Entity\OfferCategory;
use App\Entity\OfferOption;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Form\CustomerPassType;
use App\Form\InquiryCustomerType;
use App\Form\InquiryType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
/**
 * Class IndexController.
 */
#[Route(['de' => '/kundenportal', 'en' => '/customer-portal'])]
class PublicController extends BaseController
{
    #[Route('/login', name: 'login_check', methods: ['GET'])]
    public function login(): Response
    {
        // never called (security.yaml function)
        // and render a template with the button
        return $this->render('security/process_login_link.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route(['de' => '/kontakt', 'en' => '/contact'], name: 'public_contact', methods: ['GET'])]
    public function contact(): Response
    {
        return $this->json('ok');
    }

    #[Route(['de' => '/kunden/passwort', 'en' => '/customer-password'], name: 'login_password', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function passwordGenerateIndex(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var Customer $customer */
        $customer = $this->getUser();
        $form = $this->createForm(CustomerPassType::class, $customer);
        $form->handleRequest($request);
        $post = $request->request->all();

        if ($form->isSubmitted() && $form->isValid()) {
            if (!empty($post['customer_pass']['plainPassword'])) {
                $customer->setPassword($passwordHasher->hashPassword($customer, $post['customer_pass']['plainPassword']['first']));
                $this->em->persist($customer);
            }
            $this->em->persist($customer);
            $this->em->flush();

            return $this->redirectToRoute('public_index');
        }

        return $this->render('security/process_login_link.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }

    #[Route(['de' => '/angebote', 'en' => 'offers'], name: 'public_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if ($this->getUser() === null || ($this->getUser() instanceof User && empty($request->query->get('user'))) || !$this->isGranted('ROLE_CUSTOMER')) {
            return $this->redirectToRoute('security_login');
        }
        /** @var Customer $customer */
        $customer = $this->getUser();

        if ($request->query->get('user') && $this->isGranted('ROLE_MONTAGE')) {
            $customer = $this->em->getRepository(Customer::class)->find($request->query->get('user'));
        }

        return $this->render('public/index.html.twig', [
            'user' => $customer,
        ]);
    }

    #[Route(['de' => '/angebote/produkte/{offer}', 'en' => '/offers/products/{offer}'], name: 'public_products', methods: ['GET', 'POST'])]
    public function productsBox(Offer $offer): Response
    {
        if (!$this->isGranted('ROLE_MONTAGE') && $offer->getCustomer()->getId() !== $this->getUser()->getId()) {
            return new JsonResponse(false);
            // $this->redirectToRoute('security_logout');
        }
        /** @var Customer $customer */
        $customer = $this->getUser();

        return $this->render('public/products.html.twig', [
            'user' => $this->getUser(),
            'offer' => $offer,
            // 'products' => $this->em->getRepository(Offer::class)->findMaterialsByOffer($offer),
        ]);
    }

    #[Route(path: ['de' => '/angebote/{id}/src', 'en' => '/offers/{id}/src'], name: 'public_offer_src', methods: ['GET', 'POST'])]
    public function offerSrcUrl(Offer $offer): Response
    {
        if (!$this->isGranted('ROLE_MONTAGE') && $offer->getCustomer()->getId() !== $this->getUser()->getId()) {
            return new JsonResponse(false);
            // $this->redirectToRoute('security_logout');
        }
        $finder = new Finder();
        $finder->name('Angebot_'.$offer->getNumber().'*');
        $dir = $this->getParameter('kernel.project_dir').'/pdf_AggSF-2/angebote/'.($this->subdomain ?? 'app').'/';

        if (!is_dir($dir)) {
            $dir = $this->getParameter('kernel.project_dir').'/pdf_AggSF-2/angebote/';
        }
        // dd($dir);
        $files = [];
        if (is_dir($dir)) {
            if ($finder->in($dir)->hasResults()) {
                foreach ($finder->in($dir) as $file) {
                    /* @var SplFileInfo $file */
                    $name = time().'_'.$file->getFilename();
                    if (!file_exists($this->getParameter('kernel.project_dir').'/public/var/tmp/'.$name)) {
                        file_put_contents($this->getParameter('kernel.project_dir').'/public/var/tmp/'.$name, $file->getContents());
                    }
                    $files[] = '/var/tmp/'.$name;
                }
            } else {
                $files[] = $this->generateUrl('public_pdf_offer', ['id' => $offer->getId()]);
            }
        }

        return $this->render('public/offer_pdf.html.twig', [
            'offer' => $offer,
            'files' => $files,
        ]);
    }

    #[Route(path: ['de' => '/rechnungs/{id}/src', 'en' => '/invoice/{id}/src'], name: 'public_invoice_pdf', methods: ['GET', 'POST'])]
    public function invoice(Invoice $invoice): Response
    {
        if (!$this->isGranted('ROLE_MONTAGE') && $invoice->getCustomer()->getId() !== $this->getUser()->getId()) {
            return new JsonResponse(false);
            // $this->redirectToRoute('security_logout');
        }
        $finder = new Finder();
        $number = $invoice->getNumber();
        if (empty($number)) {
            $number = $invoice->getInvoiceOrder()->getOffer()->getNumber();
            if (empty($number)) {
                return new JsonResponse(false);
            }
            if ($invoice->getType() === 'part') {
                $number = $number.'.1';
            } else {
                $number = $number.'.2';
            }
        }
        $finder->name('Invoice_'.$number.'*');
        $dir = $this->getParameter('kernel.project_dir').'/pdf_AggSF-2/invoices/'.($this->subdomain ?? 'app').'/';

        if (!is_dir($dir)) {
            $dir = $this->getParameter('kernel.project_dir').'/pdf_AggSF-2/invoices/';
        }
        // dd($dir);
        $files = [];
        if (is_dir($dir)) {
            if ($finder->in($dir)->hasResults()) {
                foreach ($finder->in($dir) as $file) {
                    /* @var SplFileInfo $file */
                    $name = time().'_'.$file->getFilename();
                    if (!file_exists($this->getParameter('kernel.project_dir').'/public/var/tmp/'.$name)) {
                        file_put_contents($this->getParameter('kernel.project_dir').'/public/var/tmp/'.$name, $file->getContents());
                    }
                    $files[] = '/var/tmp/'.$name;
                }
            } else {
                $files[] = $this->generateUrl('public_pdf_offer', ['id' => $invoice->getId()]);
            }
        } else {
            $files[] = $this->generateUrl('public_pdf_offer', ['id' => $invoice->getId()]);
        }

        return $this->render('public/invoice_pdf.html.twig', [
            'invoice' => $invoice,
            'files' => $files,
        ]);
    }

    #[Route(path: ['de' => '/angebote/{id}/pdf', 'en' => '/angebote/{id}/pdf'], name: 'public_pdf_offer', methods: ['GET'])]
    public function offerPdf(Offer $offer): Response
    {
        if ((!$this->isGranted('ROLE_MONTAGE')) && $offer->getCustomer()->getId() != $this->getUser()->getId()) {
            return new JsonResponse(false);
            // $this->redirectToRoute('security_logout');
        }
        $binary = $_ENV['WKHTMLTOPDF_PATH'];
        $knp = new Pdf($binary);

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
            $knp->getOutputFromHtml($html, [
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

    #[Route(path: ['de' => '/rechnungen/{id}/pdf', 'en' => '/invoice/{id}/pdf'], name: 'public_pdf_invoice', methods: ['GET'])]
    public function invoicePdf(Invoice $invoice): Response
    {
        return new Response(
            $this->getInvoicePdf($invoice->getInvoiceOrder(), $invoice),
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                // 'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    #[Route('/addNotes', name: 'ajax_public_add_notes', methods: ['GET', 'POST'])]
    public function addCustomerNote(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $post = json_decode($request->getContent());
        if ($this->isGranted('ROLE_MONTAGE') and !empty($post->customer)) {
            $user = $entityManager->getRepository(Customer::class)->find($post->customer);
        }
        $note = new CustomerNotes();
        $date = new \DateTimeImmutable();
        $note->setCreatedAt($date);
        $note->setCustomer($user);
        $user->addCustomerNote($note);
        $note->setType('sos');
        $note->setNote(nl2br($post->note));
        if ($this->isGranted('ROLE_EMPLOYEE_SERVICE') and !empty($post->customer)) {
            $note->setUser($this->getUser());
            foreach ($user->getCustomerNotes() as $notes) {
                $notes->setAnsweredAt($date);
                $entityManager->persist($notes);
            }
            $note->setAnsweredAt($date);
        }
        $entityManager->persist($user);
        $entityManager->persist($note);
        $entityManager->flush();
        $entityManager->refresh($user);

        return $this->render('public/components/_notes.html.twig', [
            'user' => $user,
        ]);
    }

    private function getInvoicePdf(Order $order, Invoice $invoice): string
    {
        $binary = $_ENV['WKHTMLTOPDF_PATH'];
        $knp = new Pdf($binary);

        $html = $this->renderView('app/invoice/pdf/invoice.html.twig', [
            'offer' => $order->getOffer(),
            'invoice' => $invoice,
            'order' => $order,
            'text' => $invoice->getText(),
            'type' => $invoice->getType(),
        ]);

        return $knp->getOutputFromHtml($html, [
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
}
