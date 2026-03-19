<?php

namespace App\Repository;

use App\Entity\QuestionArea;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuestionArea>
 *
 * @method QuestionArea|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuestionArea|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuestionArea[]    findAll()
 * @method QuestionArea[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionAreaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionArea::class);
    }

    public function getDependencies($a)
    {
        return $this->createQueryBuilder('o')
            ->where('o.subCategory = :category')->setParameter('category', $a)
            ->getQuery()
            ->getResult();
    }

    public function save(QuestionArea $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(QuestionArea $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return QuestionArea[] Returns an array of QuestionArea objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('q')
    //            ->andWhere('q.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('q.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?QuestionArea
    //    {
    //        return $this->createQueryBuilder('q')
    //            ->andWhere('q.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
