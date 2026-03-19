<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]    findAll()
 * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function findCustomersByPhoneFormat(string $phoneNumber)
    {
        $qb = $this->createQueryBuilder('c');

        return $qb->select('c')
            ->where('c.phone LIKE :plusPhoneNumber OR c.phone LIKE :zeroPhoneNumber')
            ->andWhere("REPLACE(REPLACE(REPLACE(c.phone, '+', ''), ' ', ''), '-', '') LIKE :formattedNumber")
            ->setParameter('plusPhoneNumber', '+49%')
            ->setParameter('zeroPhoneNumber', '0%')
            ->setParameter('formattedNumber', '%'.$phoneNumber.'%')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Customer[]
     */
    public function findDashboardCustomer(): mixed
    {
        return $this->createQueryBuilder('c')
            ->setMaxResults(10)
            ->orderBy('c.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneByPhoneOrMail(array $saleData): mixed
    {
        return $this->createQueryBuilder('c')
            ->where('c.surName = :surName')
            ->andWhere('c.name = :name')
            ->andWhere('c.phone = :phone OR c.email = :email')
            ->setParameter('email', $saleData['email'] ?? '')
            ->setParameter('phone', $saleData['phone'] ?? $saleData['mobile'] ?? '')
            ->setParameter('surName', $saleData['last_name'])
            ->setParameter('name', $saleData['first_name'])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return int|mixed|string
     */
    public function search(string $value): mixed
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :val')
            ->orWhere('c.surName LIKE :val')
            ->orWhere('c.companyName LIKE :val')
            ->setParameter('val', '%'.$value.'%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return int|mixed|string
     */
    public function searchJson(string $value): mixed
    {
        return $this->createQueryBuilder('c')
            ->select('c.id', 'c.name', 'c.surName')
            ->where('c.surName LIKE :val')
            ->orWhere('c.companyName LIKE :val')
            ->setParameter('val', '%'.$value.'%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return int|mixed|string
     */
    public function searchByExtern(User $user, string $value): mixed
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.offers', 'o')
            ->where('o.user = :user');
        $exp = $qb->expr()->orX(
            $qb->expr()->like('c.name', ':val'),
            $qb->expr()->eq('c.surName', ':val'),
            $qb->expr()->eq('c.companyName', ':val')
        );

        return $qb->andWhere($exp)
            ->setParameter('user', $user)
            ->setParameter('val', '%'.$value.'%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function searchByJson(User $user, string $value): mixed
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.offers', 'o')
            ->where('o.user = :user')
            ->select('c.id', 'c.name', 'c.surName');
        $exp = $qb->expr()->orX(
            $qb->expr()->eq('c.surName', ':val'),
            $qb->expr()->eq('c.companyName', ':val')
        );

        return $qb->andWhere($exp)
            ->setParameter('user', $user)
            ->setParameter('val', '%'.$value.'%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
}
