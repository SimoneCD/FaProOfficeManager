<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajax/ajax-customer')]
class AjaxCustomerController extends AbstractController
{
    #[Route('/autocomplete', name: 'customer_ajax_autosearch', methods: ['POST'])]
    public function autocomplete(Request $request, CustomerRepository $customerRepository): Response
    {
        $searchTerm = json_decode($request->getContent());
        $g = [];
        if (!$this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            $g = $customerRepository->searchByExtern($this->getUser(), $searchTerm->q);
        } elseif (!empty($searchTerm->q)) {
            $g = $customerRepository->search($searchTerm->q);
        }

        return $this->render('customer/_autocomplete.html.twig', [
            'customers' => $g,
        ]);
    }
    #[Route('/autocompletejson', name: 'customer_ajax_jsonsearch', methods: ['POST'])]
    public function autocompleteJson(Request $request, CustomerRepository $customerRepository): Response
    {
        $searchTerm = json_decode($request->getContent());
        $g = [];
        if (!$this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            $g = $customerRepository->searchByJson($this->getUser(), $searchTerm->q);
        } else {
            $g = $customerRepository->searchJson($searchTerm->q);
        }
        return $this->json(['customers' => $g]);
    }

    /** todo refactor */
    #[Route(path: '/oauth2callback', name: 'oauth2callback', methods: ['GET'])]
    public function oauth2callback(): Response
    {
        return $this->json('#');
    }

    #[Route('/{id}/index', name: 'customer_ajax_index', methods: ['GET', 'POST'])]
    public function getCustomer(Customer $customer): Response
    {
        return $this->json($customer->toArray(), 200);
    }


}
