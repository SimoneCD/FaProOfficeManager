<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Offer;
use App\Entity\OfferCategory;
use App\Entity\OfferSubCategory;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method Offer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Offer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Offer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
    {
        parent::__construct($registry, Offer::class);
    }

    /**
     * @return mixed[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function findMaterials()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'select p.id as productId, GROUP_CONCAT(o.id SEPARATOR \',\') as offers, p.worker_product as wp, p.description, p.shop_link as link, SUM(i.amount)*p.ek_price as shift, p.stock, p.ek_price as price, p.product_category_id as catId, p.product_sub_category_id as scatId, p.product_number as pnumber, p.stock, p.name, p.shop_link as www, SUM(i.amount) as amount 
from offer as o  
left join offer_item as i on o.id = i.offer_id 
left join product as p on i.item_id = p.id 
where (o.status = "rechnungPartEingang" or o.status = "work") and (p.worker_product is null or p.worker_product = 0)
group by p.id';

        return $conn->fetchAllAssociativeIndexed($sql);
    }

    public function findSendedOffersByUser(string $date): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'select u.username as username, COUNT(o.id) as send
from offer as o  
left join `user` as u on o.user_id = u.id 
join `order` as oo on oo.offer_id = o.id 
where o.status NOT IN ("deleted","storno") 
  and oo.status NOT IN ("open","call","besichtigung") 
  and o.user_id is not null 
  and MONTH (oo.created_at) = "'.$date.'"
group by u.id order by send DESC';

        return $conn->fetchAllAssociativeIndexed($sql);
    }

    /**
     * @return mixed[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function findMaterialsByProduct(Product $product)
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o.id, i.amount, p.ekPrice, i.id as itemId')
            ->leftJoin('o.offerItems', 'i')
            ->leftJoin('i.item', 'p')
            ->where('o.status = :text or o.status = :text2')
            ->andWhere('p.id = :id')
            ->setParameter(':text', 'rechnungPartEingang')
            ->setParameter(':text2', 'work')
            ->setParameter(':id', $product->getId())
            ->getQuery()
        ;

        return $qb->getScalarResult();
    }

    /**
     * @return mixed[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function findMaterialsByOffer(Offer $offer)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'select p.id as productId, GROUP_CONCAT(o.id SEPARATOR \',\') as offers, p.worker_product as wp, p.shop_link as link, p.description, SUM(i.amount)*p.ek_price as shift, p.ek_price as price, p.product_category_id as catId, p.product_sub_category_id as scatId, p.product_number as pnumber, p.stock, p.name, p.shop_link as www, SUM(i.amount) as amount
from offer as o 
left join offer_item as i on o.id = i.offer_id
left join product as p on i.item_id = p.id
where (o.order_id IS NOT null)  and offer_id = '.$offer->getId().'
group by p.id'; /* and (p.worker_product is null or p.worker_product = 0) */

        //        $stmt = $conn->prepare($sql);
        //
        //        $stmt->executeQuery(['status' => 'rechnungPartEingang']);
        // dd($sql);
        return $conn->fetchAllAssociativeIndexed($sql);
    }

    public function findOneByLastOfferNumber(Customer $customer): string
    {
        return $this->createQueryBuilder('o')
            ->select('o.number')
            ->leftJoin('o.customer', 'c')
            ->where('c.id = :oid')
            ->orderBy('o.number', 'DESC')
            ->setParameter('oid', $customer->getId())
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllByOfferNumber(Customer $customer)
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->where('c.id = :oid')
            ->setParameter('oid', $customer->getId())
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int|mixed|string
     */
    public function findOpenItems(?string $offerState = null): mixed
    {
        $qb = $this->createQueryBuilder('c');
        if (null !== $offerState) {
            $qb->where('c.status == :status1')
                ->setParameter('status1', $offerState);
        } else {
            $qb->where($qb->expr()->orX(
                $qb->expr()->eq('c.status', 'call'),
                $qb->expr()->eq('c.status', 'call-plus'),
                $qb->expr()->eq('c.status', 'open')
            ));
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return int|mixed|string
     */
    public function findPayedItems(): mixed
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where($qb->expr()->notIn('c.status', ['storno', 'rechnungEingang', 'rechnungPartEingang', 'deleted']));

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return int|mixed|string
     */
    public function findRestItems(): mixed
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where($qb->expr()->orX(
            $qb->expr()->in('c.status', ['gesendet', 'bestaetigt', 'rechnungPartSend', 'rechnungPart', 'work', 'done'])
        ));

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return int|mixed|string
     */
    public function findMaterialPayedOffers($offer): mixed
    {
        if (null === $offer) {
            return [];
        }
        $qb = $this->createQueryBuilder('c')
        ->where('oi.id = :id')
        ->setParameter(':id', $offer->getId())
//        $qb->where($qb->expr()->orX(
//            $qb->expr()->notIn('c.status', ['call', 'call-plus', 'open', 'gesendet', 'rechnungEingang', 'archive', 'deleted', 'storno'])
//        ))
        ;

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return int|mixed|string
     */
    public function findPayedProducts(?Offer $offer = null): mixed
    {
        if (null === $offer) {
            return [];
        }
        $qb = $this->em->createQueryBuilder();
        $qb->select('c.id as cid, c.number, p.id, p.productNumber,p.workerProduct as wp, p.name, i.price, p.ekPrice,cu.id as cuid, cu.name as cuname, cu.surName, pc.id as catId, psc.id as scatId')
            ->from(Offer::class, 'c')
            ->join('c.offerItems', 'i')
            ->join('c.customer', 'cu')
            ->join('i.item', 'p')
            ->join('p.productCategory', 'pc')
            ->join('p.productSubCategory', 'psc')
            ->where('c.id = :id')
            ->setParameter(':id', $offer->getId())
//            ->where($qb->expr()->orX(
//                $qb->expr()->notIn('c.status', ['call', 'call-plus', 'open', 'gesendet', 'done', 'archive', 'deleted'])
//            ))
        // ->groupBy('i.item')
        ->orderBy('p.productNumber', 'ASC')
        ;

        return $qb
            ->getQuery()
            ->getScalarResult()
        ;
    }

    public function findCustomersNearby($monteurLatitude, $monteurLongitude, $radiusInKm)
    {
        //        $query = $this->getEntityManager()->createQuery(
        //            'SELECT c,
        //            (6371 * ACOS(COS(RADIANS(:monteurLatitude)) * COS(RADIANS(c.latitude)) * COS(RADIANS(c.longitude) - RADIANS(:monteurLongitude)) + SIN(RADIANS(:monteurLatitude)) * SIN(RADIANS(c.latitude)))) AS distance
        //            FROM App\Entity\Customer c
        //            HAVING distance <= :radiusInKm
        //            ORDER BY distance ASC'
        //        );
        //        $query->setParameter('monteurLatitude', $monteurLatitude);
        //        $query->setParameter('monteurLongitude', $monteurLongitude);
        //        $query->setParameter('radiusInKm', $radiusInKm);
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT o.id,o.station_zip as Ort, o.number, c.name as Vorname, c.sur_name as Nachname, o.station_lat as lat, o.station_lng as lng, (6371 * ACOS(COS(RADIANS('.$monteurLatitude.')) * COS(RADIANS(o.station_lat)) * COS(RADIANS(o.station_lng) - RADIANS('.$monteurLongitude.')) + SIN(RADIANS('.$monteurLatitude.')) * SIN(RADIANS(o.station_lat)))) AS distance
            FROM offer as o
            LEFT JOIN customer as c ON  o.customer_id = c.id
            GROUP BY o.station_lat
            HAVING distance <= '.$radiusInKm.'
            ORDER BY distance ASC
            '; /* and (p.worker_product is null or p.worker_product = 0) */

        //        $stmt = $conn->prepare($sql);
        //
        //        $stmt->executeQuery(['status' => 'rechnungPartEingang']);
        // dd($conn->fetchAllAssociativeIndexed($sql));
        return $conn->fetchAllAssociativeIndexed($sql);
        // return $query->getResult();
    }

    public function findByTerminCount(string $value): mixed
    {
        if (true == $value) {
            $sql = $this->createQueryBuilder('o')
                ->join('o.bookings', 'b', Join::WITH, 'o = b.offer')
                ->having('COUNT(b.id) > 0')
                ->orderBy('o.id', 'ASC')
                ->getQuery()
            ;
        } else {
            $sql = $this->createQueryBuilder('o')
                ->join('o.bookings', 'b', Join::WITH, 'o = b.offer')
                ->having('COUNT(b) = 0')
                ->orderBy('o.id', 'ASC')
                ->getQuery()
            ;
        }

        return $sql->getResult();
    }

    /**
     * @return Offer[] Returns an array of Offer objects
     */
    public function findOpenOffers(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :type1')
            ->orWhere('o.status = :type2')
            ->setParameter('type1', 'call')
            ->setParameter('type2', 'open')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Offer[] Returns an array of Offer objects
     */
    public function findCalledOffers(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :type1')
            // ->andWhere('o.type != :type2')
            ->setParameter('type1', 'open')
            // ->setParameter('type2', 'open')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Offer[] Returns an array of Offer objects
     */
    public function findSendedOffers(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :type1')
            // ->andWhere('o.type != :type2')
            ->setParameter('type1', 'gesendet')
            // ->setParameter('type2', 'open')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param User $user
     *
     * @return int|mixed|string
     */
    public function findProtocolByMonteur(UserInterface $user): mixed
    {
        //        $qb = $this->createQueryBuilder('o')
        //            ->leftJoin('o.projectTeams', 't')
        //            ->join('o.bookings', 'b');
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.projectTeams', 't')
            ->innerJoin('o.bookings', 'b');

        $qb->where($qb->expr()->in('o.status', ':statuses'))
            ->andWhere($qb->expr()->neq('b.title', ':title'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isMemberOf(':user', 't.users'),
                    $qb->expr()->eq('o.monteur', ':user'),
                    $qb->expr()->eq('o.user', ':user')
                )
            )
            ->orderBy('b.beginAt', 'ASC')
            ->setParameter('title', 'Anrufen')
            ->setParameter('statuses', ['call-plus', 'estimate', 'besichtigung', 'rechnungPartSend', 'rechnungPartEingang', 'work', 'done', 'report'])
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return int|mixed|string
     */
    public function findAll(): mixed
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c');
        $qb->where($qb->expr()->notIn('o.status', ['deleted', 'storno', 'archive']));

        return $qb->getQuery()->getResult();
    }

    /**
     * @return int|mixed|string
     */
    public function findProtocolByAll(): array
    {
        $qb = $this->createQueryBuilder('o');
        $qb->innerJoin('o.bookings', 'b')
            ->addSelect('c')
            ->leftJoin('o.customer', 'c')
            ->where(
                $qb->expr()->in(
                    'o.status',
                    array_map(function ($status) use ($qb) {
                        return $qb->expr()->literal($status);
                    }, ['call-plus', 'estimate', 'besichtigung', 'rechnungPartSend', 'rechnungPartEingang', 'work', 'done', 'report'])
                )
            )
            ->andWhere($qb->expr()->neq('b.title', ':title'))
            ->setParameter('title', 'Anrufen')
            ->orderBy('b.beginAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return int|mixed|string
     */
    public function findBySearch(?string $search = null, ?User $user = null, ?bool $hidden = null)
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.option', 'op')
            ->leftJoin('o.customer', 'c');
        $qb->where('o.status NOT IN (\'deleted\',\'archive\',\'storno\')');

        $this->loadOfferIndexDependencies($qb);
        $this->handleSearchAndType($qb, $search, $hidden);
        if ($user instanceof User) {
            $qb->andWhere('o.user = :user')->setParameter('user', $user->getId());
        }

        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * @return int|mixed|string
     */
    public function findMontageBySearch(?string $search = null, ?User $user = null, ?bool $hidden = null)
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.option', 'op')
            ->leftJoin('o.customer', 'c');
        $qb->where('o.status NOT IN (\'call\',\'call-plus\',\'gesendet\',\'deleted\',\'archive\',\'storno\')');

        $this->loadOfferIndexDependencies($qb);
        $this->handleSearchAndType($qb, $search, $hidden);
        $xOr = $qb->expr()->orX();
        $xOr->add($qb->expr()->isMemberOf('?1', 't.users'));
        $xOr->add($qb->expr()->eq('?1', 'o.monteur'));
        $xOr->add($qb->expr()->eq('?1', 'o.user'));
        $qb->andWhere($xOr)
        ->setParameter('1', $user);

        $result = $qb->getQuery();

        return $result->getResult();
    }

    /**
     * @return int|mixed|string
     */
    public function findByCategory(?OfferCategory $category, ?string $search = null, ?User $user = null, ?bool $hidden = null, ?bool $today = null)
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.option', 'op')
            ->leftJoin('o.customer', 'c');
        $qb->where('o.status NOT IN (\'deleted\',\'archive\',\'storno\')');
        if ($category) {
            $qb->andWhere('o.category = :category')->setParameter('category', $category->getId());
        }

        $this->loadOfferIndexDependencies($qb);
        $this->handleSearchAndType($qb, $search, $hidden);
        if ($user instanceof User) {
            $qb->andWhere('o.user = :user')->setParameter('user', $user->getId());
        }
        $this->handleSearchAndType($qb, $search, $hidden);
        if ($today) {
            $date = new \DateTime();
            $from = $date->format('Y-m-d').' 00:00:00';
            $to = $date->format('Y-m-d').' 23:59:59';
            $qb->andWhere('b.beginAt BETWEEN  :from AND :to')
                ->setParameter('from', $from)
                ->setParameter('to', $to);
        }

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    /**
     * @return int|mixed|string
     */
    public function findByCategoryAndUser(?OfferCategory $category, ?string $search = null, ?User $user = null, ?bool $hidden = null, bool $today = false): ?array
    {
        if (null === $user) {
            return null;
        }

        $qb = $this->createQueryBuilder('o')
            ->select('o')
            ->leftJoin('o.option', 'op')
            ->leftJoin('o.customer', 'c')
            ->leftJoin('o.projectTeams', 'pt')
            ->where('o.status NOT IN (:statuses)')
            ->setParameter('statuses', ['deleted', 'archive', 'storno']);

        if ($category) {
            $qb->andWhere('o.category = :category')->setParameter('category', $category->getId());
        }

        $this->loadOfferIndexDependencies($qb);
        $this->handleSearchAndType($qb, $search, $hidden);

        if ($user) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->isMemberOf(':user', 'pt.users'),
                ':user = o.monteur',
                ':user = o.user'
            ))->setParameter('user', $user);
        }

        if ($today) {
            $qb->innerJoin('o.bookings', 'b')
                ->andWhere('b.beginAt BETWEEN :startOfDay AND :endOfDay')
                ->setParameter('startOfDay', (new \DateTime('today'))->setTime(0, 0))
                ->setParameter('endOfDay', (new \DateTime('today'))->setTime(23, 59, 59));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find offers by subcategory with optional filters for search, user, visibility, and date.
     *
     * @param OfferSubCategory $category The subcategory to filter by
     * @param string|null      $search   Search term for filtering
     * @param User|null        $user     User to filter offers by
     * @param bool|null        $hidden   Whether to include hidden offers
     * @param bool             $today    Filter by today's date
     */
    public function findBySubCategory(OfferSubCategory $category, ?string $search = null, ?User $user = null, ?bool $hidden = null, bool $today = false): ?array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o')
            ->where('o.subCategory = :category')
            ->andWhere('o.status NOT IN (:statuses)')
            ->setParameters(['category' => $category, 'statuses' => ['deleted', 'archive', 'storno']]);

        $this->loadOfferIndexDependencies($qb);

        if ($user instanceof User) {
            $qb->andWhere('o.user = :user')->setParameter('user', $user);
        }

        if ($today) {
            $qb->innerJoin('o.bookings', 'b')
                ->andWhere('b.beginAt BETWEEN :startOfDay AND :endOfDay')
                ->setParameter('startOfDay', (new \DateTime('today'))->setTime(0, 0))
                ->setParameter('endOfDay', (new \DateTime('today'))->setTime(23, 59, 59));
        }

        $this->handleSearchAndType($qb, $search, $hidden);

        return $qb->getQuery()->getResult();
    }

    private function handleSearchAndType(QueryBuilder $qb, ?string $search = null, ?bool $hidden = null): void
    {
        if ($search) {
            $likeExpr = $qb->expr()->orX(
                $qb->expr()->like('c.companyName', ':search'),
                $qb->expr()->like('o.number', ':search'),
                $qb->expr()->like('c.name', ':search'),
                $qb->expr()->like('c.surName', ':search'),
                $qb->expr()->like('c.email', ':search')
            );

            $qb->andWhere($likeExpr)
                ->setParameter('search', '%'.$search.'%');
        } elseif (null !== $hidden) {
            $qb->andWhere('op.blendOut = :blendOut')
                ->setParameter('blendOut', $hidden);
        }
    }

    private function loadOfferIndexDependencies(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder->leftJoin('o.wallboxProduct', 'p')
            ->leftJoin('o.projectTeams', 't')
            ->leftJoin('o.user', 'u')
            // ->leftJoin('o.order', 'oo')
            ->leftJoin('o.monteur', 'm')
            ->leftJoin('o.bookings', 'b')
            ->leftJoin('o.inquiry', 'i')
            ->addSelect('c', 't', 'p', 'op', 'b', 'u', 'm', 'i')
        ;
    }
}
