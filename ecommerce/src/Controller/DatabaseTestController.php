<?php

namespace Wsei\Ecommerce\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class DatabaseTestController
{
    public function __construct(
        private Connection $connection
    ) {}

    #[Route('/test-db', name: 'test.test_db')]
    public function test(): JsonResponse
    {
        try {
            $result = $this->connection->fetchAssociative('SELECT 1 as test');

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Database connection successful',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
