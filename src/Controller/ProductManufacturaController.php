<?php

namespace App\Controller;

use App\Entity\ProductManufactura;
use App\Form\ProductManufacturaType;
use App\Repository\ProductManufacturaRepository;
use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product-manufactura')]
class ProductManufacturaController extends AbstractController
{
    #[Route('/', name: 'app_product-manufactura_index', methods: ['GET', 'POST'])]
    public function index(ProductManufacturaRepository $productManufacturaRepository): Response
    {
        return $this->render('product_manufactura/index.html.twig', [
            'productManufacturas' => $productManufacturaRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product-manufactura_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductManufacturaRepository $productManufacturaRepository, FileUploader $fileUploader): Response
    {
        $productManufactura = new ProductManufactura();
        $form = $this->createForm(ProductManufacturaType::class, $productManufactura);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logo = $form->get('manufacturaLogo')->getData();
            if (!empty($logo)) {
                $name = 'logo_'.$productManufactura->getId();
                $productImageFileName = $fileUploader->upload($logo, 'product-manufacturer', $name);
                $productManufactura->setLogo($productImageFileName);
            }
            $productManufacturaRepository->save($productManufactura, true);

            return $this->redirectToRoute('app_product-manufactura_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product_manufactura/new.html.twig', [
            'productManufactura' => $productManufactura,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product-manufactura_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProductManufactura $productManufactura, ProductManufacturaRepository $productManufacturaRepository, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(ProductManufacturaType::class, $productManufactura);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logo = $form->get('manufacturaLogo')->getData();
            if (!empty($logo)) {
                $name = 'logo_'.$productManufactura->getId();
                $productImageFileName = $fileUploader->upload($logo, 'product-manufacturer', $name);
                $productManufactura->setLogo($productImageFileName);
            }
            $productManufacturaRepository->save($productManufactura, true);

            return $this->redirectToRoute('app_product-manufactura_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product_manufactura/edit.html.twig', [
            'productManufactura' => $productManufactura,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_product-manufactura_delete', methods: ['POST'])]
    public function delete(Request $request, ProductManufactura $productManufactura, ProductManufacturaRepository $productManufacturaRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$productManufactura->getId(), $request->request->get('_token'))) {
            $productManufacturaRepository->remove($productManufactura, true);
        }

        return $this->redirectToRoute('app_product-manufactura_index', [], Response::HTTP_SEE_OTHER);
    }
}
