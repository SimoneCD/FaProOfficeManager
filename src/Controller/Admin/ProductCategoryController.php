<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\OfferCategory;
use App\Entity\ProductCategory;
use App\Form\ProductCategoryType;
use App\Repository\ProductCategoryRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['de' => '/admin/produkt-kategorien', 'en' => '/admin/product-categories'])]
class ProductCategoryController extends BaseController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/', name: 'backend_product_category_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('backend/product_category/index.html.twig', [
            'product_categories' => $this->getProductCategories(),
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(['de' => '/neu', 'en' => '/new'], name: 'backend_product_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductCategoryRepository $productCategoryRepository): Response
    {
        $productCategory = new ProductCategory();
        $form = $this->createForm(ProductCategoryType::class, $productCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productCategoryRepository->save($productCategory, true);
            $this->fa->delete('product-categories');

            return $this->redirectToRoute('backend_product_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backend/product_category/new.html.twig', [
            'product_category' => $productCategory,
            'form' => $form,
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'backend_product_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProductCategory $productCategory, ProductCategoryRepository $productCategoryRepository): Response
    {
        $form = $this->createForm(ProductCategoryType::class, $productCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productCategoryRepository->save($productCategory, true);
            $this->fa->delete('product-categories');

            return $this->redirectToRoute('backend_product_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backend/product_category/edit.html.twig', [
            'product_category' => $productCategory,
            'form' => $form,
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(['de' => '/{id}/loeschen', 'en' => '/{id}/delete'], name: 'backend_product_category_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, ProductCategory $productCategory, ProductCategoryRepository $productCategoryRepository, string $hdDir, TranslatorInterface $translator): Response
    {
        if ($this->isCsrfTokenValid('product-category-delete-'.$productCategory->getId(), $request->request->get('_token'))) {
            try {
                $categories = $this->em->getRepository(OfferCategory::class)->findByProductCategory($productCategory);
                $productCategoryRepository->remove($productCategory, $hdDir, true);
                if (count($categories)) {
                    $name = '';
                    foreach ($categories as $category) {
                        $name .= $category->getName().'<br>';
                    }
                    $this->addFlash('warning', $translator->trans('o.error.catDependencies').':<br>'.$name);
                }
                $this->fa->delete('product-categories');
            } catch (ForeignKeyConstraintViolationException $exception) {
                $this->addFlash('danger', $translator->trans('o.error.server').':<br>'.$exception->getMessage());

                return $this->redirectToRoute('backend_product_category_edit', ['id' => $productCategory->getId()], Response::HTTP_SEE_OTHER);
            }
        }
        $this->addFlash('success', $translator->trans('p.c.deleteSuccess'));

        return $this->redirectToRoute('backend_product_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
