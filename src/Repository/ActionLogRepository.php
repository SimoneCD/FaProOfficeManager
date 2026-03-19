<?php

namespace App\Repository;

use App\Entity\ActionLog;
use App\Entity\Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActionLog>
 *
 * @method ActionLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActionLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActionLog[]    findAll()
 * @method ActionLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActionLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActionLog::class);
    }

    public function save(ActionLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findLastWeekNotes(): array
    {
        $today = new \DateTime();
        $lastWeek = (clone $today)->modify('-7 days');

        return $this->createQueryBuilder('a')
            ->select('a, answers')
            ->leftJoin('a.answers', 'answers')
            ->where('a.createdAt BETWEEN :start AND :end')
            ->andWhere('a.type != :type')
            ->setParameter('start', $lastWeek)
            ->setParameter('end', $today)
            ->setParameter('type', 'answer')
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOfferNotes(Offer $offer): array
    {
        return $this->createQueryBuilder('a')
            ->select('a, answers')
            ->leftJoin('a.answers', 'answers')
            ->where('a.offer = :offer')
            ->andWhere('a.type != :type')
            ->setParameter('offer', $offer)
            ->setParameter('type', 'answer')
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function remove(ActionLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
