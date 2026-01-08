<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use OpenApi\Attributes as OA;
use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\ApiToken;
use Wsei\Ecommerce\Utility\Defaults;

#[OA\Schema(
    schema: 'ApiTokenResponse',
    properties: [
        new OA\Property(property: 'token', type: 'string', example: 'abc123xyz789'),
        new OA\Property(property: 'expiresAt', type: 'string', format: 'date-time', example: '2026-01-08 12:00:00'),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'ApiToken'),
    ]
)]
class ApiTokenResponse extends EcommerceResponse
{
    public function __construct(
        private readonly ApiToken $apiToken
    ) {
        parent::__construct();
    }

    protected function formatData(): array
    {
        return [
            'token' => $this->apiToken->getToken(),
            'expiresAt' => $this->apiToken->getExpiresAt()
                ->format(Defaults::DEFAULT_DATE_FORMAT),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'ApiToken';
    }
}
