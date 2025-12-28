<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Wsei\Ecommerce\Entity\PaymentSession;

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

        if ($paymentSession === null || $paymentSession->isExpired()) {
            return null;
        }

        return $paymentSession;
    }
}
