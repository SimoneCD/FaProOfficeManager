<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductSubCategory;
use App\Form\ProductType;
use App\Service\FileUploader;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

#[Route(['de' => '/admin/produktkategorie/{category}/unterkategorien/{subCategory}', 'en' => '/admin/productcategory/{category}/sub-categories/{subCategory}'])]
#[IsGranted('ROLE_MONTAGE')]
class ProductController extends BaseController
{
    use TargetPathTrait;

    #[Route(path: ['de' => '/produkte', 'en' => '/products'], name: 'backend_product_index', methods: ['GET'])]
    public function indexCategory(ProductCategory $category, string $subCategory = 'x'): Response
    {
        return $this->render('backend/product/index.html.twig', [
            'category' => $category,
            'cat' => $category,
            'subCategories' => $this->em->getRepository(ProductSubCategory::class)->findBy(['category' => $category]),
        ]);
    }

    #[Route(path: ['de' => '/alle', 'en' => '/all'], name: 'backend_product_all', methods: ['GET'])]
    public function indexsCategory(string $category = 'x', string $subCategory = 'x'): Response
    {
        return $this->render('backend/product/all.html.twig', [
            'products' => $this->em->getRepository(Product::class)->findAll(),
            'tableData' => 'table_data2',
        ]);
    }

    #[Route(path: ['de' => '/neu', 'en' => '/new'], name: 'backend_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, FileUploader $fileUploader, string $category, ProductSubCategory $subCategory): Response
    {
        $product = new Product();
        $lastProductNumber = $this->em->getRepository(Product::class)->findBy([
            'productSubCategory' => $subCategory,
        ], ['productNumber' => 'DESC'], 1);
        if (!empty($lastProductNumber)) {
            $num = (float) $lastProductNumber[0]->getProductNumber();
            $product->setProductNumber(($num + 0.01).'');
        }
        $product->setProductSubCategory($subCategory);
        $product->setProductCategory($subCategory->getCategory());
        // todo remove unuses parameter
        $product->setProductType('');
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $productImage */
            $productImage = $form->get('productImage')->getData();
            if (!empty($productImage)) {
                $name = $product->getName().'_'.$product->getId();
                $productImageFileName = $fileUploader->upload($productImage, 'products', $name);
                $product->setImage($productImageFileName);
            }
            $this->em->persist($product);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('p.add'));

            return $this->redirectToRoute('backend_product_index', [
                'category' => $subCategory->getCategory()->getId(),
                'subCategory' => 'x',
                '_fragment' => $request->query->get('type') ?? 'sub-'.$subCategory->getId(),
            ],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('backend/product/new.html.twig', [
            'product' => $product,
            'category' => $subCategory->getCategory(),
            'form' => $form,
        ]);
    }

    #[Route(['de' => '/{id}/bild-hochladen', 'en' => '/{id}/image-upload'], name: 'backend_product_upload_image', methods: ['GET', 'POST'])]
    public function uploadImage(Request $request, FileUploader $fileUploader, string $category, string $subCategory, Product $product): JsonResponse
    {
        $file = $request->files->get('image');
        if ($file instanceof UploadedFile) {
            $name = $product->getName().'_'.$product->getId();
            $productImageFileName = $fileUploader->upload($file, 'products', $name);
            $product->setImage($productImageFileName);
            $this->fa->delete('categories');
            $this->em->persist($product);
            $this->em->flush();

            return $this->json('succes');
        }

        return $this->json('error');
    }

    #[Route(path: '/{id}', name: 'backend_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        $generator = new BarcodeGeneratorHTML();

        return $this->render('backend/product/show.html.twig', [
            'product' => $product,
            'barcode' => $generator->getBarcode($product->getProductNumber().'.'.$product->getId(), $generator::TYPE_UPC_A),
        ]);
    }

    #[Route(path: ['de' => '/{id}/bearbeiten', 'en' => '/{id}/edit'], name: 'backend_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FileUploader $fileUploader, string $category, string $subCategory, Product $product): Response
    {
        $generator = new BarcodeGeneratorHTML();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $productImage = $form->get('productImage')->getData();

            if ($productImage instanceof UploadedFile) {
                $name = $product->getName().'_'.$product->getId();
                $productImageFileName = $fileUploader->upload($productImage, 'products', $name);
                $product->setImage($productImageFileName);
            }
            $this->em->persist($product);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('p.editSuccess'));

            return $this->redirect($this->generateUrl('backend_product_edit', ['category' => 'x', 'subCategory' => 'x', 'id' => $product->getId()]));
        }
        $barcode = $generator->getBarcode($product->getProductNumber().'-'.$product->getEkPrice().' Euro', $generator::TYPE_CODE_93);

        return $this->render('backend/product/edit.html.twig', [
            'product' => $product,
            'category' => $product->getProductCategory(),
            'form' => $form->createView(),
            'barcodes' => $barcode,
        ]);
    }

    #[Route(path: ['de' => '/{id}/loeschen', 'en' => '/{id}/delete'], name: 'backend_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, string $category, string $subCategory, Product $product, string $hdDir): Response
    {
        if ($this->isCsrfTokenValid('product-delete-'.$product->getId(), $request->request->get('_token'))) {
            $image = $product->getImage();
            $filesystem = new Filesystem();
            if (!empty($image) && $filesystem->exists($hdDir.'/'.$image)) {
                $filesystem->remove($hdDir.'/'.$image);
            }
            $this->addFlash('success', $this->translator->trans('p.deleteSuccess'));

            try {
                $this->em->remove($product);
                $this->em->flush();
            } catch (\Exception $e) {
                $this->addFlash('danger', 'p.deleteError');
            }
        }

        return $this->redirectToRoute('backend_product_index', ['category' => $product->getProductCategory()->getId(), 'subCategory' => 'x'], Response::HTTP_SEE_OTHER);
    }
}
