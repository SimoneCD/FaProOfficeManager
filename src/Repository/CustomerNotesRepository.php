<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\CustomerNotes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerNotes>
 *
 * @method CustomerNotes|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerNotes|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomerNotes[]    findAll()
 * @method CustomerNotes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerNotesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerNotes::class);
    }

    /**
     * @return int|mixed|string|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findNewCustomerChats(): ?array
    {
        $qb = $this->createQueryBuilder('cc');
        $qb->where('cc.answeredAt IS NULL');
        $qb->andWhere('cc.user IS NULL')
        ->orderBy('cc.createdAt', 'DESC')
        ->groupBy('cc.customer');

        return $qb->getQuery()->getResult();
    }

    public function save(CustomerNotes $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CustomerNotes $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return CustomerNotes[] Returns an array of CustomerNotes objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CustomerNotes
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
