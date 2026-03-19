<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ContactPerson;
use App\Entity\Customer;
use App\Entity\Inquiry;
use App\Entity\Link;
use App\Entity\Offer;
use App\Entity\OfferCategory;
use App\Entity\OfferOption;
use App\Entity\OfferSubCategory;
use App\Entity\User;
use App\Form\ContactPersonType;
use App\Form\InquiryRecommendationCustomerType;
use App\Form\LinkExternType;
use App\Repository\OfferRepository;
use App\Repository\ProductRepository;
use App\Service\PHPMailerService;
use App\Service\SlackService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['de' => '/empfehlung', 'en' => '/recommendation'])]
class RecommendationController extends OfferBaseController
{
    private const ADMIN = 16;
    private const DEFAULT_CODE_AMOUNT = 500;

    public function __construct(private SlackService $slackService, OfferRepository $offerRepository, ProductRepository $productRepository, EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain)
    {
        parent::__construct($offerRepository, $productRepository, $em, $client, $mailerService, $translator, $subdomain);
    }

    #[Route(path: '/', name: 'app_recommendation', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $frame = $request->query->get('frame') ? 'select-' : '';
        $inquiry = $this->getInquire($request);
        $customer = null;
        $link = null;
        if (!empty($request->request->get('key'))) {
            $link = $this->em->getRepository(Link::class)->findOneBy([
                'link' => $request->query->get('key') ?? ($request->request->get('key') ?? ''),
            ]);
        }
        $inquiry->setRefererFrom($frame.'funnel');
        $form = $this->createForm(InquiryRecommendationCustomerType::class, $inquiry, [
            'action' => $this->generateUrl('app_recommendation', ['frame' => $request->query->get('frame') ?? '']),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            /** @var Customer $customer */
            $customer = $data->getCustomer();
            $city = $request->request->get('city');
            if (!empty($city)) {
                $zip = explode(' ', $city);
                $customer->setZip($zip[0]);
                $customer->setCity($zip[1].(isset($zip[2]) ? ' '.$zip[2] : '').(isset($zip[3]) ? ' '.$zip[3] : ''));
            }
            //            if (!empty($post['customer']['email'])) {
            //                $ce = $this->em->getRepository(Customer::class)->findOneBy([
            //                     'email' => $post['customer']['email'],
            //                 ]);
            //                if ($ce instanceof Customer) {
            //                    $customer = $ce;
            //                }
            //            }
            //            if (empty($ce) && !empty($post['customer']['phone'])) {
            //                $ce = $this->em->getRepository(Customer::class)->findOneBy([
            //                     'phone' => $post['customer']['phone'],
            //                 ]);
            //                if ($ce instanceof Customer) {
            //                    $customer = $ce;
            //                }
            //            }
            if ($customer instanceof Customer) {
                $inquiry->setCustomer($customer);
                if (empty($customer->getSex())) {
                    $customer->setSex('');
                }
                $customer->setPassword('');
                $customer->setSurName($customer->getName());
            }
            $customer = $inquiry->getCustomer();
            $customer->setLink($link);
            //            if (empty(trim($customer->getEmail())) && empty(trim($customer->getPhone()))) {
            //                $form->addError(new FormError('Email oder Telefonnummer müssen angegeben sein!'));
            //                // $this->addFlash('danger', 'Email oder Telefonnummer müssen angegeben sein!');
            //
            //                return $this->render('offer/types/'.$frame.'recommendation.html.twig', [
            //                    'inquiry' => $inquiry,
            //                    'offer' => $inquiry->getOffer(),
            //                    'form' => $form->createView(),
            //                    'link' => $link,
            //                    'errors' => ['message' => 'Telefonnummer oder Email, müssen angegeben werden'],
            //                ]);
            //            }
            //            if (empty(trim($customer->getSurName()))) {
            //                $form->addError(new FormError('Der Nachname darf nicht leer sein.'));
            //                // $this->addFlash('danger', 'Vor- oder Nachname dürfen nicht leer sein!');
            //
            //                return $this->render('offer/types/'.$frame.'recommendation.html.twig', [
            //                    'inquiry' => $inquiry,
            //                    'offer' => $inquiry->getOffer(),
            //                    'form' => $form->createView(),
            //                    'link' => $link,
            //                    'errors' => ['message' => 'Der Name muss eingetragen sein'],
            //                ]);
            //            }
            // $this->em->persist($customer);
            // $this->em->persist($inquiry);
            // $this->em->refresh($inquiry);
            $customer = $inquiry->getCustomer();
            $offer = $inquiry->getOffer();
            $offer->setCustomer($customer);
            $offer->setUser($this->em->getRepository(User::class)->find(self::ADMIN));
            $offer->setStatus('call');
            $offer->setStatusDate(new \DateTime());
            $offer->setTax(19);
            $offer->setPrice(0);
            $offer->setContext(null);
            $offer->setUseCase('privat');
            $offer->setNotice($inquiry->getNotice());
            $offer->setOfferDate(new \DateTime());
            $offer->setNumber('1.');
            $customer->setCustomerNumber('');
            $offer->setInquiry($inquiry);
            $inquiry->setOffer($offer);
            $customer->setPassword('');

            $this->em->persist($customer);
            $this->em->persist($offer);
            $this->em->persist($inquiry);
            $this->em->flush();

            $this->em->refresh($offer);

            $this->addSubCategorieData($offer);
            $this->addInquiryContext($request, $offer);
            $iq = $offer->getInquiry();
            $iq->setContext($this->inquiryContext);

            $customer->setCustomerNumber(''.(Customer::CUSTOMER_START + $customer->getId()));
            $offer->setNumber($customer->getCustomerNumber().'.'.$this->getNextOfferNumber($customer));
            $this->em->persist($offer);
            $this->em->flush();
            $this->em->refresh($offer);
            if ($this->getParameter('app_active_log')['slack_active'] && $_ENV['APP_ENV'] !== 'dev') {
                try {
                    if (!empty($request->request->get('key')) && $request->request->get('key') == 'ZOE500') {
                        $this->slackService->addSlackLogToChannel('slack_funnel', 'Neuer OBI-Funnel '.$offer->getNumber());
                    } else {
                        $this->slackService->addSlackLogToChannel('slack_funnel', 'Neuer Funnel '.$offer->getNumber());
                    }
                } catch (\Exception $exception) {
                }
            }

            return $this->render('recommendation/caroussel.html.twig', [
                // 'form' => $form->createView(),
                'done' => true,
            ]);
        } elseif ($form->isSubmitted()) {
            return $this->render('recommendation/caroussel.html.twig', [
                'form' => $form->createView(),
                'error' => 'Fehler beim Senden!',
                'errors' => $form->getErrors(),
                'link' => $link,
            ]);
        }

        if (!$link instanceof Link && !empty($request->query->get('key'))) {
            return $this->render('offer/types/'.$frame.'recommendation.html.twig', [
                // 'products' => $this->getOfferMainProducts($offer),
                'form' => $form->createView(),
                'errors' => ['message' => 'Ihr Empfehlungslink ist nicht gültig. <br>Bitte wenden Sie sich an unseren Kundenservice.'],
            ]);
        }

        return $this->render('offer/types/'.$frame.'recommendation.html.twig', [
            'inquiry' => $inquiry,
            'offer' => $inquiry->getOffer(),
            'category' => $inquiry->getOffer()->getCategory(),
            'link' => $link,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/komplett', name: 'app_komplett_recommendation', methods: ['GET', 'POST'])]
    public function indexKomplett(Request $request): Response
    {
        $frame = $request->query->get('frame') ? 'komplett-' : '';
        $inquiry = $this->getInquire($request);

        $inquiry->setRefererFrom($frame.'funnel');
        $form = $this->createForm(InquiryRecommendationCustomerType::class, $inquiry, [
            'action' => $this->generateUrl('app_komplett_recommendation', ['frame' => $request->query->get('frame') ?? '']),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            /** @var Customer $customer */
            $customer = $data->getCustomer();
            $city = $request->request->get('city');
            if (!empty($city)) {
                $zip = explode(' ', $city);
                $customer->setZip($zip[0]);
                $customer->setCity($zip[1].(isset($zip[2]) ? ' '.$zip[2] : '').(isset($zip[3]) ? ' '.$zip[3] : ''));
            }
            //            if (!empty($post['customer']['email'])) {
            //                $ce = $this->em->getRepository(Customer::class)->findOneBy([
            //                     'email' => $post['customer']['email'],
            //                 ]);
            //                if ($ce instanceof Customer) {
            //                    $customer = $ce;
            //                }
            //            }
            //            if (empty($ce) && !empty($post['customer']['phone'])) {
            //                $ce = $this->em->getRepository(Customer::class)->findOneBy([
            //                     'phone' => $post['customer']['phone'],
            //                 ]);
            //                if ($ce instanceof Customer) {
            //                    $customer = $ce;
            //                }
            //            }
            if ($customer instanceof Customer) {
                $inquiry->setCustomer($customer);
                if (empty($customer->getSex())) {
                    $customer->setSex('');
                }
                $customer->setPassword('');
                $customer->setSurName($customer->getName());
            }
            //            if (empty(trim($customer->getEmail())) && empty(trim($customer->getPhone()))) {
            //                $form->addError(new FormError('Email oder Telefonnummer müssen angegeben sein!'));
            //                // $this->addFlash('danger', 'Email oder Telefonnummer müssen angegeben sein!');
            //
            //                return $this->render('offer/types/'.$frame.'recommendation.html.twig', [
            //                    'inquiry' => $inquiry,
            //                    'offer' => $inquiry->getOffer(),
            //                    'form' => $form->createView(),
            //                    'link' => $link,
            //                    'errors' => ['message' => 'Telefonnummer oder Email, müssen angegeben werden'],
            //                ]);
            //            }
            //            if (empty(trim($customer->getSurName()))) {
            //                $form->addError(new FormError('Der Nachname darf nicht leer sein.'));
            //                // $this->addFlash('danger', 'Vor- oder Nachname dürfen nicht leer sein!');
            //
            //                return $this->render('offer/types/'.$frame.'recommendation.html.twig', [
            //                    'inquiry' => $inquiry,
            //                    'offer' => $inquiry->getOffer(),
            //                    'form' => $form->createView(),
            //                    'link' => $link,
            //                    'errors' => ['message' => 'Der Name muss eingetragen sein'],
            //                ]);
            //            }
            // $this->em->persist($customer);
            // $this->em->persist($inquiry);
            // $this->em->refresh($inquiry);
            $customer = $inquiry->getCustomer();
            $offer = $inquiry->getOffer();
            $offer->setCustomer($customer);
            $offer->setUser($this->em->getRepository(User::class)->find(self::ADMIN));
            $offer->setStatus('call');
            $offer->setStatusDate(new \DateTime());
            $offer->setTax(19);
            $offer->setPrice(0);
            $offer->setContext(null);
            $offer->setUseCase('privat');
            $offer->setNotice($inquiry->getNotice());
            $offer->setOfferDate(new \DateTime());
            $offer->setNumber('1.');
            $customer->setCustomerNumber('');
            $offer->setInquiry($inquiry);
            $inquiry->setOffer($offer);
            $customer->setPassword('');

            $this->em->persist($customer);
            $this->em->persist($offer);
            $this->em->persist($inquiry);
            $this->em->flush();

            $this->em->refresh($offer);

            $this->addSubCategorieData($offer);
            $this->addInquiryContext($request, $offer);
            $iq = $offer->getInquiry();
            $iq->setContext($this->inquiryContext);

            $customer->setCustomerNumber(''.(Customer::CUSTOMER_START + $customer->getId()));
            $offer->setNumber($customer->getCustomerNumber().'.'.$this->getNextOfferNumber($customer));
            $this->em->persist($offer);
            $this->em->flush();
            $this->em->refresh($offer);
            if ($this->getParameter('app_active_log')['slack_active'] && $_ENV['APP_ENV'] !== 'dev') {
                try {
                    $this->slackService->addSlackLogToChannel('slack_funnel', 'Neuer Funnel '.$offer->getNumber());
                } catch (\Exception $exception) {
                }
            }

            return $this->render('recommendation/komplett.html.twig', [
                // 'form' => $form->createView(),
                'done' => true,
            ]);
        } elseif ($form->isSubmitted()) {
            return $this->render('recommendation/komplett.html.twig', [
                'form' => $form->createView(),
                'error' => 'Fehler beim Senden!',
                'errors' => $form->getErrors(),
            ]);
        }

        return $this->render('offer/types/komplett-recommendation.html.twig', [
            'inquiry' => $inquiry,
            'offer' => $inquiry->getOffer(),
            'category' => $inquiry->getOffer()->getCategory(),
            'form' => $form->createView(),
        ]);
    }

    #[Route(['de' => '/{id}/ruekrufbitte', 'en' => '/{id}/requestcall'], name: 'ajax_extern_request', methods: ['GET', 'POST'])]
    public function ruekruf(Request $request, Customer $customer): Response
    {
        // dd('Rückrufbitte von '.$customer->getFullNormalName().' ('.$customer->getPhone().') KundenNr.:'.$customer->getCustomerNumber());
        if ($this->getParameter('app_active_log')['slack_active'] && $_ENV['APP_ENV'] !== 'dev') {
            try {
                $this->slackService->addSlackLogToChannel('slack_funnel', 'Rückrufbitte von '.$customer->getFullNormalName().' ('.$customer->getPhone().') KundenNr.:'.$customer->getCustomerNumber());
            } catch (\Exception $exception) {
            }
        }

        return $this->redirect($this->getParameter('sf')['call_back']);
    }

    #[Route('/link-form', name: 'ajax_extern_form_link', methods: ['GET', 'POST'])]
    public function getLinkForm(Request $request): Response
    {
        $link = new Link();
        $form = $this->createForm(LinkExternType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() and $form->isValid()) {
            /** @var Link $newLink */
            $newLink = $form->getData();
            $linkexist = $this->em->getRepository(Link::class)->findOneBy([
                'email' => strtolower($newLink->getEmail()),
            ]);
            if ($linkexist instanceof Link) {
                return $this->render('offer/types/formlink.html.twig', [
                    'form' => $form->createView(),
                    'link' => $linkexist,
                    'done' => true,
                ]);
            }
            $newLink->setEmail(strtolower($newLink->getEmail()));
            $newLink->setNotice('Funnel in - '.$request->headers->get('host'));
            $newLink->setAmount(self::DEFAULT_CODE_AMOUNT);
            $newLink->setCreatedAt(new \DateTime());
            $newLink->setLink($this->getParameter('data')['name_short'].'-'.$this->getRandomString(6));
            $this->em->persist($newLink);
            $this->em->flush();
            if ($this->getParameter('app_active_log')['slack_active'] && $_ENV['APP_ENV'] !== 'dev') {
                $this->slackService->addSlackLogToChannel('slack_funnel', 'Ein Code wurde angefordert von '.strtolower($newLink->getEmail()));
            }

            return $this->render('offer/types/formlink.html.twig', [
                'form' => $form->createView(),
                'link' => $newLink,
                'done' => true,
            ]);
        }

        return $this->render('offer/types/formlink.html.twig', [
            'form' => $form->createView(),
            'link' => $link,
        ]);
    }

    #[Route('/contact-form', name: 'extern_form_contact', methods: ['GET', 'POST'])]
    public function getContactForm(Request $request): Response
    {
        $customer = new ContactPerson();
        $customer->setType('contact');
        $customer->setDone(false);
        $date = new \DateTime();
        $form = $this->createForm(ContactPersonType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() and $form->isValid()) {
            /** @var ContactPerson $newCustomer */
            $newCustomer = $form->getData();
            $newCustomer->setEmail(strtolower($newCustomer->getEmail()));
            // $newCustomer->setText('Kontaktanfrage '.$date->format('d.m.Y H:i'));
            $this->em->persist($newCustomer);
            $this->em->flush();

            return $this->render('offer/types/formcontact.html.twig', [
                'form' => $form->createView(),
                'type' => 'contact',
                'customer' => $newCustomer,
                'done' => true,
            ]);
        }

        return $this->render('offer/types/formcontact.html.twig', [
            'form' => $form->createView(),
            'type' => 'contact',
        ]);
    }

    #[Route('/link', name: 'ajax_extern_link', methods: ['GET', 'POST'])]
    public function saveExternLink(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->request->get('email')) {
            $linkOld = $entityManager->getRepository(Link::class)->findOneBy([
                'email' => $request->request->get('email'),
            ]);
            if ($linkOld instanceof Link) {
                return $this->json($linkOld->getLink());
            }
            $link = new Link();
            $link->setEmail($request->request->get('email'));
            $link->setFullname($request->request->get('fullname') ?? '');
            $link->setSex($request->request->get('sex') ?? '');
            $link->setNotice($request->headers->get('host'));
            try {
                $this->em->persist($link);
                $entityManager->flush();
            } catch (\Exception $exception) {
                // dd($exception);
                return $this->json(false);
            }

            return $this->json(true);
        }

        return $this->json(false);
    }

    #[Route(path: '/update/{funnel}', name: 'app_recommendation_update', methods: ['GET', 'POST'])]
    public function update(Request $request, OfferSubCategory $funnel): Response
    {
        $rsm = new ResultSetMapping();
        $this->em->createNativeQuery('update offer_sub_category set top_funnel=0', $rsm)->execute();
        if ($request->request->get('topfunnel')) {
            $funnel->setTopFunnel(true);
            $this->em->persist($funnel);
            $this->em->flush();
        }

        return $this->json('succes');
    }

    private function getInquire(Request $request): Inquiry
    {
        $inquiry = new Inquiry();
        $inquiry->setUser(null);
        $inquiry->setDate(new \DateTime());
        $inquiry->setCreateDate(new \DateTime());
        $offer = new Offer();
        $option = new OfferOption();
        $option->setOffer($offer);
        if ($request->get('ca')) {
            /** @var OfferCategory $category */
            $category = $this->em->getRepository(OfferCategory::class)->find($request->get('ca'));
            $subCategory = $this->em->getRepository(OfferSubCategory::class)->find($request->get('sca'));
        } else {
            /** @var OfferCategory $category */
            $subCategory = $this->em->getRepository(OfferSubCategory::class)->findOneBy(['topFunnel' => true]);
            $category = $subCategory->getCategory();
        }
        $offer->setCategory($category);
        $offer->setSubCategory($subCategory);
        // $option->setSolar(true);
        $offer->setOption($option);
        $inquiry->setOffer($offer);
        $offer->setInquiry($inquiry);

        return $inquiry;
    }
}
