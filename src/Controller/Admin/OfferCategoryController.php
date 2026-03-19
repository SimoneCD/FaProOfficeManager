<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\OfferCategory;
use App\Form\OfferCategoryType;
use App\Repository\OfferCategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(['de' => '/admin/kategorien', 'en' => '/admin/categories'])]
#[IsGranted('ROLE_ADMIN')]
class OfferCategoryController extends BaseController
{
    #[Route('/', name: 'backend_category_index', methods: ['GET'])]
    public function index(OfferCategoryRepository $offerCategoryRepository): Response
    {
        return $this->render('backend/offer_category/index.html.twig', [
            'categories' => $this->getCategories(),
        ]);
    }

    #[Route(['de' => '/neu', 'en' => '/create'], name: 'backend_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, OfferCategoryRepository $offerCategoryRepository): Response
    {
        $offerCategory = new OfferCategory();
        $form = $this->createForm(OfferCategoryType::class, $offerCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offerCategoryRepository->save($offerCategory, true);
            $this->fa->delete('categories');

            return $this->redirectToRoute('backend_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backend/offer_category/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'backend_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, OfferCategory $offerCategory, OfferCategoryRepository $offerCategoryRepository): Response
    {
        $form = $this->createForm(OfferCategoryType::class, $offerCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offerCategoryRepository->save($offerCategory, true);
            $this->fa->delete('categories');
            $this->fa->delete('category'.$offerCategory->getId());

            return $this->redirectToRoute('backend_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backend/offer_category/edit.html.twig', [
            'category' => $offerCategory,
            'form' => $form,
        ]);
    }

    #[Route(['de' => '/{id}/loeschen', 'en' => '/{id}/delete'], name: 'backend_category_delete', methods: ['POST'])]
    public function delete(Request $request, OfferCategory $offerCategory, OfferCategoryRepository $offerCategoryRepository): Response
    {
        if ($this->isCsrfTokenValid('delete_category_'.$offerCategory->getId(), $request->request->get('_token'))) {
            try {
                $offerCategoryRepository->remove($offerCategory, true);
                $this->fa->delete('categories');
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'Die Kategorie hat Abhängigkeiten, die zuerst gelöst werden müssen!');
            }
        }

        return $this->redirectToRoute('backend_category_index', ['id' => $offerCategory->getId()], Response::HTTP_SEE_OTHER);
    }
}
