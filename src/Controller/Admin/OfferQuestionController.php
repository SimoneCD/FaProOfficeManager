<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\KeyValueSubCategoryData;
use App\Entity\OfferQuestion;
use App\Entity\OfferSubCategory;
use App\Entity\QuestionArea;
use App\Form\OfferQuestionType;
use App\Form\OfferSubCategoryProductType;
use App\Repository\OfferQuestionsRepository;
use App\Repository\OfferSubCategoryRepository;
use App\Repository\QuestionAreaRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// #[Route(['de' => 'admin/fragebogen', 'en' => '/admin/questionnaire'])]
#[Route(['de' => '/admin/kategorien/x/frageboegen/{questionnaire}/fragen', 'en' => '/admin/categories/x/questionnaire/{questionnaire}/questions'])]
class OfferQuestionController extends BaseController
{
    #[Route('/', name: 'backend_subcategory_questions', methods: ['GET', 'POST'])]
    public function bySubcategory(Request $request, OfferQuestionsRepository $offerQuestionsRepository, OfferSubCategoryRepository $subCategoryRepository, QuestionAreaRepository $questionAreaRepository, OfferSubCategory $questionnaire): Response
    {
        // $questionnaireAreas = $questionnaire->getQuestionAreas();
        $form = $this->createForm(OfferSubCategoryProductType::class, $questionnaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var KeyValueSubCategoryData $data */
            foreach ($questionnaire->getKeyValueSubCategoryData() as $data) {
                $data->setSubCategory($questionnaire);
            }
            $subCategoryRepository->save($questionnaire, true);
        }
        $questions = $offerQuestionsRepository->findBy(['subCategory' => $questionnaire]);
        $questionsSorted = [];
        $post = $request->request->all();
        if (isset($post['questionArea'])) {
            $questions = $offerQuestionsRepository->findBy([
                'subCategory' => $questionnaire,
            ]);
            /* @var QuestionArea $a */

            $area = $post['offer_sub_category_group']['questionAreas'][$post['questionArea']];
            if (empty($area['name'] && !empty($area['questions']))) {
                $this->addFlash('danger', $this->translator->trans('o.error.groupName'));

                return $this->redirectToRoute('backend_subcategory_questions', ['questionnaire' => $questionnaire->getId()], Response::HTTP_SEE_OTHER);
            }

            $qa = $this->em->getRepository(QuestionArea::class)->findOneBy(['name' => $area['name'], 'subCategory' => $questionnaire]);

            if ($qa instanceof QuestionArea) {
                $questionnaire->addQuestionArea($qa);
            } else {
                $qa = new QuestionArea();
            }
            $qa->setSubCategory($questionnaire);
            $qa->setName($area['name']);
            $qa->setSort((float) $area['sort']);
            $qRepo = $this->em->getRepository(OfferQuestion::class);
            foreach ($area['questions'] as $qe) {
                $questi = $qRepo->find($qe);
                if ($questi instanceof OfferQuestion) {
                    $questi->setSubCategory($questionnaire);
                    $questi->setQuestionArea($qa);
                    $this->em->persist($questi);
                    $qa->addQuestion($questi);
                }
            }
            $this->em->persist($qa);
            $questionnaire->addQuestionArea($qa);
        }
        /** @var QuestionArea $qas */
        foreach ($questionnaire->getQuestionAreas() as $qas) {
            if (empty($qas->getName()) && $qas->getQuestions()->isEmpty()) {
                $questionnaire->removeQuestionArea($qas);
            } elseif (empty($qas->getSubCategory())) {
                $qas->setSubCategory($questionnaire);
            }
        }
        $subCategoryRepository->save($questionnaire, true);

        foreach ($questions as $k => $question) {
            $questionsSorted[$question->getQuestionArea() ? $question->getQuestionArea()->getSort() : 100][] = $question;
        }

        return $this->render('backend/offer_question/index.html.twig', [
            'questions' => $questionsSorted,
            'subCategory' => $questionnaire,
            // 'questionAreas' => $questionAreaRepository->findBy(['subCategory' => $questionnaire]),
            'form' => $form->createView(),
            'category' => $questionnaire->getCategory(),
        ]);
    }

    #[Route('/ajax-delete-area/{id}', name: 'backend_question_remove_group', methods: ['GET', 'POST'])]
    public function removeArea(QuestionAreaRepository $qRepository, OfferSubCategory $questionnaire, QuestionArea $qArea): Response
    {
        $qRepository->remove($qArea, true);

        return $this->redirectToRoute('backend_subcategory_questions', ['questionnaire' => $questionnaire->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/ajax-delete-areaquestion/{id}', name: 'backend_question_remove_group_question', methods: ['GET', 'POST'])]
    public function removeAreaQuestion(OfferQuestionsRepository $qRepository, OfferSubCategory $questionnaire, OfferQuestion $question): Response
    {
        $question->setQuestionArea(null);
        $qRepository->save($question, true);

        return $this->redirectToRoute('backend_subcategory_questions', ['questionnaire' => $questionnaire->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route(['de' => '/neu', 'en' => '/new'], name: 'backend_question_new', methods: ['GET', 'POST'])]
    public function new(Request $request, OfferQuestionsRepository $offerQuestionsRepository, OfferSubCategory $questionnaire): Response
    {
        try {
            $offerQuestion = new OfferQuestion();
            $offerQuestion->setSubCategory($questionnaire);
            $offerQuestion->setView(true);
            $form = $this->createForm(OfferQuestionType::class, $offerQuestion);
            $form->handleRequest($request);
        } catch (\Exception $exception) {
            $this->addFlash('danger', 'Bitte vor dem Anlegen von Fragen, der Angebotskategorie, eine Produktkategorie zuweisen!');

            return $this->redirectToRoute('backend_category_edit', ['id' => $questionnaire->getCategory()->getId()]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $offerQuestionsRepository->save($offerQuestion, true);

            return $this->redirectToRoute('backend_subcategory_questions', ['questionnaire' => $questionnaire->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backend/offer_question/new.html.twig', [
            'question' => $offerQuestion,
            'subCategory' => $questionnaire,
            'form' => $form,
        ]);
    }

    #[Route(['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'backend_question_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, OfferQuestionsRepository $offerQuestionsRepository, OfferSubCategory $questionnaire, OfferQuestion $question): Response
    {
        $form = $this->createForm(OfferQuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offerQuestionsRepository->save($question, true);

            return $this->redirectToRoute('backend_subcategory_questions', ['questionnaire' => $questionnaire->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('backend/offer_question/edit.html.twig', [
            'question' => $question,
            'subCategory' => $question->getSubCategory(),
            'form' => $form->createView(),
        ]);
    }

    #[Route(['de' => '/{id}/loeschen', 'en' => '/{id}/delete'], name: 'backend_question_delete', methods: ['POST'])]
    public function delete(Request $request, OfferSubCategory $questionnaire, OfferQuestion $offerQuestion, OfferQuestionsRepository $offerQuestionsRepository): Response
    {
        $subCategory = $offerQuestion->getSubCategory();
        if ($this->isCsrfTokenValid('delete'.$offerQuestion->getId(), $request->request->get('_token'))) {
            $offerQuestionsRepository->remove($offerQuestion, true);
        }

        return $this->redirectToRoute('backend_subcategory_questions', ['questionnaire' => $subCategory->getId()], Response::HTTP_SEE_OTHER);
    }
}
