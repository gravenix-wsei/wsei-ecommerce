<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Wsei\Ecommerce\Entity\CartItem;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    /**
     * Find a cart item that belongs to a specific cart
     */
    public function findOneByCart(int $itemId, int $cartId): ?CartItem
    {
        return $this->createQueryBuilder('ci')
            ->andWhere('ci.id = :itemId')
            ->andWhere('ci.cart = :cartId')
            ->setParameter('itemId', $itemId)
            ->setParameter('cartId', $cartId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

