<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Inquiry;
use App\Entity\Offer;
use App\Entity\OfferCategory;
use App\Entity\OfferOption;
use App\Entity\User;
use App\Form\InquiryCustomerType;
use App\Form\InquiryType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

#[Route(path: ['de' => '/admin/daa', 'en' => 'admin/daa'])]
class DaaController extends BaseController
{
    use TargetPathTrait;

    #[Route(path: ['en' => '/new', 'de' => '/neu'], name: 'inquiry_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $post = $request->request->all();
        $post = $post['inquiry'] ?? null;
        $inquiry = new Inquiry();
        $inquiry->setUser($this->getUser());
        $inquiry->setDate(new \DateTime());
        $inquiry->setCreateDate(new \DateTime());
        $customer = null;
        if (!empty($post['customer']) && !empty($post['customer']['id'])) {
            /** @var Customer $customer */
            $customer = $this->em->getRepository(Customer::class)->find($post['customer']['id']);
            $inquiry->setCustomer($customer);
            $customer->addInquiry($inquiry);
            $this->em->persist($customer);
            $form = $this->createForm(InquiryType::class, $inquiry);
            $form->handleRequest($request);
        } else {
            $form = $this->createForm(InquiryCustomerType::class, $inquiry);
            $form->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($customer instanceof Customer) {
                $inquiry->setCustomer($customer);
            }
            $customer = $inquiry->getCustomer();
            if (empty(trim($customer->getEmail())) && empty(trim($customer->getPhone()))) {
                $form->addError(new FormError('Email oder Telefonnummer müssen angegeben sein!'));
                $this->addFlash('danger', 'Email oder Telefonnummer müssen angegeben sein!');

                return $this->renderForm('inquiry/new.html.twig', [
                    'inquiry' => $inquiry,
                    'categories' => $this->em->getRepository(OfferCategory::class)->findAll(),
                    'customers' => $this->em->getRepository(Customer::class)->findAll(),
                    'form' => $form,
                ]);
            }
            if (empty(trim($customer->getName())) && empty(trim($customer->getSurName()))) {
                $form->addError(new FormError('Vor- oder Nachname dürfen nicht leer sein!'));
                $this->addFlash('danger', 'Vor- oder Nachname dürfen nicht leer sein!');

                return $this->render('inquiry/new.html.twig', [
                    'inquiry' => $inquiry,
                    'categories' => $this->em->getRepository(OfferCategory::class)->findAll(),
                    'customers' => $this->em->getRepository(Customer::class)->findAll(),
                    'form' => $form->createView(),
                ]);
            }
            $this->em->persist($customer);
            $this->em->persist($inquiry);
            $this->em->flush();
            $this->em->refresh($inquiry);

            $customer = $inquiry->getCustomer();
            $offer = new Offer();
            $option = new OfferOption();
            $option->setOffer($offer);
            $category = $request->request->get('category');
            if ($category) {
                /** @var OfferCategory $category */
                $category = $this->em->getRepository(OfferCategory::class)->find($category);
                $offer->setCategory($category);
            }
            $offer->setOption($option);
            $offer->setCustomer($customer);
            $offer->setUser($this->getUser());
            $offer->setStatus('call');
            $offer->setStatusDate(new \DateTime());
            $offer->setTax(19);
            $offer->setPrice(0);
            $offer->setContext(null);
            $offer->setUseCase('privat');
            $offer->setNotice('');
            $offer->setOfferDate(new \DateTime());
            $baseNumber = (Customer::CUSTOMER_START + $customer->getId());
            $offer->setNumber($baseNumber.'.'.$this->getNextOfferNumber($customer));
            $customer->setCustomerNumber(''.(Customer::CUSTOMER_START + $customer->getId()));
            $this->em->persist($customer);
            $offer->setInquiry($inquiry);
            $inquiry->setOffer($offer);
            $this->em->persist($offer);
            $this->em->persist($inquiry);
            $this->em->flush();
            $this->em->refresh($offer);

            return $this->redirectToRoute('offer_edit', ['id' => $offer->getId()]);
        }

        return $this->render('inquiry/new.html.twig', [
            'categories' => $this->em->getRepository(OfferCategory::class)->findAll(),
            'inquiry' => $inquiry,
            'customers' => $this->em->getRepository(Customer::class)->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: ['en' => '/import-daa', 'de' => '/import'], name: 'inquiry_import', methods: ['GET'])]
    public function daaImport(string $protocolDirectory): Response
    {
        $lastSaleId = $this->em->getRepository(Inquiry::class)->findLastSaleId() ?? 0;
        // $datum->modify('-1 week');
        $response = $this->client->request(
            'GET',
            sprintf('https://www.daa.net/api/v2/leads/bought?start_sale_id='.$lastSaleId),
            [
                'headers' => [
                    'accept' => 'application/json',
                    'Authorization' => 'Basic '.$_ENV['DA_BASIC_AUTH'],
                ],
            ]
        );

        $sales = $response->toArray();

        $date = new \DateTime();
        $date2 = new \DateTime();
        $date2->modify('-1 month');
        $i = 0;
        foreach ($sales as $key => $sale) {
            if ($sale['last_name'] === 'XXXXXXXX') {
                continue;
            }

            $saleDate = new \DateTime($sale['sale_date']);
            $customer = $this->em->getRepository(Customer::class)->findOneByPhoneOrMail($sale);

//            $bot = $this->em->getRepository(User::class)->find(11);
            $bot = $this->getUser();
            if (!$customer instanceof Customer) {
                $customer = new Customer();
                $customer->setSex($sale['title'] === 'Herr' ? 'mr' : ($sale['title'] === 'Frau' ? 'ms' : 'ma'));
                $customer->setAddress(str_replace('Auf Anfrage', '', (string) $sale['street']));
                $customer->setZip($sale['zipcode']);
                $customer->setCity($sale['city']);
                $customer->setCountry('DE');
                $customer->setName($sale['first_name']);
                $customer->setSurName($sale['last_name']);
                $customer->setEmail($sale['email']);
                $customer->setPhone($sale['phone']);
                $this->em->persist($customer);
            }

            if ($customer instanceof Customer) {
                $inquiry = new Inquiry();
                $inquiry->setStatus('call');
                $inquiry->setUser($bot);
                $inquiry->setLeadId($sale['lead_id']);
                $inquiry->setSaleId($sale['sale_id']);
                $inquiry->setCreateDate($saleDate);
                $inquiry->setDate($date);
                $inquiry->setCustomer($customer);
                $inquiry->setRefererFrom('DAA');
                $this->em->persist($customer);
                $this->em->flush();

                $notice = $this->getNotice($sale, $inquiry, $protocolDirectory);
                // dd($notice);
                $this->em->persist($inquiry);

                $offer = new Offer();
                $offerOptions = new OfferOption();
                $offer->setServiceDateFrom(new \DateTime());
                $offer->setServiceDateTo(new \DateTime());
                $offer->setCategory($this->em->getRepository(OfferCategory::class)->find(1));
                $offerOptions->setUser($bot);
                $offerOptions->setOffer($offer);
                $offerOptions->setInvoicePercent(50);
                $offer->setOption($offerOptions);
                $offer->setUser($bot);
                $offer->setStatus('call');
                $offer->setStatusDate($date);
                $offer->setOfferDate($date);
                $offer->setNotice($notice);
                $offer->setCustomer($customer);
                $offer->setStationAddress(str_replace('Auf Anfrage', '', (string) $sale['street']));
                $offer->setStationZip($sale['zipcode'].' '.$sale['city']);
                $offer->setInquiry($inquiry);
                $offer->setNumber(Customer::CUSTOMER_START + $customer->getId().'.'.$this->getNextOfferNumber($customer));
                $this->em->persist($offer);

                $inquiry->setOffer($offer);
                $customer->addOffers($offer);
                $customer->addInquiry($inquiry);
                $this->em->flush();
                $this->em->refresh($customer);
                $customer->setCustomerNumber(''.($customer->getId() + Customer::CUSTOMER_START));
                $this->em->persist($customer);
                $this->em->flush();
                ++$i;
            }
        }
        $this->addFlash('success', $this->translator->trans('iq.import.new', ['%count%' => $i]));

        return $this->redirectToRoute('booking_index');
    }

    #[Route(path: ['en' => '/import/{id}/stornieren', 'de' => '/import/{id}/storno'], name: 'inquiry_daa_storno', methods: ['GET', 'POST'])]
    public function daaStorno(Request $request, Inquiry $inquiry, string $hdDir): Response
    {
        $datum = new \DateTime();
        $customer = $inquiry->getCustomer();
        $offer = $inquiry->getOffer();
        $revison = $request->request->all()['revison'];

        if (!empty($revison['reson'])) {
//            $text = $this->translator->trans('iq.import.error.incorectData');
//            switch ($revison['reson']) {
//                case '2':
//                    $text = $this->translator->trans('iq.import.error.dontWant');
//                    break;
//                case '3':
//                    $text = $this->translator->trans('iq.import.error.time');
//                    break;
//            }
//            $html = $this->renderView('mail/daa/daa.html.twig', ['reson' => $text, 'inquiry' => $inquiry]);
//            $subject = $this->translator->trans('iq.import.title.storno').': '.$inquiry->getLeadId();
//
//            $m = 'kundenservice@daa.net';
//
//            /* kundenservice@daa.net */
//            $this->mailerService->sendMail('DAA-Team', $m, $subject, $html);
            $this->addFlash('danger', $this->translator->trans('o.stotnoDaaMessageError', ['%offerNumber%' => $offer->getNumber()]));
            $inquiry = $offer->getInquiry();
            $inquiry->setStornoDate($datum);
            $this->em->persist($inquiry);
            $this->em->flush();
        }
        /** @var array $revison */
        if (count($revison) && !empty($revison['do'])) {
            $this->handleDo($offer, $customer, $revison, $hdDir);
        }

        return $this->redirectToRoute('offer_subcategory_index', ['id' => $offer->getId()]);
    }

    #[Route(path: ['en' => '/{id}/reclamation', 'de' => '/{id}/stornieren'], name: 'inquiry_storno', methods: ['GET', 'POST'])]
    public function storno(Request $request, Inquiry $inquiry, string $hdDir): Response
    {
        new \DateTime();
        $customer = $inquiry->getCustomer();
        $offer = $inquiry->getOffer();
        $revison = $request->request->all()['revison'];
        if (!empty($revison['do'])) {
            $this->handleDo($offer, $customer, $revison, $hdDir);

            return $this->redirectToRoute('offer_subcategory_index', ['id' => $inquiry->getOffer()->getId()]);
        }

        return $this->redirectToRoute('offer_subcategory_index', ['id' => $inquiry->getOffer()->getId()]);
    }

    #[Route(path: ['en' => '/{id}/copy', 'de' => '/{id}/kopieren'], name: 'inquiry_copy', methods: ['GET', 'POST'])]
    public function copyToNew(Inquiry $inquiry): Response
    {
        $customer = $inquiry->getCustomer();
        $off = $inquiry->getOffer();
        $count = count($customer->getAllOffers()) + 1;
        if ($off->getNumber() === ((Customer::CUSTOMER_START + $customer->getId()).'.'.($count + 1))) {
            ++$count;
        }
        $newInquiry = clone $inquiry;
        $newInquiry->setUser($this->getUser());
        $newInquiry->setRefererFrom('Copy');
        $newInquiry->setCreateDate(new \DateTime());

        $this->em->persist($newInquiry->getOffer());
        $this->em->persist($newInquiry);
        $this->em->flush();
        $this->em->refresh($newInquiry);
        $offer = $newInquiry->getOffer();
        $offer->setInquiry($newInquiry);
        $number = (Customer::CUSTOMER_START + $customer->getId()).'.'.$count;
        $offer->setNumber($number);
        $this->em->persist($offer);
        $this->em->flush();

        return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
    }

    private function handleDo(Offer $offer, Customer $customer, ?array $revison, string $hdDir): void
    {
        $option = $offer->getOption();
        switch ($revison['do']) {
            case '1':
                $option->setBlendOut(true);
                $this->em->persist($option);
                $this->em->flush();
                break;
            case '2':
                $this->deleteOffer($offer, $hdDir);
                break;
            case '3':
                $this->deleteCustomer($customer);
                break;
        }
    }

    private function getNotice(array $sale, Inquiry $inquiry, string $protocolDirectory): string
    {
        $notice = 'Import Information:'."\n\r";
        if (!empty($sale['infos'])) {
            foreach ($sale['infos'] as $nk => $info) {
                $notice .= $nk.' = '.$info."\n";
            }
        }
        if (!empty($sale['images'])) {
            $i = 0;
            foreach ($sale['images'] as $im) {
                // $img = file_get_contents($im['url']);
                // $path = $protocolDirectory.'/'.$inquiry->getCustomer()->getId().'/Kundenbild_'.$i.'_'.date('d-m-Y_H').'.jpg';
                // @file_put_contents($path, $img);
                $notice = $notice.' '."\n".'image '.($i + 1).': '.$im['url'];
                ++$i;
            }
        }

        return $notice;
    }
}
