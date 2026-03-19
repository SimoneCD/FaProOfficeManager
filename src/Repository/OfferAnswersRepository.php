<?php

namespace App\Repository;

use App\Entity\OfferAnswers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OfferAnswers>
 *
 * @method OfferAnswers|null find($id, $lockMode = null, $lockVersion = null)
 * @method OfferAnswers|null findOneBy(array $criteria, array $orderBy = null)
 * @method OfferAnswers[]    findAll()
 * @method OfferAnswers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferAnswersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OfferAnswers::class);
    }

    public function save(OfferAnswers $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OfferAnswers $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return OfferAnswers[] Returns an array of OfferAnswers objects
     */
    public function findByProductCategory($category): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.products', 'p')
            ->where('p.productCategory = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult()
        ;
    }

//    public function findOneBySomeField($value): ?OfferAnswers
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
