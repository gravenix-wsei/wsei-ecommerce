<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response;

use OpenApi\Attributes as OA;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\ProductResponse;
use Wsei\Ecommerce\Entity\Product;

#[OA\Schema(
    schema: 'ProductListResponse',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ProductResponse')
        ),
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'totalPages', type: 'integer', example: 10),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'ProductList'),
    ]
)]
class ProductListResponse extends EcommerceResponse
{
    /**
     * @param Product[] $products
     */
    public function __construct(
        private readonly array $products,
        private readonly int $page,
        private readonly int $totalPages
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
                fn (Product $product) => new ProductResponse($product)->formatResponse(),
                $this->products
            ),
            'page' => $this->page,
            'totalPages' => $this->totalPages,
        ];
    }

    protected function getApiDescription(): ?string
    {
        return 'ProductList';
    }
}
