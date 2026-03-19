<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method Booking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Booking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Booking[]    findAll()
 * @method Booking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @return Booking[] Returns an array of Booking objects
     */
    public function findTermine(\DateTime $start, \DateTime $end, ?bool $call = false): mixed
    {
        $qb = $this->createQueryBuilder('booking')
            ->where('booking.beginAt BETWEEN :start and :end OR booking.endAt BETWEEN :start and :end')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return Booking[] Returns an array of Booking objects
     */
    public function findServiceTermine(UserInterface $serviceUser): array
    {
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('b.offer', 'o')
            ->leftJoin('o.projectTeams', 'pt');

        return $qb->where(
            $qb->expr()->orX(
                $qb->expr()->isMemberOf(':serviceUser', 'pt.users'),
                $qb->expr()->eq(':serviceUser', 'o.monteur'),
                $qb->expr()->eq(':serviceUser', 'o.user'),
                $qb->expr()->eq(':serviceUser', 'b.userTask')
            )
        )
        ->setParameter('serviceUser', $serviceUser)
        ->andWhere('b.title NOT IN (:excludedTitles)')
        ->setParameter('excludedTitles', ['Anrufen'])
        ->andWhere('o.status NOT IN (:excludedStatus)')
        ->setParameter('excludedStatus', ['deleted', 'storno', 'archive'])
        ->getQuery()
        ->getResult();
    }

    /**
     * @return Booking[] Returns an array of Booking objects
     */
    public function findCallTermine(UserInterface $serviceUser): array
    {
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('b.offer', 'o')
            ->leftJoin('o.projectTeams', 'pt');

        return $qb->where(
            $qb->expr()->orX(
                $qb->expr()->isMemberOf(':serviceUser', 'pt.users'),
                $qb->expr()->eq(':serviceUser', 'o.monteur'),
                $qb->expr()->eq(':serviceUser', 'o.user'),
                $qb->expr()->eq(':serviceUser', 'b.userTask')
            )
        )
            ->setParameter('serviceUser', $serviceUser)
            ->andWhere('b.title IN (:includedTitles)')
            ->setParameter('includedTitles', ['Anrufen'])
            ->andWhere('o.status NOT IN (:excludedStatus)')
            ->setParameter('excludedStatus', ['deleted', 'storno', 'archive'])
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Booking[] Returns an array of Booking objects
     */
    public function findOfferTermine(Offer $offer): array
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.offer', 'o', Join::WITH, 'b.offer = o')
            ->innerJoin('o.user', 'u', Join::WITH, 'o.user = u')
            ->where('b.offer = :offer')
            ->setParameter('offer', $offer)
            ->getQuery()
            ->getResult();
    }

    /*
    public function findOneBySomeField($value): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
