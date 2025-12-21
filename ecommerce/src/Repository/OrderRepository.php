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

    /**
     * @return Order[]
     */
    public function findAllPaginated(int $page, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Order[]
     */
    public function findByCustomerPaginated(int $customerId, int $page, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('o')
            ->andWhere('o.customer = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('o.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByCustomer(int $customerId): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.customer = :customerId')
            ->setParameter('customerId', $customerId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
