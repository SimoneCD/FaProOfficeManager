<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\Reminder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Reminder|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reminder|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reminder[]    findAll()
 * @method Reminder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReminderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reminder::class);
    }

    /**
     * @return int|mixed[]|string
     */
    public function findInvoiceReminder(Invoice $invoice)
    {
        return $this->createQueryBuilder('i')
            ->where('i.invoice = :invoice')
            ->setParameter('invoice', $invoice)
            ->andWhere('i.type LIKE :typePlus')
                ->setParameter('typePlus', 'reminder%')
            ->getQuery()
            ->getResult()
        ;
    }
}
