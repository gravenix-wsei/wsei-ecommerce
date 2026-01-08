<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response;

use OpenApi\Attributes as OA;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\CategoryResponse;
use Wsei\Ecommerce\Entity\Category;

#[OA\Schema(
    schema: 'CategoryListResponse',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CategoryResponse')
        ),
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'totalPages', type: 'integer', example: 5),
        new OA\Property(
            property: 'nextPage',
            type: 'string',
            format: 'uri',
            example: 'http://localhost/ecommerce/api/v1/categories?page=2&limit=20',
            nullable: true
        ),
        new OA\Property(property: 'previousPage', type: 'string', format: 'uri', example: null, nullable: true),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'CategoryList'),
    ]
)]
class CategoryListResponse extends EcommerceResponse
{
    /**
     * @param Category[] $categories
     */
    public function __construct(
        private readonly array $categories,
        private readonly int $page,
        private readonly int $totalPages,
        private readonly ?string $nextPage,
        private readonly ?string $previousPage
    ) {
        parent::__construct();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatData(): array
    {
        return [
            'data' => \array_map(
                fn (Category $category) => new CategoryResponse($category)->formatResponse(),
                $this->categories
            ),
            'page' => $this->page,
            'totalPages' => $this->totalPages,
            'nextPage' => $this->nextPage,
            'previousPage' => $this->previousPage,
        ];
    }

    protected function getApiDescription(): ?string
    {
        return 'CategoryList';
    }
}
