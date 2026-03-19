<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ActionLog;
use App\Entity\ContactPerson;
use App\Entity\Customer;
use App\Entity\ProjectTeam;
use App\Entity\User;
use App\Form\ActionLogType;
use App\Form\CustomerType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: [
    'en' => '/admin/customers',
    'de' => '/admin/kunden',
])]
#[IsGranted('ROLE_EMPLOYEE_SERVICE')]
class CustomerController extends BaseController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route(path: '/', name: 'customer_index', methods: ['GET'])]
    public function index(): Response
    {
        //        if(!$this->isGranted('ROLE_ADMIN') && ($url = $this->checkUserTime($request)) !== false) {
        //            return new RedirectResponse($url);
        //        }
        return $this->render('customer/index.html.twig', [
            'customers' => $this->getCustomers(),
            'users' => $this->getServiceUsers(),
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(path: '/kontakt-anfragen', name: 'customer_contact_index', methods: ['GET'])]
    public function contact(EntityManagerInterface $entityManager): Response
    {
        //        if(!$this->isGranted('ROLE_ADMIN') && ($url = $this->checkUserTime($request)) !== false) {
        //            return new RedirectResponse($url);
        //        }
        return $this->render('customer/contact.html.twig', [
            'customers' => $entityManager->getRepository(ContactPerson::class)->findAll(),
            'users' => $this->getServiceUsers(),
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(path: '/kontakt-update/{id}', name: 'ajax_contact_update', methods: ['POST'])]
    public function contactUpdate(Request $request, EntityManagerInterface $entityManager, ContactPerson $contactPerson): Response
    {
        $checked = $request->request->get('checked');
        if ('1' === $checked) {
            $contactPerson->setDone(true);
        } else {
            $contactPerson->setDone(false);
        }
        $entityManager->persist($contactPerson);
        $entityManager->flush();

        return $this->json(true);
    }

    #[Route(path: [
        'en' => '/new-customer',
        'de' => '/neuer-kunde',
    ], name: 'customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $customer = new Customer();
        $customer->setCustomerNumber($this->getParameter('customer_start').'');
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer->setPassword('');
            $this->em->persist($customer);
            $this->em->flush();
            $this->em->refresh($customer);
            $customer->setCustomerNumber(''.(Customer::CUSTOMER_START + $customer->getId()));
            $this->em->persist($customer);
            $this->em->flush();

            return $this->redirectToRoute('customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(path: [
        'en' => '/{id}/edit',
        'de' => '/{id}/bearbeiten',
    ], name: 'customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer): Response
    {
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->em->persist($data);
            $this->em->flush();
            $this->addFlash('success', 'Der Kunde wurde gespeichert.');
            $this->fa->delete('customer'.$customer->getId());

            return $this->redirectToRoute('customer_edit', ['id' => $customer->getId()], Response::HTTP_SEE_OTHER);
        }
        $users = $this->em->getRepository(User::class)->findAll();
        $form2 = $this->createForm(ActionLogType::class, new ActionLog());
        return $this->render('customer/edit.html.twig', [
            'customer' => $customer,
            'teams' => $this->em->getRepository(ProjectTeam::class)->findAll(),
            'form' => $form->createView(),
            'offers' => array_reverse($customer->getOffers()->toArray()),
            'archiveOffers' => array_reverse($customer->getArchiveOffers()->toArray()),
            'users' => $users,
            'ActionLog' => ActionLog::TYPE_CHOICES,
            'form2' => $form2,
        ]);
    }

    #[Route(path: [
        'en' => '/{id}/delete',
        'de' => '/{id}/loeschen',
    ], name: 'customer_delete', methods: ['POST'])]
    public function delete(Request $request, Customer $customer): Response
    {
        if ($this->isCsrfTokenValid('delete'.$customer->getId(), $request->request->get('_token')) && 277 !== $customer->getId()) {
            $this->log(
                'delete',
                'Kunde ('.$customer->getFullName().') gelöscht.',
                'Kunde ('.$customer->getFullName().') wurde von '.$this->getUser()->getName().' gelöscht.\n ID:'.$customer->getId().'\n Name:'.$customer->getName().' '.$customer->getSurname().'\n Email:'.$customer->getEmail().'\n Telefonnummer:'.$customer->getPhone(),
                null
            );
            $this->deleteCustomer($customer);
        }

        return $this->redirectToRoute('customer_index');
    }

    #[Route(path: [
        'en' => '/{id}/contact-delete',
        'de' => '/{id}/contact-loeschen',
    ], name: 'customer_contact_delete', methods: ['POST'])]
    public function deleteContact(Request $request, ContactPerson $customer): Response
    {
        if ($this->isCsrfTokenValid('delete'.$customer->getId(), $request->request->get('_token')) && 277 !== $customer->getId()) {
            $this->log(
                'delete',
                'Kunde ('.$customer->getSex().' '.$customer->getName().' '.$customer->getSurname().') wurde gelöscht',
                'Kunde:'.$customer->getId().' '.$customer->getName().' '.$customer->getSurname().' ('.$customer->getEmail().')',
                null
            );
            // $this->em->remove($customer);
            // $this->em->flush();
            $this->em->remove($customer);
            $this->em->flush();
        }

        return $this->redirectToRoute('customer_contact_index');
    }
}
