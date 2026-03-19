<?php

namespace App\Repository;

use App\Entity\OfferCategory;
use App\Entity\ProductCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OfferCategory>
 *
 * @method OfferCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method OfferCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method OfferCategory[]    findAll()
 * @method OfferCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OfferCategory::class);
    }

    public function save(OfferCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OfferCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByProductCategory(ProductCategory $category): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.productCategory = :cat')
            ->setParameter('cat', $category)
            ->getQuery()
            ->getResult()
        ;
    }

//    public function findOneBySomeField($value): ?OfferCategory
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
