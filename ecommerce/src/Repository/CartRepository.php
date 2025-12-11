<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Wsei\Ecommerce\Entity\Cart;
use Wsei\Ecommerce\Entity\Customer;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    /**
     * Find the active cart for a customer
     */
    public function findActiveByCustomer(Customer $customer): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.customer = :customer')
            ->andWhere('c.isActive = :isActive')
            ->setParameter('customer', $customer)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
