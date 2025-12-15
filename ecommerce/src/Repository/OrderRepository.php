<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Wsei\Ecommerce\Entity\Order;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function getNextOrderNumber(): string
    {
        $result = $this->createQueryBuilder('o')
            ->select('MAX(o.orderNumber)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($result === null) {
            return '00001';
        }

        $lastNumber = (int) $result;
        $nextNumber = $lastNumber + 1;

        return str_pad((string) $nextNumber, 5, '0', \STR_PAD_LEFT);
    }
}
