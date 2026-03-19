<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Offer;
use App\Entity\OfferCategory;
use App\Entity\OfferSubCategory;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProjectTeam;
use App\Entity\ProjectTeamCategory;
use App\Entity\User;
use App\Repository\CustomerRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;

trait CacheTrait
{
    /**
     * deleted on User:edit and new.
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    protected function getServiceUsers()
    {
        // return $this->fa->get('service_user', function (ItemInterface $item) {
        //    $item->expiresAfter(3600);
        return $this->em->getRepository(User::class)->findServiceUser();
        // });
    }

    /**
     * deleted on OfferController:deleteOffer.
     *
     * @throws InvalidArgumentException
     */
    protected function getOfferMainProducts(Offer $offer): mixed
    {
        // return $this->fa->get('find_offer_main_products'.$offer->getId(), function (ItemInterface $item) use ($offer) {
        //    $item->expiresAfter(3600);
        $productCategory = !empty($offer->getCategory()) ? $offer->getCategory()->getProductCategory() : null;
        if ($productCategory instanceof ProductCategory) {
            return $this->em->getRepository(Product::class)->findGlobalOfferProductsByCategory($productCategory);
        }

        return [];
        // });
    }

    /**
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    protected function getCustomers()
    {
        // return $this->fa->get('customer', function (ItemInterface $item) {
        //    $item->expiresAfter(3600);

        return $this->em->getRepository(Customer::class)->findAll();
        // });
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getCustomer(int $id): mixed
    {
        // return $this->fa->get('customer'.$id, function (ItemInterface $item) use ($id) {
        //    $item->expiresAfter(3600);

        return $this->em->getRepository(CustomerRepository::class)->find($id);
        // });
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getSubCategories(OfferCategory $category): mixed
    {
        // return $this->fa->get('sub-category-'.$category->getId(), function (ItemInterface $item) use ($category) {
        //    $item->expiresAfter(3600);
        return $this->em->getRepository(OfferSubCategory::class)->findBy([
            'category' => $category,
        ]);
        // });
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getCategories(): mixed
    {
        // return $this->fa->get('categories', function (ItemInterface $item) {
        //    $item->expiresAfter(3600);
        return $this->em->getRepository(OfferCategory::class)->findAll();
        // });
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getProductCategories(): mixed
    {
        // return $this->fa->get('product-categories', function (ItemInterface $item) {
        //    $item->expiresAfter(3600);
        return $this->em->getRepository(ProductCategory::class)->findAll();
        // });
    }

    /**
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    protected function getCategory(int $categoryId)
    {
        // return $this->fa->get('category'.$categoryId, function (ItemInterface $item) use ($categoryId) {
        //    $item->expiresAfter(3600);

        return $this->em->getRepository(OfferCategory::class)->find($categoryId);
        // });
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getTeams(): mixed
    {
        // return $this->fa->get('teams', function (ItemInterface $item) {
        //    $item->expiresAfter(3600);

        return $this->em->getRepository(ProjectTeam::class)->findAll();
        // });
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getTeamCategories(): mixed
    {
        // return $this->fa->get('teams', function (ItemInterface $item) {
        //    $item->expiresAfter(3600);

        return $this->em->getRepository(ProjectTeamCategory::class)->findAll();
        // });
    }

    protected function deleteUserDependCache(Offer $offer, User $user)
    {
        // $this->fa->delete('service_user'.$offer->getId());
    }

    protected function deleteOfferDependCache(Offer $offer, User $user)
    {
        // $this->fa->delete('find_offer_main_products'.$offer->getId());
    }
}
