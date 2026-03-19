<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Document;
use App\Entity\KeyValueSubCategoryData;
use App\Entity\OfferCategory;
use App\Entity\OfferSubCategory;
use App\Form\OfferSubCategoryType;
use App\Repository\OfferSubCategoryRepository;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// #[Route([
//    'de' => '/admin/katgorie-frageboegen',
//    'en' => '/admin/category-questionnaire',
// ])]
#[Route(['de' => '/admin/kategorien/{category}/frageboegen', 'en' => '/admin/categories/{category}/questionnaires'])]
class OfferSubCategoryController extends BaseController
{
    #[Route(['de' => '/', 'en' => '/'], name: 'backend_subcategory_index', methods: ['GET', 'POST'])]
    public function index(OfferCategory $category): Response
    {
        return $this->render('backend/offer_sub_category/index.html.twig', [
            'category' => $category,
            'subCategories' => $this->getSubCategories($category),
        ]);
    }

    #[Route(['de' => '/neu', 'en' => '/new'], name: 'backend_subcategory_new', methods: ['GET', 'POST'])]
    public function new(Request $request, OfferSubCategoryRepository $subCategoryRepository, FileUploader $fileUploader, OfferCategory $category): Response
    {
        $subCategory = new OfferSubCategory();
        $subCategory->setTopFunnel(false);
        $subCategory->setCategory($category);
        $form = $this->createForm(OfferSubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->setDefaultKeys($subCategory);

            $offerImage = $form->get('offerImage')->getData();
            if ($offerImage instanceof UploadedFile) {
                $fileName = $subCategory->getName().'-'.$subCategory->getId();
                $offerImageFileName = $fileUploader->upload($offerImage, 'offers', $fileName);
                $subCategory->setImage($offerImageFileName);
            }

            $subCategoryRepository->save($subCategory, true);
            $this->fa->delete('category'.$category->getId());
            $this->fa->delete('categories');
            $this->fa->delete('sub-category-'.$category->getId());

            $this->addFlash('success', $this->translator->trans('osc.quest.q.success.addQuestionnaire', ['%name%' => $subCategory->getName()]));

            return $this->redirectToRoute('backend_subcategory_questions', ['questionnaire' => $subCategory->getId()], Response::HTTP_SEE_OTHER);
        }
        return $this->render('backend/offer_sub_category/new.html.twig', [
            'category' => $category,
            'subCategory' => $subCategory,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/ajax-upload-image', name: 'backend_subcategory_upload_image', methods: ['GET', 'POST'])]
    public function uploadImage(Request $request, FileUploader $fileUploader, OfferCategory $category, OfferSubCategory $subCategory): JsonResponse
    {
        $file = $request->files->get('image');
        if ($file instanceof UploadedFile) {
            $fileName = $subCategory->getName().'-'.$subCategory->getId();
            $offerImageFileName = $fileUploader->upload($file, 'offers', $fileName);
            $subCategory->setImage($offerImageFileName);

            $this->fa->delete('category'.$subCategory->getCategory()->getId());
            $this->fa->delete('categories');
            $this->fa->delete('sub-category-'.$subCategory->getId());
            $this->em->persist($subCategory);
            $this->em->flush();

            return $this->json('succes');
        }

        return $this->json('error');
    }

    #[Route(['de' => '/{id}/{doc}/remove-request', 'en' => '/{id}/edit'], name: 'ajax-remove-request', methods: ['POST'])]
    public function removeRequest(OfferCategory $category, OfferSubCategory $subCategory, Document $doc, string $hdDir): Response
    {
        try {
            $this->em->remove($doc);
            unlink($hdDir.'/requests/'.$subCategory->getId().'/'.$doc->getFilename());
            $this->em->flush();
        } catch (\Exception $exception) {
            // dd($exception->getMessage());
            return $this->json(false);
        }

        return $this->json(true);
    }

    #[Route(['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'backend_subcategory_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, OfferCategory $category, OfferSubCategory $subCategory, FileUploader $fileUploader, OfferSubCategoryRepository $subCategoryRepository): Response
    {
        $form = $this->createForm(OfferSubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $subCategory = $form->getData();
            $offerImage = $form->get('offerImage')->getData();
            if ($offerImage instanceof UploadedFile) {
                $fileName = $subCategory->getName().'-'.$subCategory->getId();
                $offerImageFileName = $fileUploader->upload($offerImage, 'offer', $fileName);
                $subCategory->setImage($offerImageFileName);
            }
            $postSubCatData = $request->files->all()['offer_sub_category'];
            $offerRequests = $postSubCatData and !empty($postSubCatData['requests']) ? $postSubCatData['requests'] : null;

            if (!empty($offerRequests)) {
                /** @var OfferSubCategory $osc */
                $osc = $form->getData();
                $requests = $osc->getRequests();
                /** @var Document $document */
                foreach ($requests as $key => $document) {
                    if (!empty($offerRequests[$key]['file'])) {
                        /** @var UploadedFile $file */
                        $file = $offerRequests[$key]['file'];
                        $filename = $document->getFilename();
                        $document->setType('request');
                        $document->setTypeId($key);
                        $document->setOriginalName($request->request->all()['offer_sub_category']['requests'][$key]['filename']);
                        $document->setMimeType($file->getMimeType());
                        $document->setOfferSubCategory($osc);
                        $fileUploader->setUniqueId($subCategory->getId().'');
                        $offerRequestFileName = $fileUploader->upload($file, 'request', $filename);
                        if ($offerRequestFileName !== false) {
                            $document->setFilename($offerRequestFileName);
                            $this->em->persist($document);
                        }
                    }
                }
            }
            $subCategoryRepository->save($subCategory, true);
            $category = $subCategory->getCategory();
            $this->fa->delete('category'.$category->getId());
            $this->fa->delete('categories');
            $this->fa->delete('sub-category-'.$category->getId());

            $this->addFlash('success', 'OK - Speichern ist erledigt!');

            return $this->redirectToRoute('backend_subcategory_edit', ['category' => $category->getId(), 'id' => $subCategory->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('backend/offer_sub_category/edit.html.twig', [
            'category' => $subCategory->getCategory(),
            'subCategory' => $subCategory,
            'form' => $form->createView(),
        ]);
    }

    #[Route(['de' => '/{id}/loeschen', 'en' => '/{id}/delete'], name: 'backend_subcategory_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, OfferCategory $category, OfferSubCategory $subCategory, OfferSubCategoryRepository $subCategoryRepository): Response
    {
        $id = $subCategory->getCategory()->getId();
        if ($this->isCsrfTokenValid('delete-sub-category-'.$subCategory->getId(), $request->request->get('_token'))) {
            $subCategoryRepository->remove($subCategory, true);
            $this->fa->delete('category'.$id);
            $this->fa->delete('categories');
            $this->fa->delete('sub-category-'.$id);
        }

        return $this->redirectToRoute('backend_subcategory_index', ['category' => $id], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/kv-ajax/{keyValue}-delete', name: 'backend_subcategory_kv_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function deleteKeyValue(OfferCategory $category, OfferSubCategory $subCategory, KeyValueSubCategoryData $keyValue, OfferSubCategoryRepository $subCategoryRepository): Response
    {
        $this->em->remove($keyValue);
        $this->em->flush();

        return $this->json(true);
    }

    private function setDefaultKeys(OfferSubCategory $subCategory)
    {
        $kv = new KeyValueSubCategoryData();
        $kv->setKeyName('Auftrag:')
            ->setKeySort(1)
            ->setKeyValue('##offerName##');
        $subCategory->addKeyValueSubCategoryData($kv);
        $kv = new KeyValueSubCategoryData();
        $kv->setKeyName('LV-Nr.:')
            ->setKeySort(2)
            ->setKeyValue('##offerNumber##');
        $subCategory->addKeyValueSubCategoryData($kv);
        $kv = new KeyValueSubCategoryData();
        $kv->setKeyName('Bauvorhaben')
            ->setKeySort(3)
            ->setKeyValue('##address##');
        $subCategory->addKeyValueSubCategoryData($kv);
    }
}
