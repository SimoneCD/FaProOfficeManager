<?php

namespace App\Controller;

use App\Entity\ProductOrder;
use App\Form\ProductOrderType;
use App\Repository\ProductOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product/order')]
class ProductOrderController extends AbstractController
{
    #[Route('/', name: 'app_product_order_index', methods: ['GET'])]
    public function index(ProductOrderRepository $productOrderRepository): Response
    {
        return $this->render('product_order/index.html.twig', [
            'product_orders' => $productOrderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductOrderRepository $productOrderRepository): Response
    {
        $productOrder = new ProductOrder();
        $form = $this->createForm(ProductOrderType::class, $productOrder);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productOrderRepository->save($productOrder, true);

            return $this->redirectToRoute('app_product_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product_order/new.html.twig', [
            'product_order' => $productOrder,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_order_show', methods: ['GET'])]
    public function show(ProductOrder $productOrder): Response
    {
        return $this->render('product_order/show.html.twig', [
            'product_order' => $productOrder,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProductOrder $productOrder, ProductOrderRepository $productOrderRepository): Response
    {
        $form = $this->createForm(ProductOrderType::class, $productOrder);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productOrderRepository->save($productOrder, true);

            return $this->redirectToRoute('app_product_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product_order/edit.html.twig', [
            'product_order' => $productOrder,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_order_delete', methods: ['POST'])]
    public function delete(Request $request, ProductOrder $productOrder, ProductOrderRepository $productOrderRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$productOrder->getId(), $request->request->get('_token'))) {
            $productOrderRepository->remove($productOrder, true);
        }

        return $this->redirectToRoute('app_product_order_index', [], Response::HTTP_SEE_OTHER);
    }
}
