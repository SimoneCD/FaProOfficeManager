<?php

namespace App\Repository;

use App\Entity\KeyValueSubCategoryData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KeyValueSubCategoryData>
 *
 * @method KeyValueSubCategoryData|null find($id, $lockMode = null, $lockVersion = null)
 * @method KeyValueSubCategoryData|null findOneBy(array $criteria, array $orderBy = null)
 * @method KeyValueSubCategoryData[]    findAll()
 * @method KeyValueSubCategoryData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KeyValueSubCategoryDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KeyValueSubCategoryData::class);
    }

    public function save(KeyValueSubCategoryData $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(KeyValueSubCategoryData $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return KeyValueSubCategoryData[] Returns an array of KeyValueSubCategoryData objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('k')
//            ->andWhere('k.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('k.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?KeyValueSubCategoryData
//    {
//        return $this->createQueryBuilder('k')
//            ->andWhere('k.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
