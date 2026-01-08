<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use OpenApi\Attributes as OA;
use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Category;

#[OA\Schema(
    schema: 'CategoryResponse',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'Category'),
    ]
)]
class CategoryResponse extends EcommerceResponse
{
    public function __construct(
        private readonly Category $category
    ) {
        parent::__construct();
    }

    protected function formatData(): array
    {
        return [
            'id' => $this->category->getId(),
            'name' => $this->category->getName(),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'Category';
    }
}
