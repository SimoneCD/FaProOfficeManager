<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OfferQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OfferQuestion>
 *
 * @method OfferQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method OfferQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method OfferQuestion[]    findAll()
 * @method OfferQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferQuestionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OfferQuestion::class);
    }

    public function save(OfferQuestion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OfferQuestion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    public function findOneBySomeField($value): ?OfferQuestion
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
