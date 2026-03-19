<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\ProductSubCategory;
use App\Service\FileUploader;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AjaxController.
 */
#[Route(path: '/ajax/ajax-product')]
class AjaxProductController extends AbstractController
{
    private readonly ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    #[Route(path: '/{id}', name: 'ajax_product', methods: ['GET', 'POST'])]
    public function getProduct(Product $product): JsonResponse
    {
        return $this->json($product->toArray());
    }

    #[Route(path: '/{id}/update/number', name: 'ajax_product_update_number', methods: ['POST'])]
    public function updateProductNumber(Request $request, Product $product): JsonResponse
    {
        $product->setProductNumber($request->request->get('productNumber'));
        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/update/funnel', name: 'app_product_funnel_update', methods: ['POST'])]
    public function updateProductFunnel(Request $request, Product $product): JsonResponse
    {
        $product->setFunnel(!empty($request->request->get('funnel')));
        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/update/einheit', name: 'ajax_product_update_einheit', methods: ['POST'])]
    public function updateProductEinheit(Request $request, Product $product): JsonResponse
    {
        $product->setEinheit($request->request->get('productEinheit'));
        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();

        return $this->json(true);
    }
    #[Route(path: '/{id}/update/kw', name: 'ajax_product_update_kw', methods: ['POST'])]
    public function updateKw(Request $request, Product $product): JsonResponse
    {
        $product->setKw($request->request->get('kw'));
        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/update/ekPrice', name: 'ajax_product_update_ekprice', methods: ['POST'])]
    public function updateProductEkPrice(Request $request, Product $product): JsonResponse
    {
        $price = floatval(str_replace(',', '.', $request->request->get('productEkPrice')));
        $product->setEkPrice(round($price, 2));
        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/update/price', name: 'ajax_product_update_price', methods: ['POST'])]
    public function updateProductPrice(Request $request, Product $product): JsonResponse
    {
        $price = floatval(str_replace(',', '.', $request->request->get('price')));
        $product->setPrice(round($price, 2));
        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/update/stock', name: 'ajax_product_update_stock', methods: ['POST'])]
    public function updateProductStock(Request $request, Product $product): JsonResponse
    {
        $product->setStock(floatval($request->request->get('stock')));
        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/update/instock', name: 'ajax_product_update_instock', methods: ['POST'])]
    public function updateProductInStock(Request $request, Product $product): JsonResponse
    {
        $product->setInStock((int) $request->request->get('inStock'));
        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/update/name', name: 'ajax_product_update_name', methods: ['POST'])]
    public function updateName(Request $request, Product $product): JsonResponse
    {
        $product->setName($request->request->get('name'));
        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/update/shoplink', name: 'ajax_product_update_shoplink', methods: ['POST'])]
    public function updateProductshoplink(Request $request, Product $product): JsonResponse
    {
        $link = $request->request->get('shopLink');
        if (strstr($link, 'http') && strstr($link, '://') && strstr($link, '.') && strlen($link) > 15) {
            $product->setShopLink($request->request->get('shopLink'));
            $this->managerRegistry->getManager()->persist($product);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['success' => true, 'link' => $product->getShopLink()]);
        }

        return $this->json(['success' => false, 'link' => $product->getShopLink()]);
    }

    #[Route(path: '/{id}/update/valueName', name: 'ajax_product_update_value_name', methods: ['POST'])]
    public function updateProductValueName(Request $request, Product $product): JsonResponse
    {
        $product->setValueName($request->request->get('productValueName'));
        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/update/subCategory/{subCategory}', name: 'ajax_product_update_sub_category', methods: ['POST'])]
    public function productSubCategoryUpdate(Product $product, ProductSubCategory $subCategory): JsonResponse
    {
        $product->setProductSubCategory($subCategory);
        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/upload-document', name: 'ajax_upload_product_document', methods: ['GET', 'POST'])]
    public function uploadDocument(Request $request, FileUploader $fileUploader, Product $product): Response
    {
        $file = $request->files->get('file');
        if ($file instanceof UploadedFile) {
            $name = $fileUploader->upload($file, 'product-documents', $product->getId().'_doc_'.count($product->getCertificats()));
            $document = new Document();
            $document->setOffer(null);
            $document->setOriginalName($file->getClientOriginalName());
            $document->setMimeType($file->getClientMimeType());
            $document->setFilename($name);
            $document->setUpdated(new \DateTime());
            $product->addCertificat($document);
            $this->managerRegistry->getManager()->persist($product);
            $this->managerRegistry->getManager()->persist($document);
            $this->managerRegistry->getManager()->flush();
            $this->managerRegistry->getManager()->refresh($document);
        }

        return $this->render('backend/product/components/doc_box.html.twig', [
            'doc' => $document,
            'product' => $product,
            'docsCount' => count($product->getCertificats()),
        ]);
    }

    #[Route(path: '/document/{id}/remove', name: 'ajax_product_document_remove', methods: ['GET', 'POST'])]
    public function productDocumentRemove(Document $document): Response
    {
        $productsDirectory = $this->getParameter('app_product_upload_dir');
        $offer = $document->getOffer();
        if ($offer instanceof Offer) {
            $offer->removeDocument($document);
            $document->setOffer(null);
            $this->managerRegistry->getManager()->persist($offer);
        }
        $this->managerRegistry->getManager()->remove($document);
        $this->managerRegistry->getManager()->flush();
        $file = $productsDirectory.'/docs/'.$document->getFilename();
        try {
            if (file_exists($file)) {
                @unlink($file);

                return new JsonResponse(true);
            } else {
                return new JsonResponse(false);
            }
        } catch (\Exception $exception) {
            return new JsonResponse(false);
        }
    }

    #[Route(path: '/image/{id}/remove', name: 'ajax_product_image_remove', methods: ['GET', 'POST'])]
    public function productImageRemove(Product $product): Response
    {
        $productsDirectory = $this->getParameter('app_product_upload_dir');
        $image = $product->getImage();
        if ($image !== null) {
            if (file_exists($productsDirectory.'/'.$image)) {
                @unlink($productsDirectory.'/'.$image);
            }
            $product->setImage(null);
            $this->managerRegistry->getManager()->persist($product);
            $this->managerRegistry->getManager()->flush();

            return new JsonResponse(true);
        }

        return new JsonResponse(false);
    }
}
