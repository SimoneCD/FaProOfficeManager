<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Grok;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Grok>
 *
 * @method Grok|null find($id, $lockMode = null, $lockVersion = null)
 * @method Grok|null findOneBy(array $criteria, array $orderBy = null)
 * @method Grok[]    findAll()
 * @method Grok[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GrokRepository extends ServiceEntityRepository
{
    private const DATA = ['c.id as grokId', 'c.date', 'c.text as content', 'u.id as userId', 'u.fullName', 'u.color'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Grok::class);
    }

    public function save(Grok $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Grok $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getScalar(int $id): array
    {
        if ($id > 0) {
            $qb = $this->createQueryBuilder('c')
                ->leftJoin('c.user', 'u')
                ->select(self::DATA)
                ->where('c.id > :id')->setParameter('id', $id - Grok::MAX_RESULT)
                ->orderBy('c.id', 'ASC')
                ->getQuery();
        } else {
            $qb = $this->createQueryBuilder('c')
                ->leftJoin('c.user', 'u')
                ->select(self::DATA)
                ->orderBy('c.id', 'ASC')
                ->getQuery();
        }

        return $qb->getScalarResult();
    }

    /**
     * @return int|mixed|string|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findNext(int $id)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.id > :id')->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery();

        return $qb->getOneOrNullResult();
    }

    public function getLastScalar(int $count): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->select(self::DATA)
            ->orderBy('c.id', 'DESC')
            ->setMaxResults($count)
            ->getQuery();

        return $qb->getScalarResult()
        ;
    }

    public function getLastData(int $count): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->orderBy('c.id', 'ASC')
            ->setMaxResults($count)
            ->getQuery();

        return $qb->getScalarResult()
        ;
    }

    public function lastId(): int
    {
        return $this->createQueryBuilder('c')
            ->select('c.id')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

//    public function findOneBySomeField($value): ?Grok
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
