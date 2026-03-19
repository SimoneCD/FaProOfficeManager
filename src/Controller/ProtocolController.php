<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Protocol;
use App\Service\PHPMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * auftragsblätter.
 */
#[Route(path: ['de' => '/protocol', 'en' => '/protocol'])]
#[IsGranted('ROLE_MONTAGE')]
class ProtocolController extends BaseController
{
    use TargetPathTrait;

    private $knp;

    private readonly ManagerRegistry $managerRegistry;

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain)
    {
        $binary = $_ENV['WKHTMLTOPDF_PATH'];
        $this->knp = new Pdf($binary);
        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    /** protocol live update */
    #[Route(path: '/ajax/{id}/protocol/{type}/save', name: 'ajax_protocol_save', methods: ['GET', 'POST'])]
    public function saveProtocol(Request $request, Offer $offer, string $type): JsonResponse
    {
        $customer = $offer->getCustomer();
        $post = $request->request->all();

        $context = $offer->getContext();
        if (isset($post[$type])) {
            $newContext = $post[$type];
            if (!isset($context[$type]) && isset($context['protocol'])) {
                $context[$type] = $context['protocol'];
            } else {
                $context[$type] = [];
            }
            $context = array_merge($context[$type], $newContext);
//            dd($context);
            $protocol = new Protocol();
            $protocol->setName($type);
            $protocol->setCreateAt(new \DateTime());
            $protocol->setUser($this->getUser());
            $protocol->setType($type);
            $offer->setContext(null);
            $this->em->persist($offer);
            $this->em->flush();

            return $this->json(['done']);
        }

        return $this->json(['nothing-done']);
    }

    /** protocol live update */
    #[Route(path: '/ajax/{id}/protocol/{type}/change', name: 'ajax_protocol_changes', methods: ['GET', 'POST'])]
    public function changeProtocol(Request $request, Offer $offer, string $type): JsonResponse
    {
        $protocolCustomer = $offer->getCustomer();
        $post = $request->request->all();
        $newContext = $post[$type];

        $context = $offer->getContext();
        if (isset($post[$type])) {
            unset($context[$type]);
            $context = array_merge($context, [$type => $newContext]);
        }

        $offer->setContext($context);
        $this->em->persist($offer);
        $this->em->flush();

        return $this->json(['done']);
    }

    /** protocol live update */
    #[Route(path: '/ajax/{id}/new/{type}/protocol', name: 'ajax_new_protocol', methods: ['GET', 'POST'])]
    public function loadNewProtocol(Offer $offer, string $type): Response
    {
        return $this->render('offer/montage/intern/_selected_protocol.html.twig', [
            'type' => $type,
            'offer' => $offer,
        ]);
    }

    /** Ladet Kundendaten für den Kundenservice-Kundenchat */
    #[Route(path: ['de' => '/{id}/protokoll-{type}.pdf', 'en' => '/{id}/protocol-{type}.pdf'], name: 'app_offer_protocol_pdf', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function protocolPdf(Offer $offer, string $type): Response
    {
        $html = $this->renderView('offer/montage/intern/pdf/protocol_pdf.html.twig', [
            'offer' => $offer,
            'type' => $type,
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

    /** Ladet Kundendaten für den Kundenservice-Kundenchat */
    #[Route(path: ['de' => '/{id}/protokoll-{type}.html', 'en' => '/{id}/protocol-{type}.html'], name: 'app_offer_protocol_html', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function protocolHtml(Offer $offer, string $type): Response
    {
        return $this->render('offer/montage/intern/pdf/protocol_pdf.html.twig', [
            'offer' => $offer,
            'type' => $type,
        ]);
    }
}
