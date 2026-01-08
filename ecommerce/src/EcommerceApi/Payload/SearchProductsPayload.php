<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Payload;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'SearchProductsPayload',
    properties: [
        new OA\Property(property: 'categoryId', type: 'integer', example: 1, description: 'Filter by category ID', nullable: true),
        new OA\Property(property: 'page', type: 'integer', example: 1, description: 'Page number', default: 1),
        new OA\Property(property: 'limit', type: 'integer', example: 20, description: 'Items per page (max 100)', default: 20),
    ]
)]
class SearchProductsPayload
{
    public function __construct(
        #[Assert\Type(type: 'integer', message: 'Category ID must be an integer')]
        #[Assert\Positive(message: 'Category ID must be positive')]
        public readonly ?int $categoryId = null,
        #[Assert\Type(type: 'integer', message: 'Page must be an integer')]
        #[Assert\Positive(message: 'Page must be positive')]
        public readonly int $page = 1,
        #[Assert\Type(type: 'integer', message: 'Limit must be an integer')]
        #[Assert\Positive(message: 'Limit must be positive')]
        #[Assert\LessThanOrEqual(value: 100, message: 'Limit cannot exceed 100')]
        public readonly int $limit = 20,
    ) {
    }
}
