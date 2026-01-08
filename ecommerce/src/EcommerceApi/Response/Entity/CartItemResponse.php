<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use OpenApi\Attributes as OA;
use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\CartItem;
use Wsei\Ecommerce\Utility\Defaults;

#[OA\Schema(
    schema: 'CartItemProductRef',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Laptop'),
    ]
)]
#[OA\Schema(
    schema: 'CartItemResponse',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product', ref: '#/components/schemas/CartItemProductRef'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'unitPriceNet', type: 'string', example: '999.99'),
        new OA\Property(property: 'unitPriceGross', type: 'string', example: '1229.99'),
        new OA\Property(property: 'totalPriceNet', type: 'string', example: '1999.98'),
        new OA\Property(property: 'totalPriceGross', type: 'string', example: '2459.98'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-01-08 10:00:00'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', example: '2026-01-08 10:30:00', nullable: true),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'CartItem'),
    ]
)]
class CartItemResponse extends EcommerceResponse
{
    public function __construct(
        private readonly CartItem $cartItem
    ) {
        parent::__construct();
    }

    protected function formatData(): array
    {
        return [
            'id' => $this->cartItem->getId(),
            'product' => [
                'id' => $this->cartItem->getProduct()
                    ->getId(),
                'name' => $this->cartItem->getProduct()
                    ->getName(),
            ],
            'quantity' => $this->cartItem->getQuantity(),
            'unitPriceNet' => $this->cartItem->getProduct()
                ->getPriceNet(),
            'unitPriceGross' => $this->cartItem->getProduct()
                ->getPriceGross(),
            'totalPriceNet' => $this->cartItem->getTotalPriceNet(),
            'totalPriceGross' => $this->cartItem->getTotalPriceGross(),
            'createdAt' => $this->cartItem->getCreatedAt()?->format(Defaults::DEFAULT_DATE_FORMAT),
            'updatedAt' => $this->cartItem->getUpdatedAt()?->format(Defaults::DEFAULT_DATE_FORMAT),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'CartItem';
    }
}
