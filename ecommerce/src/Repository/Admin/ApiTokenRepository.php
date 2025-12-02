<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Repository\Admin;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Wsei\Ecommerce\Entity\Admin\ApiToken;

/**
 * @extends ServiceEntityRepository<ApiToken>
 */
class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    /**
     * Find an active (non-expired) token by its value
     */
    public function findActiveTokenByValue(string $token): ?ApiToken
    {
        $apiToken = $this->findOneBy(['token' => $token]);

        if ($apiToken === null || $apiToken->isExpired()) {
            return null;
        }

        return $apiToken;
    }
}

