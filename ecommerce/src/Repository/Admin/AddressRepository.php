<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Repository\Admin;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Wsei\Ecommerce\Entity\Admin\Address;

/**
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    /**
     * Find all addresses for a specific customer
     *
     * @return Address[]
     */
    public function findByCustomer(int $customerId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.customer = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find an address that belongs to a specific customer
     */
    public function findOneByCustomer(int $addressId, int $customerId): ?Address
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :addressId')
            ->andWhere('a.customer = :customerId')
            ->setParameter('addressId', $addressId)
            ->setParameter('customerId', $customerId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Check if an address belongs to a specific customer
     */
    public function belongsToCustomer(Address $address, int $customerId): bool
    {
        return $address->getCustomer()?->getId() === $customerId;
    }
}
