<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Image;
use App\Entity\Inquiry;
use App\Entity\Offer;
use App\Entity\OfferCategory;
use App\Entity\OfferOption;
use App\Entity\ProjectTeam;
use App\Form\InquiryCustomerType;
use App\Form\InquiryType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

#[Route(path: ['de' => '/kundenanfrage', 'en' => 'customer-request'])]
#[IsGranted('ROLE_EMPLOYEE_EXTERN')]
class InquiryController extends BaseController
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

                return $this->render('inquiry/new.html.twig', [
                    'inquiry' => $inquiry,
                    'categories' => $this->em->getRepository(OfferCategory::class)->findAll(),
                    'customers' => $this->em->getRepository(Customer::class)->findAll(),
                    'form' => $form->createView(),
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
            // $this->em->flush();
            // $this->em->refresh($inquiry);

            $customer = $inquiry->getCustomer();
            $offer = new Offer();
            $option = new OfferOption();
            $option->setOffer($offer);
            $category = $request->request->get('category');
            if ($category) {
                /** @var OfferCategory $category */
                $category = $this->em->getRepository(OfferCategory::class)->find($category);
                $offer->setCategory($category);
                if ('Photovoltaik' === $category->getName()) {
                    $option->setSolar(true);
                }
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
            $offer->setNotice($inquiry->getNotice());
            $offer->setOfferDate(new \DateTime());
            $offer->setNumber('1.');
            $customer->setCustomerNumber('');
            $offer->setInquiry($inquiry);
            $inquiry->setOffer($offer);
            $customer->setPassword('');
            $this->em->persist($customer);
            $this->em->persist($offer);
            $team = $this->em->getRepository(ProjectTeam::class)->findOneBy([
                'isDefault' => true,
            ]);
            $offer->addProjectTeam($team);
            $this->em->persist($inquiry);
            $this->em->flush();

            $this->em->refresh($offer);

            $customer->setCustomerNumber(''.(Customer::CUSTOMER_START + $customer->getId()));
            $offer->setNumber($customer->getCustomerNumber().'.'.$this->getNextOfferNumber($customer));
            $this->em->persist($offer);
            $this->em->flush();
            $this->log(
                'new',
                'Neuen Kundenauftrag ('.$offer->getNumber().').',
                'Neuen Kundenauftrag '.$offer->getNumber().' aufgenommen.\n Kunde: '.$customer->getName().' '.$customer->getSurname().'\n Email: '.$customer->getEmail().'\n Telefon: '.$customer->getPhone().'',
                $customer,
                $offer,
            );

            return $this->redirectToRoute('offer_edit', ['id' => $offer->getId()]);
        }

        return $this->render('inquiry/new.html.twig', [
            'categories' => $this->em->getRepository(OfferCategory::class)->findAll(),
            'inquiry' => $inquiry,
            'customers' => $this->em->getRepository(Customer::class)->findAll(),
            'form' => $form->createView(),
        ]);
    }

    /** call from customer profile */
    #[Route(path: ['en' => '/from/{customer}', 'de' => '/von/{customer}'], name: 'inquiry_new_customer', methods: ['GET', 'POST'])]
    public function directNew(Request $request, Customer $customer): Response
    {
        $post = $request->request->all();
        $post = $post['inquiry'] ?? null;
        $inquiry = new Inquiry();
        $inquiry->setUser($this->getUser());
        $inquiry->setDate(new \DateTime());
        $inquiry->setCreateDate(new \DateTime());
        $inquiry->setCustomer($customer);
        $customer->addInquiry($inquiry);

        $form = $this->createForm(InquiryType::class, $inquiry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inquiry->setCustomer($customer);

            $this->em->persist($customer);
            $this->em->persist($inquiry);
            $this->em->flush();
            $this->em->refresh($inquiry);

            $customer = $inquiry->getCustomer();
            $offer = new Offer();
            $option = new OfferOption();
            $option->setOffer($offer);

            $offer->setCustomer($customer);
            $offer->setUser($this->getUser());
            $offer->setStatus('call');
            $offer->setStatusDate(new \DateTime());
            $offer->setTax(19);
            $offer->setPrice(0);
            $offer->setUseCase('privat');
            $offer->setNotice($inquiry->getNotice());
            $offer->setOfferDate(new \DateTime());
            $offer->setNumber(Customer::CUSTOMER_START + $customer->getId().'.'.$this->getNextOfferNumber($customer));
            $customer->setCustomerNumber(''.(Customer::CUSTOMER_START + $customer->getId()));
            $this->em->persist($customer);
            $offer->setInquiry($inquiry);
            $inquiry->setOffer($offer);
            if ($request->request->get('category')) {
                $offerCategory = $this->em->getRepository(OfferCategory::class)->find($request->request->get('category'));
                $offer->setCategory($offerCategory);
            }
            $this->em->persist($offer);
            $this->em->persist($inquiry);
            $this->em->flush();
            $this->em->refresh($offer);
            $this->log(
                'new',
                'Neuer Kostenvoranschlag für '.$customer->getFullName(),
                'Neuer Kostenvoranschlag ('.$offer->getNumber().') für '.$customer->getName().' '.$customer->getSurname().' ('.$customer->getEmail().') erstellt.',
                $customer,
                $offer,
            );
            if ($request->request->get('category')) {
                return $this->redirectToRoute('offer_edit', ['id' => $offer->getId()], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('offer_subcategory_index', ['id' => $offer->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('inquiry/direkt_new.html.twig', [
            'inquiry' => $inquiry,
            'customer' => $customer,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: ['en' => '/{id}/copy', 'de' => '/{id}/kopieren'], name: 'inquiry_copy', methods: ['GET', 'POST'])]
    public function copyToNew(Request $request, Inquiry $inquiry): Response
    {
        /** Mit Daten (1) oder ohne copieren */
        $copyState = ($request->query->get('data') ?? false);
        $customer = $inquiry->getCustomer();
        $offer = $inquiry->getOffer();
        $newInquiry = clone $inquiry;
        $newInquiry->setUser($this->getUser());
        $newInquiry->setRefererFrom('Copy');
        $newInquiry->setCreateDate(new \DateTime());
        $newOffer = $newInquiry->getOffer();
        $temp = [
            'doc' => [],
            'img' => [],
        ];
        if ($copyState) {
            foreach ($offer->getDocuments() as $doc) {
                $newDoc = clone $doc;
                $temp['doc'][] = [
                    'old' => getcwd().'/hd/app/uploads/'.$offer->getId().'/'.$doc->getFilename(),
                    'new' => $doc->getFilename(),
                ];
                $newDoc->setOffer($newOffer);
                $newOffer->addDocument($newDoc);
            }
            foreach ($offer->getImages() as $img) {
                /** @var Image $img */
                $newImg = clone $img;
                $temp['img'][] = [
                    'old' => getcwd().'/hd/app/uploads/'.$offer->getId().'/'.$img->getFilename(),
                    'new' => $img->getFilename(),
                ];
                $newOffer->addImage($newImg);
            }
        } else {
            $newOffer->setImages(new ArrayCollection());
            $newOffer->setDocuments(new ArrayCollection());
            $option = $offer->getOption();
            $newOption = $newOffer->getOption();
            $context = $option->getContext();
            if (isset($context['anrede'])) {
                unset($context['anrede']);
            }
            if (isset($context['offerEmailText'])) {
                unset($context['offerEmailText']);
            }
            $newOption->setContext($context);
        }
        $this->em->persist($newOffer);
        $this->em->persist($newInquiry);
        $this->em->flush();
        $this->em->refresh($newInquiry);
        $newOffer->setInquiry($newInquiry);
        $number = (Customer::CUSTOMER_START + $customer->getId()).'.'.$this->getNextOfferNumber($customer);
        $newOffer->setNumber($number);
        $this->em->persist($newOffer);
        $this->em->flush();
        $this->em->refresh($newOffer);
        if (count($temp['img']) or count($temp['doc'])) {
            mkdir('hd/app/uploads/'.$newOffer->getId());
        }
        foreach ($temp['img'] as $i) {
            copy($i['old'], 'hd/app/uploads/'.$newOffer->getId().'/'.$i['new']);
        }
        foreach ($temp['doc'] as $d) {
            copy($d['old'], 'hd/app/uploads/'.$newOffer->getId().'/'.$d['new']);
        }
        $this->log(
            'copy',
            'Angebot ('.$offer->getNumber().' -> '.$newOffer->getNumber().') kopiert',
            'Altes Angebot: '.$offer->getNumber().'\n Neues Angebot: '.$newOffer->getNumber().'\n Kunde: '.$customer->getName().' '.$customer->getSurname().' ('.$customer->getEmail().')',
            $customer,
            $newOffer,
        );

        return $this->redirectToRoute('offer_show', ['id' => $newOffer->getId()]);
    }
}
