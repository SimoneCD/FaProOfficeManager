<?php

namespace App\Repository;

use App\Entity\Offer;
use App\Entity\OfferItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OfferItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method OfferItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method OfferItem[]    findAll()
 * @method OfferItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OfferItem::class);
    }

    /**
     * @return int|mixed|string
     */
    public function findMaterialPayedOffer(?Offer $offer = null): mixed
    {
        if ($offer === null) {
            return [];
        }
        $qb = $this->createQueryBuilder('oi');
        $qb->join('oi.offer', 'o')
            ->where('o.id = :id')
            ->setParameter(':id', $offer->getId())
            // ->andWhere($qb->expr()->notIn('o.status', ['call', 'call-plus', 'open', 'gesendet', 'done', 'archive', 'deleted']));
        ;

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return int|mixed|string
     */
    public function findMaterialByCustomerOffer(string $offer)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'select p.id, o.id as offerId, p.name, p.description, p.image, d.filename
from offer_item as oi
left join offer as o on o.id = oi.offer_id
left join product as p on p.id = oi.item_id
left join document as d on d.product_id = p.id
where (p.worker_product = false or p.worker_product is null) and MD5(o.id) = "'.$offer.'"';
//        $qb = $this->createQueryBuilder('oi')
//            ->leftJoin('oi.item', 'p')
//            ->leftJoin('oi.offer', 'o')
//            ->leftJoin('p.certificats', 'certificats')
//            ->where('MD5(oi.offer) = :id')
//            ->andWhere('p.workerProduct = :wp or p.workerProduct is null')
//            ->setParameter(':id', $offer)
//            ->setParameter(':wp', false)
//            ->getQuery()
//        ;

        return $conn->fetchAllAssociativeIndexed($sql);
        // dd($qb->getSQL());

        // return $qb->getResult();
    }

    /**
     * @return int|mixed|string
     */
    public function findMaterialNeeded(): mixed
    {
        $qb = $this->createQueryBuilder('oi');
        $qb->join('oi.offer', 'o')
            ->where('o.status = :id')
            ->setParameter(':id', 'rechnungPartEingang')
            // ->andWhere($qb->expr()->notIn('o.status', ['call', 'call-plus', 'open', 'gesendet', 'done', 'archive', 'deleted']));
        ;

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?OfferItem
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
