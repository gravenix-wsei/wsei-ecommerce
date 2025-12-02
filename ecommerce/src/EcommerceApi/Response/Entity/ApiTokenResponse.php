<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Admin\ApiToken;
use Wsei\Ecommerce\Utility\Defaults;

class ApiTokenResponse extends EcommerceResponse
{
    public function __construct(private readonly ApiToken $apiToken) {
        parent::__construct();
    }

    protected function formatResponse(): array
    {
        return [
            'token' => $this->apiToken->getToken(),
            'expiresAt' => $this->apiToken->getExpiresAt()->format(Defaults::DEFAULT_DATE_FORMAT),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'ApiToken';
    }
}

