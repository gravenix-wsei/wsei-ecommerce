<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Entity\PaymentSession;
use Wsei\Ecommerce\Framework\Payment\Stripe\PaymentSessionStatus;

/**
 * @extends ServiceEntityRepository<PaymentSession>
 */
class PaymentSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentSession::class);
    }

    public function findByToken(string $token): ?PaymentSession
    {
        return $this->findOneBy([
            'token' => $token,
        ]);
    }

    public function findValidByToken(string $token): ?PaymentSession
    {
        $paymentSession = $this->findByToken($token);

        if ($paymentSession === null || $paymentSession->isExpired() || !$paymentSession->isActive()) {
            return null;
        }

        return $paymentSession;
    }

    /**
     * Find active payment sessions for an order
     *
     * @return PaymentSession[]
     */
    public function findActiveByOrder(Order $order): array
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.order = :order')
            ->andWhere('ps.status = :status')
            ->setParameter('order', $order)
            ->setParameter('status', PaymentSessionStatus::ACTIVE)
            ->getQuery()
            ->getResult();
    }

    /**
     * Cancel all active payment sessions for an order
     */
    public function cancelActiveSessionsForOrder(Order $order): void
    {
        $this->createQueryBuilder('ps')
            ->update()
            ->set('ps.status', ':cancelledStatus')
            ->andWhere('ps.order = :order')
            ->andWhere('ps.status = :activeStatus')
            ->setParameter('cancelledStatus', PaymentSessionStatus::CANCELLED)
            ->setParameter('order', $order)
            ->setParameter('activeStatus', PaymentSessionStatus::ACTIVE)
            ->getQuery()
            ->execute();
    }
}
