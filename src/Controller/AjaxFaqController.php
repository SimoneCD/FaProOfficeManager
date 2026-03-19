<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Faq;
use App\Form\FaqType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AjaxMessageController.
 */
#[Route(path: '/ajax/ajax-faq')]
class AjaxFaqController extends BaseController
{
    private const HELP_TEMPLATES = [
        'default' => [
            'template' => 'default',
            'title' => 'Keine FAQ vorhanden!',
            'text' => 'FAQ in Arbeit',
        ],
    ];

    #[Route(path: '/{id}/delete-video', name: 'ajax_faq_delvideo', methods: ['POST'])]
    public function delVideo(Faq $faq): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir').'/public/';

        if (file_exists($projectDir.$faq->getVideo())) {
            unlink($projectDir.$faq->getVideo());
        }
        $faq->setVideo(null);

        $this->em->persist($faq);
        $this->em->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/delete-image', name: 'ajax_faq_delimage', methods: ['POST'])]
    public function delImage(Faq $faq): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir').'/public/';

        if (file_exists($projectDir.$faq->getImage())) {
            unlink($projectDir.$faq->getImage());
        }
        $faq->setImage(null);

        $this->em->persist($faq);
        $this->em->flush();

        return $this->json(true);
    }

    #[Route(path: '/form/submit/{faqId}', name: 'ajax_faq_submit', methods: ['GET', 'POST'])]
    public function saveFaqForm(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader, int $faqId): Response
    {
        $faq = null;
        if (!empty($faqId)) {
            $faq = $this->em->getRepository(Faq::class)->find($faqId);
        }
        if (!$faq instanceof Faq) {
            $faq = new Faq();
            $faq->setId($faqId);
            $faq->setUser($this->getUser());
        }
        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            $result = ['success' => true];
            if (!empty($image)) {
                $name = 'FAQ_img_'.time();
                $imageFileName = $fileUploader->upload($image, 'faq', $name);
                $faq->setImage($imageFileName);
                $result['image'] = $imageFileName;
            }
            $video = $form->get('video')->getData();
            if (!empty($video)) {
                $name = 'FAQ_vid_'.time();
                $videoFileName = $fileUploader->upload($video, 'faq', $name);
                $faq->setVideo($videoFileName);
                $result['video'] = $videoFileName;
            }
            $entityManager->persist($faq);
            $entityManager->flush();

            return $this->json($result);
        }/* elseif ($form->isSubmitted()) {
            foreach ($form->getErrors() as $error) {
                dd($error->getMessage());
            }
        }*/

        return $this->json(false);
    }

    #[Route(path: '/form/{id}', name: 'ajax_faq', methods: ['GET', 'POST'])]
    public function getFaqForm(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader, int $id): Response
    {
        $faq = $this->em->getRepository(Faq::class)->find($id);
        if (!$faq instanceof Faq) {
            $faq = new Faq();
            $faq->setId($id);
            $faq->setUser($this->getUser());
        }
        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            if (!empty($image)) {
                $name = 'FAQ_'.str_replace(' ', '_', $faq->getTitle());
                $imageFileName = $fileUploader->upload($image, 'faq', $name);
                $faq->setImage($imageFileName);
            }
            $entityManager->persist($faq);
            $entityManager->flush();

            return $this->json(true);
        }
        if ($faq instanceof Faq) {
            if ($faq->getImage() && $faq->getVideo()) {
                return $this->render('faq/template.html.twig', ['template' => 'image-video', 'faq' => $faq, 'form' => $form]);
            } elseif ($faq->getImage()) {
                return $this->render('faq/template.html.twig', ['template' => 'image', 'faq' => $faq, 'form' => $form]);
            } elseif ($faq->getVideo()) {
                return $this->render('faq/template.html.twig', ['template' => 'video', 'faq' => $faq, 'form' => $form]);
            } else {
                return $this->render('faq/template.html.twig', ['template' => 'default', 'faq' => $faq, 'form' => $form]);
            }
        }

        return $this->render('faq/template.html.twig', self::HELP_TEMPLATES['default']);
    }
}
