<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\OfferAnswers;
use App\Entity\Settings;
use App\Form\SettingsType;
use App\Repository\OfferAnswersRepository;
use App\Repository\SettingsRepository;
use App\Service\FileUploader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(['de' => '/admin/einstellungen', 'en' => '/admin/settings'])]
class SettingsController extends BaseController
{
    #[Route(['de' => '/', 'en' => '/'], name: 'backend_settings_index', methods: ['GET'])]
    public function questionAnswers(SettingsRepository $settingsRepository): Response
    {
        return $this->render('backend/settings/index.html.twig', []);
    }

    #[Route(['de' => 'neu', 'en' => 'new'], name: 'backend_settings_new')]
    public function new(Request $request, FileUploader $fileUploader, string $hdDir): Response
    {
        $settings = new Settings();
        $form = $this->createForm(SettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logo')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $fileUploader->getSlugger()->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $logoFile->move(
                        $this->getParameter($hdDir.'logo'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $settings->setLogo($newFilename);
            }

            // ... persist the $product variable or any other work

            return $this->redirectToRoute('settings_edit', ['id' => $settings->getId()]);
        }

        return $this->render('settings/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'backend_settings_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Settings $settings): Response
    {
        $form = $this->createForm(SettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($settings);
            $this->em->flush();

            return $this->redirectToRoute('backend_settings_index');
        }

        return $this->render('backend/offer_answers/edit.html.twig', [
            'settings' => $settings,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/ajax-upload-image', name: 'backend_settings_upload_image', methods: ['GET', 'POST'])]
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

    #[Route(['de' => '/{id}/loeschen', 'en' => '/{id}/delete'], name: 'backend_settings_delete', methods: ['POST'])]
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
}
