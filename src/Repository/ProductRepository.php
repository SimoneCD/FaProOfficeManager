<?php

namespace App\Repository;

use App\Entity\OfferSubCategory;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductSubCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return int|mixed|string
     */
    public function bySubCategoryAndGlobal(OfferSubCategory $subCategory)
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.productSubCategory', 's')
            ->join('s.category', 'c')
            ->where('c = :cat AND s.mainProduct = :main')->setParameter('main', false);

        return $qb->orWhere('s.global = :global')->setParameter('global', true)
            ->setParameter('cat', $subCategory->getCategory()->getProductCategory())
            ->setMaxResults(1)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Product[] Returns an array of Product objects
     */
    public function findGlobalOfferProductsByCategory(ProductCategory $category): mixed
    {
        return $this->createQueryBuilder('p')
            ->join('p.productCategory', 'c')
            ->join('c.productSubCategories', 'sc')
            ->where('p.productCategory = :cat')
            ->orWhere('sc.global = :global')
            ->setParameter('cat', $category)
            ->setParameter('global', true)
            ->orderBy('p.name', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findMainProductsByCategory(ProductSubCategory $subCategory): mixed
    {
        return $this->createQueryBuilder('p')
            ->join('p.productSubCategory', 'sc')
            ->where('sc.mainProduct = :main')
            ->andWhere('p.productSubCategory = :sub')
            ->setParameter('main', true)
            ->setParameter('sub', $subCategory)
            ->orderBy('p.kw', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Product[] Returns an array of Product objects
    //  */
    public function findByNameField($value)
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :val')
            ->orWhere('p.description LIKE :val')
            ->setParameter('val', '%'.$value.'%')
            ->orderBy('p.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByMaterialSearch($value)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = sprintf('select p.id as productId, 1 as offers, p.worker_product as wp, p.description, p.shop_link as link, p.ek_price as shift, p.stock, p.ek_price as price, p.product_category_id as catId, p.product_sub_category_id as scatId, p.product_number as pnumber, p.stock, p.name, p.shop_link as www, 1 as amount 
from  product as p
where (p.name LIKE "%s" or p.product_number LIKE "%s") and (p.worker_product is null or p.worker_product = 0)
group by p.id','%'.$value.'%', '%'.$value.'%');

        return $conn->fetchAllAssociativeIndexed($sql);
    }
}
