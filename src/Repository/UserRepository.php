<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return int|mixed|string
     */
    public function findRealAll()
    {
        return $this->createQueryBuilder('u')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int|mixed|string
     */
    public function findServiceUser()
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->orWhere('u.roles LIKE :role2')
            ->setParameter('role', '%ROLE_EMPLOYEE_SERVICE%')
            ->setParameter('role2', '%ROLE_EMPLOYEE_EXTERN%')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int|mixed|string
     */
    public function findMontageUser()
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->orWhere('u.roles LIKE :role2')
            ->orWhere('u.roles LIKE :role3')
            ->setParameter('role', '%ROLE_MONTAGE%')
            ->setParameter('role2', '%ROLE_EMPLOYEE_EXTERN%')
            ->setParameter('role3', '%ROLE_EMPLOYEE_SERVICE%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds all entities in the repository.
     *
     * @psalm-return list<T> The entities.
     */
    public function findAll(): ?array
    {
        return $this->createQueryBuilder('u')
            ->where('u.status = true')
            ->getQuery()
            ->getResult()
        ;
    }


    /*
    public function findOneBySomeField($value): ?Product
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
