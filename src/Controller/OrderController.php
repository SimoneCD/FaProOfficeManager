<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Order;
use App\Entity\User;
use App\Service\PHPMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['de' => '/bestaetigte-angebote', 'en' => '/confirmed-offers'])]
#[IsGranted('ROLE_EMPLOYEE_EXTERN')]
class OrderController extends BaseController
{
    private readonly Pdf $knp;

    public function __construct(Pdf $knp, EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain)
    {
        $this->knp = $knp;
        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    #[Route(path: '/', name: 'order_index', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $this->em->getRepository(Order::class)->findAll(),
        ]);
    }

    #[Route(path: '/{id}/save', name: 'order_save', methods: ['POST', 'GET'])]
    public function save(Request $request, Offer $offer, string $templateDirectory): Response
    {
        if (!$this->getUser() instanceof User || !$this->isGranted('POST_EDIT', $offer)) {
            return $this->redirectToRoute('booking_index');
        }

        $post = $request->request->all();
        $option = $offer->getOption();
        $postContext = $post['offer_option']['context'];
        $offerContext = $option->getContext();
        $this->addOptionContext($postContext ?? [], $offerContext);
        $option->setContext($offerContext);
        $this->em->persist($option);
        $estimateOffer = in_array($offer->getStatus(), ['besichtigung', 'estimate']);
        $sm = $request->request->get('sendMail');
        $smt = $offer->getOption()->getContext();
        $smt = $estimateOffer ? $smt['estimateEmailText'] : $smt['offerEmailText'];
        $price = 0.00;
        $items = $offer->getOfferItems();
        foreach ($items as $item) {
            $price += $item->getPrice() * $item->getAmount();
        }
        $price += $offer->getWallboxProduct() !== null ? $offer->getWallboxProduct()->getPrice() * $offer->getAmount() : $price;
        $accessKey = md5(time().'SF');

        $order = $offer->getOrder() instanceof Order ? $offer->getOrder() : new Order();
        $order->setText($smt);
        $order->setOffer($offer);

        $order->setPrice($price);
        $order->setTax($offer->getTax());
        $order->setAccessKey($accessKey);
        $order->setBestaetigt(false);

        $offer->setOrder($order);
        if ($offer->getStatus() === 'open' || $offer->getStatus() === 'storno') {
            $order->setStatus('gesendet');
            $offer->getOption()->setBlendOut(false);
        } else {
            $order->setStatus('estimate');
            $offer->setStatus('estimate');
        }
        $offer->setStatusDate(new \DateTime());
        $offerOptionContext = $offer->getOption()->getContext();
        if (empty($offerOptionContext) || (empty($offerOptionContext['kv']['value']) && isset($offerOptionContext['kv']['value']))) {
            $this->addFlash('danger', $this->translator->trans('o.error.checkProjectData'));

            return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
        }
        if (isset($offerOptionContext['kv']['value'])) {
            foreach ($offerOptionContext['kv']['value'] as $k => $v) {
                if ($v === 'dd.mm.yyyy' || strstr($v, '##')) {
                    $this->addFlash('danger', $this->translator->trans('o.error.checkProjectData'));

                    return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
                }
            }
        }
        if (!$estimateOffer && (empty($offerOptionContext['anrede']) || strstr($offerOptionContext['anrede'], '##'))) {
            $this->addFlash('danger', 'o.error.checkSalutation');

            return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
        } elseif ($estimateOffer && (empty($offerOptionContext['estimateAnrede']) || strstr($offerOptionContext['estimateAnrede'], '##'))) {
            $this->addFlash('danger', 'o.error.checkSalutation');

            return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
        }
        if (!$estimateOffer && (empty($offerOptionContext['offerEmailText']) || strstr($offerOptionContext['offerEmailText'], '##'))) {
            $this->addFlash('danger', 'o.error.checkEmailSalutation');

            return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
        } elseif ($estimateOffer && (empty($offerOptionContext['estimateEmailText']) || strstr($offerOptionContext['estimateEmailText'], '##'))) {
            $this->addFlash('danger', 'o.error.checkEmailSalutation');

            return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
        }

        $this->em->persist($order);
        $this->em->persist($offer);
        $this->em->flush();
        $this->em->refresh($order);

        $date = new \DateTime();
        if ($sm !== null) {
            try {
                if ($offer->getStatus() === 'estimate' || $offer->getStatus() === 'besichtigung') {
                    $filname = 'Kostenvoranschlag_'.$offer->getNumber().'_'.$date->format('d-m-Y_H').'.pdf';
                } else {
                    $filname = $this->translator->trans('w.offer').'_'.$offer->getNumber().'_'.$date->format('d-m-Y_H').'.pdf';
                }
                $pdf = $this->getPdf($offer->getOrder(), $templateDirectory);
                $dir = $this->getParameter('kernel.project_dir').'/pdf_AggSF-2/angebote/'.($this->subdomain ?? 'app').'/';
                @file_put_contents($dir.$filname, $pdf);
                if ($offer->getStatus() === 'open') {
                    $offer->setStatus('gesendet');
                    $order->setStatus('gesendet');
                    $order->setCreatedAt($date);
                }
                if ($estimateOffer) {
                    $order->setSendPreOfferAt($date);
                } else {
                    $order->setSendAt($date);
                }
                $this->mailerService->setUserToLog($this->getUser());
                if (false === $this->mailerService->sendOrder($order, $dir.$filname, $this->translator)) {
                    $this->addFlash('danger', $this->translator->trans('o.error.checkSendEmail'));

                    return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
                }
                $this->em->persist($order);
                $this->em->persist($offer);
                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('o.success.send'));

                return $this->redirectToRoute('offer_category_index', ['id' => $offer->getCategory()->getId()]);
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'o.error.send'.$exception->getMessage());

                return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
            }
        } else {
            $this->addFlash('success', 'o.success.noSend');
        }

        return $this->redirectToRoute('offer_category_index', ['id' => $offer->getCategory()->getId()]);
        // return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
    }

    private function getPdf(Order $order, string $templateDirectory): string
    {
        $items = [];
        $i = 0;
        $offer = $order->getOffer();
        foreach ($offer->getOfferItems() as $item) {
            $items[$item->getItem() ? $item->getItem()->getProductNumber().$i : $i] = $item;
            ++$i;
        }
        ksort($items);
        $html = $this->renderView('app/offer_pdf/offer.html.twig', [
            'offer' => $offer,
            'items' => $items,
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
}
