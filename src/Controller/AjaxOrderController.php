<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\OfferItem;
use App\Entity\Product;
use App\Entity\ProductOrder;
use App\Form\ProductOrderType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AjaxController.
 */
#[Route(path: '/ajax/ajax-order')]
class AjaxOrderController extends BaseController
{
    /**
     * Called in Materiallist when click the checkboxes.
     */
    #[Route(['de' => '/{id}/angebot/{offer}', 'en' => '/{id}/offer/{offer}'], name: 'ajax_product_order_save', methods: ['GET', 'POST'])]
    public function saveProductOrder(Request $request, Product $product, Offer $offer): JsonResponse
    {
        $post = json_decode($request->getContent());
        $items = $offer->getOfferItems();
        $amount = $post->amount ?? 1;
        if (empty($post->amount)) {
            foreach ($items as $it) {
                if ($it->getItem() instanceof Product && $it->getItem()->getId() === $product->getId()) {
                    $amount = $it->getAmount();
                }
            }
        }
        $order = new ProductOrder();
        $order->setProduct($product);
        $order->setOffer($offer);
        $order->setCreatedAt(new \DateTime());
        $order->setUser($this->getUser());
        $order->setAmount($amount);
        if (!empty($post->amount)) {
            $order->setName($post->name ?? '');
            $order->setPrice(floatval(str_replace(',', '.', $post->price)));
        } else {
            $order->setName('Auto-Generiert');
            $order->setPrice($product->getEkPrice());
        }

        $this->em->persist($order);
        $offer->addProductOrder($order);
        $this->em->persist($offer);
        $this->em->flush();

        return $this->json(true);
    }

    /**
     * Called in Materiallist when click the checkboxes.
     */
    #[Route(['de' => '/{id}/angebot-lieferung/{order}', 'en' => '/{id}/offer-deliver/{offer}'], name: 'ajax_product_deliver_save', methods: ['GET', 'POST'])]
    public function saveProductDelivery(Product $product, ProductOrder $order): JsonResponse
    {
        $order->setProduct($product);
        $order->setDeliverd(true);
        $order->setDeliverDate(new \DateTime());

        $this->em->persist($order);
        $this->em->flush();

        return $this->json(true);
    }

    #[Route(path: '/{id}/modal', name: 'ajax_product_order_modal', methods: ['GET', 'POST'])]
    public function getProduct(Request $request, Product $product): Response
    {
        $productOrder = new ProductOrder();
        $productOrder->setCreatedAt(new \DateTime());
        $productOrder->setProduct($product);
        $post = json_decode($request->getContent());
        $materialOrders = $this->em->getRepository(Offer::class)->findMaterialsByProduct($product);

        $form = $this->createForm(ProductOrderType::class, $productOrder);

        return $this->render('material/_form.html.twig', [
            'product' => $product,
            'product_order' => $productOrder,
            'offers' => $materialOrders,
            'amount' => $post->amount ?? 1,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/offer/{offer}/item/{id}/amount', name: 'ajax_order_product_amount', methods: ['GET', 'POST'])]
    public function getProductAmount(Offer $offer, OfferItem $offerItem): Response
    {
        $item = $offerItem->getItem();
        if ($offerItem->getItem() instanceof Product) {
            return $this->json([
                'amount' => $offerItem->getAmount(),
                'price' => (float) ($item->getEkPrice() * $offerItem->getAmount()),
            ]);
        }

        return $this->json([
            'amount' => $offerItem->getAmount(),
            'price' => 0,
        ]);
    }

    #[Route(path: '/offer/{id}/save', name: 'ajax_order_product_save_all', methods: ['GET', 'POST'])]
    public function saveOrder(Request $request, Product $product): Response
    {
        $post = json_decode($request->getContent());
        if (count($post->offers)) {
            foreach ($post->offers as $key => $o) {
                $offer = $this->em->getRepository(Offer::class)->find($o);
                $item = $this->em->getRepository(OfferItem::class)->findOneBy([
                    'offer' => $offer,
                    'item' => $product,
                ]);
                if ($item instanceof OfferItem) {
                    $productOrder = $this->em->getRepository(ProductOrder::class)->findOneBy([
                        'offer' => $offer,
                        'product' => $product,
                    ]);
                    if ($productOrder instanceof ProductOrder) {
                        $productOrder->setAmount((float) $post->amount);
                        $this->em->persist($productOrder);
                    } else {
                        $order = new ProductOrder();
                        $order->setOffer($offer);
                        $order->setProduct($product);
                        $order->setUser($this->getUser());
                        $order->setPrice((float) $post->price);
                        $order->setAmount((float) $post->amount);
                        $order->setName($post->name);

                        $this->em->persist($order);
                        $offer->addProductOrder($order);
                        $this->em->persist($offer);
                    }
                    $this->em->flush();
                }
            }
        } else {
            $order = new ProductOrder();
            $order->setOffer(null);
            $order->setUser($this->getUser());
            $order->setProduct($product);
            $order->setPrice((float) $post->price);
            $order->setAmount((float) $post->amount);
            $order->setName($post->name);

            $this->em->persist($order);
            $this->em->flush();
        }

        return $this->json(1);
    }
}
