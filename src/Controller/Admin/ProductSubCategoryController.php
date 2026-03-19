<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\OfferSubCategory;
use App\Entity\ProductCategory;
use App\Entity\ProductSubCategory;
use App\Form\ProductSubCategoryType;
use App\Repository\ProductSubCategoryRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: ['de' => '/admin/produkt-kategorie/{category}/unterkategorien', 'en' => '/admin/product-category/{category}/sub-categories'])]
#[IsGranted('ROLE_EMPLOYEE_SERVICE')]
class ProductSubCategoryController extends BaseController
{
    #[Route('/', name: 'backend_product_subcategory_index', methods: ['GET'])]
    public function index(ProductSubCategoryRepository $productSubCategoryRepository, ProductCategory $category): Response
    {
        return $this->render('backend/product_sub_category/index.html.twig', [
            'product_sub_categories' => $productSubCategoryRepository->findBy([
                'category' => $category,
            ]),
            'category' => $category,
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(['de' => '/neu', 'en' => '/new'], name: 'backend_product_subcategory_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductCategory $category, ProductSubCategoryRepository $productSubCategoryRepository): Response
    {
        $productSubCategory = new ProductSubCategory();
        $productSubCategory->setCategory($category);
        $form = $this->createForm(ProductSubCategoryType::class, $productSubCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productSubCategoryRepository->save($productSubCategory, true);
            $this->fa->delete('product-categories');

            return $this->redirectToRoute('backend_product_subcategory_index', ['category' => $productSubCategory->getCategory()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backend/product_sub_category/new.html.twig', [
            'product_sub_category' => $productSubCategory,
            'category' => $category,
            'form' => $form,
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'backend_product_subcategory_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProductCategory $category, ProductSubCategory $productSubCategory, ProductSubCategoryRepository $productSubCategoryRepository): Response
    {
        $form = $this->createForm(ProductSubCategoryType::class, $productSubCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productSubCategoryRepository->save($productSubCategory, true);
            $this->fa->delete('product-categories');

            return $this->redirectToRoute('backend_product_subcategory_index', ['category' => $productSubCategory->getCategory()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backend/product_sub_category/edit.html.twig', [
            'product_sub_category' => $productSubCategory,
            'category' => $productSubCategory->getCategory(),
            'form' => $form,
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(['de' => '/{id}/loeschen', 'en' => '/{id}/delete'], name: 'backend_product_subcategory_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, ProductCategory $category, ProductSubCategory $productSubCategory, ProductSubCategoryRepository $productSubCategoryRepository): Response
    {
        $categoryId = $productSubCategory->getCategory()->getId();
        if ($this->isCsrfTokenValid('delete-product-sub-category-'.$productSubCategory->getId(), $request->request->get('_token'))) {
            $questionaires = $this->em->getRepository(OfferSubCategory::class)->findBy([
                'productSubCategory' => $productSubCategory,
            ]);
            $nameT = [];
            foreach ($questionaires as $questionaire) {
                $nameT[] = $questionaire->getName();
            }
            try {
                $productSubCategoryRepository->remove($productSubCategory, true);
                $this->fa->delete('product-categories');
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'Folgende Fragebögen müssen erst enkoppelt oder gelöscht werden: <strong>'.implode(',', $nameT).'</strong>');

                return $this->redirectToRoute('backend_product_subcategory_edit', ['category' => $productSubCategory->getCategory()->getId(), 'id' => $productSubCategory->getId()]);
            }
        }

        return $this->redirectToRoute('backend_product_subcategory_index', ['category' => $categoryId], Response::HTTP_SEE_OTHER);
    }
}
