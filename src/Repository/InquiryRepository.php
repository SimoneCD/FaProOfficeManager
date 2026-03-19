<?php

namespace App\Repository;

use App\Entity\Inquiry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Inquiry|null find($id, $lockMode = null, $lockVersion = null)
 * @method Inquiry|null findOneBy(array $criteria, array $orderBy = null)
 * @method Inquiry[]    findAll()
 * @method Inquiry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InquiryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inquiry::class);
    }

    public function findLastSaleId(): int
    {
        return $this->createQueryBuilder('i')
            ->select('i.saleId')
            ->where('i.saleId IS NOT null')
            ->orderBy('i.saleId', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?Inquiry
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
