<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use OpenApi\Attributes as OA;
use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Product;

#[OA\Schema(
    schema: 'ProductResponse',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Laptop'),
        new OA\Property(property: 'description', type: 'string', example: 'A powerful laptop', nullable: true),
        new OA\Property(property: 'stock', type: 'integer', example: 100),
        new OA\Property(property: 'priceNet', type: 'string', example: '999.99'),
        new OA\Property(property: 'priceGross', type: 'string', example: '1229.99'),
        new OA\Property(property: 'category', ref: '#/components/schemas/CategoryResponse', nullable: true),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'Product'),
    ]
)]
class ProductResponse extends EcommerceResponse
{
    public function __construct(
        private readonly Product $product
    ) {
        parent::__construct();
    }

    protected function formatData(): array
    {
        $data = [
            'id' => $this->product->getId(),
            'name' => $this->product->getName(),
            'description' => $this->product->getDescription(),
            'stock' => $this->product->getStock(),
            'priceNet' => $this->product->getPriceNet(),
            'priceGross' => $this->product->getPriceGross(),
            'category' => null,
        ];

        if ($this->product->getCategory() !== null) {
            $categoryResponse = new CategoryResponse($this->product->getCategory());
            $data['category'] = $categoryResponse->formatResponse();
        }

        return $data;
    }

    protected function getApiDescription(): string
    {
        return 'Product';
    }
}
