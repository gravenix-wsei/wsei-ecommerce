<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use OpenApi\Attributes as OA;
use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Cart;
use Wsei\Ecommerce\Utility\Defaults;

#[OA\Schema(
    schema: 'CartResponse',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CartItemResponse')
        ),
        new OA\Property(property: 'totalPriceNet', type: 'string', example: '1999.98'),
        new OA\Property(property: 'totalPriceGross', type: 'string', example: '2459.98'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-01-08 10:00:00'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', example: '2026-01-08 10:30:00', nullable: true),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'Cart'),
    ]
)]
class CartResponse extends EcommerceResponse
{
    public function __construct(
        private readonly Cart $cart
    ) {
        parent::__construct();
    }

    protected function formatData(): array
    {
        $items = [];
        foreach ($this->cart->getItems() as $item) {
            $items[] = (new CartItemResponse($item))->formatResponse();
        }

        return [
            'id' => $this->cart->getId(),
            'items' => $items,
            'totalPriceNet' => $this->cart->getTotalPriceNet(),
            'totalPriceGross' => $this->cart->getTotalPriceGross(),
            'createdAt' => $this->cart->getCreatedAt()?->format(Defaults::DEFAULT_DATE_FORMAT),
            'updatedAt' => $this->cart->getUpdatedAt()?->format(Defaults::DEFAULT_DATE_FORMAT),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'Cart';
    }
}
