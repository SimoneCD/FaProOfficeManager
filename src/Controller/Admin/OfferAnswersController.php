<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\OfferAnswers;
use App\Entity\OfferQuestion;
use App\Entity\Product;
use App\Form\OfferAnswersType;
use App\Repository\OfferAnswersRepository;
use App\Repository\OfferQuestionsRepository;
use App\Service\FileUploader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(['de' => '/admin/kategorien/x/frageboegen/x/fragen/{question}/antworten', 'en' => '/admin/categories/x/questionnaire/x/questions/{question}/answers'])]
class OfferAnswersController extends BaseController
{
    #[Route(['de' => '/', 'en' => '/'], name: 'backend_answers_index', methods: ['GET'])]
    public function questionAnswers(OfferAnswersRepository $offerAnswersRepository, OfferQuestion $question): Response
    {
        return $this->render('backend/offer_answers/index.html.twig', [
            'answers' => $offerAnswersRepository->findBy([
                'question' => $question,
            ]),
            'question' => $question,
            'subCategory' => $question->getSubCategory(),
        ]);
    }

    #[Route(['de' => '/neu', 'en' => '/new'], name: 'backend_answers_new', methods: ['GET', 'POST'])]
    public function newByQuestion(Request $request, OfferAnswersRepository $offerAnswersRepository, OfferQuestionsRepository $questionsRepository, OfferQuestion $question): Response
    {
        $offerAnswer = new OfferAnswers();
        $offerAnswer->setQuestion($question);
        $form = $this->createForm(OfferAnswersType::class, $offerAnswer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offerAnswersRepository->save($offerAnswer, true);
            foreach ($offerAnswer->getDependencies() as $quest) {
                $q = $questionsRepository->find($quest);
                /* @var OfferQuestion $q */
                $q->setView(false);
                $questionsRepository->save($q, true);
            }

            return $this->redirectToRoute('backend_answers_index', ['question' => $question->getId()], Response::HTTP_SEE_OTHER);
        }
        $subCategory = $question->getSubCategory();
        $products = $this->em->getRepository(Product::class)->findBy([
            'productCategory' => $subCategory->getCategory()->getProductCategory(),
        ]);

        return $this->renderForm('backend/offer_answers/new.html.twig', [
            'answer' => $offerAnswer,
            'subCategory' => $question->getSubCategory(),
            'questionProducts' => $products,
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route(['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'backend_answers_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, OfferQuestion $question, OfferAnswers $offerAnswer, OfferAnswersRepository $offerAnswersRepository, OfferQuestionsRepository $questionsRepository): Response
    {
        $form = $this->createForm(OfferAnswersType::class, $offerAnswer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offerAnswersRepository->save($offerAnswer, true);
            foreach ($offerAnswer->getDependencies() as $questId) {
                $q = $questionsRepository->find($questId);
                if ($q instanceof OfferQuestion) {
                    $q->setView(true);
                    $questionsRepository->save($q);
                }
            }

            return $this->redirectToRoute('backend_answers_index', ['question' => $offerAnswer->getQuestion()->getId()], Response::HTTP_SEE_OTHER);
        }
        $subCategory = $offerAnswer->getQuestion()->getSubCategory();
        $products = $this->em->getRepository(Product::class)->findBy([
           'productSubCategory' => $subCategory->getProductSubCategory(),
        ]);

        return $this->renderForm('backend/offer_answers/edit.html.twig', [
            'answer' => $offerAnswer,
            'subCategory' => $subCategory,
            'questionProducts' => $products,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/ajax/upload-image', name: 'backend_offer_answer_upload_image', methods: ['GET', 'POST'])]
    public function uploadImage(Request $request, FileUploader $fileUploader, OfferAnswers $answer): JsonResponse
    {
        $file = $request->files->get('image');
        if ($file instanceof UploadedFile) {
            $name = $answer->getQuestion()->getId().'-'.substr($answer->getQuestion()->getName(), 0, 10).'_'.$answer->getId();
            $answerImageFileName = $fileUploader->upload($file, 'answers', $name);
            $answer->setImage($answerImageFileName);
            $this->em->persist($answer);
            $this->em->flush();

            return $this->json('success');
        }

        return $this->json('error');
    }

    #[Route(['de' => '/{id}/loeschen', 'en' => '/{id}/delete'], name: 'backend_answers_delete', methods: ['POST'])]
    public function delete(Request $request, OfferAnswers $offerAnswer, OfferAnswersRepository $offerAnswersRepository, string $hdDir): Response
    {
        $question = $offerAnswer->getQuestion();
        if ($this->isCsrfTokenValid('delete'.$offerAnswer->getId(), $request->request->get('_token'))) {
            $image = $offerAnswer->getImage();
            $filesystem = new Filesystem();
            if (!empty($image) && $filesystem->exists($hdDir.'/offers/answers/'.$image)) {
                $filesystem->remove($hdDir.'/offers/answers/'.$image);
            }
            $offerAnswersRepository->remove($offerAnswer, true);
            $this->addFlash('success', $this->translator->trans('p.deleteSuccess'));
        }

        return $this->redirectToRoute('backend_answers_index', ['question' => $question->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route(['de' => '/{id}/ajax-sort-update', 'en' => '/{id}/ajax-sort-update'], name: 'backend_answers_ajax_sort', methods: ['POST'])]
    public function sort(Request $request, OfferQuestion $question, OfferAnswers $offerAnswer, OfferAnswersRepository $offerAnswersRepository): Response
    {
        $sort = $request->getContent();
        $offerAnswer->setSort(floatval($sort));
        $offerAnswersRepository->save($offerAnswer, true);

        return $this->json('success');
    }
}
