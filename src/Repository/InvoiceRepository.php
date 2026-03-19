<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Invoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invoice[]    findAll()
 * @method Invoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * @return int|mixed|string
     */
    public function findInvoice(\DateTime $start, string $type, bool $payed = true)
    {
        $start->setTime(0, 0, 1);
        $qb = $this->createQueryBuilder('i');
        $qb->where('i.bezahlt is not NULL');
        $qb->leftJoin('i.invoiceOrder', 'o');
        $qb->addSelect('o');
        if ($payed) {
            if ($type === 'part') {
                // findet bezahlte Abschlagsrechnung
                $qb->andWhere('o.status IN (:status)')
                    ->setParameter('status', ['rechnungPartEingang', 'work', 'done', 'rechnungSend']);
            } else {
                // findet bezahlte Abschlussrechnung
                $qb->andWhere('o.status = :status')->setParameter('status', 'rechnungEingang');
            }

            return $qb->andWhere('i.bezahlt LIKE :start')
                ->andWhere('i.type = :type')
                ->setParameter('start', $start->format('Y-m-d').'%')
                ->setParameter('type', $type)
                ->getQuery()
                ->getResult()
            ;
        } else {
            if ($type === 'part') {
                // findet unbezahlte Abschlagsrechnung
                $qb->andWhere('o.status IN (:status)')
                    ->setParameter('status', ['bestätigt', 'rechnungPartSend']);
            } else {
                // findet unbezahlte Abschlussrechnung
                $qb->andWhere('o.status IN (:status)')
                    ->setParameter('status', ['rechnungPartEingang', 'work', 'done', 'rechnungSend']);
            }

            return $qb->andWhere('i.bezahlt LIKE :start')
                ->andWhere('i.type = :type')
                ->setParameter('start', $start->format('Y-m-d').'%')
                ->setParameter('type', $type)
                ->getQuery()
                ->getResult()
            ;
        }
    }

    /**
     * @param bool $type
     *
     * @return int|mixed|string
     */
    public function findMonthUnpayedInvoice(\DateTime $start)
    {
        $start->setTime(0, 0, 1);
        $qb = $this->createQueryBuilder('i');
        $qb->where('i.bezahlt is NULL');
        $qb->join('i.invoiceOrder', 'o');
        $r = $qb->andWhere('o.sendAt LIKE :start');

        return $r->setParameter('start', $start->format('Y-m-').'%')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param bool $type
     *
     * @return int|mixed|string
     */
    public function findUnpayedInvoice()
    {
        $qb = $this->createQueryBuilder('i');

        return $qb->where('i.bezahlt is NULL')
            ->leftJoin('i.invoiceOrder', 'io')
            ->leftJoin('o.offer', 'o')
            ->leftJoin('i.reminder', 'r')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return int|mixed|string
     */
    public function findMonthInvoice(\DateTime $start, $type, bool $payed = true)
    {
        $start->setTime(0, 0, 1);
        $qb = $this->createQueryBuilder('i');
        $qb->join('i.invoiceOrder', 'o');

        if ($payed) {
            $qb->where('i.bezahlt IS NOT NULL');
        } else {
            $qb->where('i.bezahlt IS NULL');
        }
        if ($type !== false) {
            $qb->andWhere('i.type = :type')
                ->setParameter('type', $type);
        }
        $qb->andWhere('i.sendDate LIKE :datei')
            ->setParameter('datei', $start->format('Y-m').'%');
        $qb->andWhere('o.status NOT IN (:status)')
            ->setParameter('status', ['archive', 'deleted', 'storno']);

        return $qb->getQuery()
        ->getResult()
        ;
    }

    /**
     * @return int|mixed[]|string
     */
    public function findPartInvoice(Order $order)
    {
        return $this->createQueryBuilder('i')
            ->where('i.invoiceOrder = :order')->setParameter('order', $order)
            ->andWhere('i.type = :type')->setParameter('type', 'part')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return int|mixed[]|string
     */
    public function findPartPlusInvoices(Order $order)
    {
        return $this->createQueryBuilder('i')
            ->where('i.invoiceOrder = :order')->setParameter('order', $order)
            ->andWhere('i.type = :typePlus')
                ->setParameter('typePlus', 'part-plus')
            ->getQuery()
            ->getResult()
        ;
    }
}
