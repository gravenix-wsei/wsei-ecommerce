<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SuccessResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'Success'),
    ]
)]
class SuccessResponse extends EcommerceResponse
{
    public function __construct()
    {
        parent::__construct(self::HTTP_NO_CONTENT);
    }

    protected function formatData(): array
    {
        return [];
    }

    protected function getApiDescription(): ?string
    {
        return null;
    }
}
