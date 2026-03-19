<?php

namespace App\Repository;

use App\Entity\ProjectTeamCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectTeamCategory>
 *
 * @method ProjectTeamCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectTeamCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectTeamCategory[]    findAll()
 * @method ProjectTeamCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectTeamCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectTeamCategory::class);
    }

    public function save(ProjectTeamCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProjectTeamCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return ProjectTeamCategory[] Returns an array of ProjectTeamCategory objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ProjectTeamCategory
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
