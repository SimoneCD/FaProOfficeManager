<?php

namespace App\Repository;

use App\Entity\ProductManufactura;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductManufactura>
 *
 * @method ProductManufactura|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductManufactura|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductManufactura[]    findAll()
 * @method ProductManufactura[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductManufacturaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductManufactura::class);
    }

    public function save(ProductManufactura $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProductManufactura $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
