<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use OpenApi\Attributes as OA;
use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Entity\OrderItem;

#[OA\Schema(
    schema: 'OrderAddressData',
    properties: [
        new OA\Property(property: 'firstName', type: 'string', example: 'John'),
        new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
        new OA\Property(property: 'street', type: 'string', example: '123 Main St'),
        new OA\Property(property: 'zipcode', type: 'string', example: '12345'),
        new OA\Property(property: 'city', type: 'string', example: 'New York'),
        new OA\Property(property: 'country', type: 'string', example: 'USA'),
    ]
)]
#[OA\Schema(
    schema: 'OrderItemData',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'productId', type: 'integer', example: 5),
        new OA\Property(property: 'productName', type: 'string', example: 'Laptop'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'priceNet', type: 'string', example: '999.99'),
        new OA\Property(property: 'priceGross', type: 'string', example: '1229.99'),
    ]
)]
#[OA\Schema(
    schema: 'OrderResponse',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'orderNumber', type: 'string', example: 'ORD-20260108-001'),
        new OA\Property(property: 'status', type: 'string', example: 'new', enum: [
            'new',
            'pending_payment',
            'paid',
            'processing',
            'shipped',
            'delivered',
            'cancelled',
        ]),
        new OA\Property(property: 'totalPriceNet', type: 'string', example: '1999.98'),
        new OA\Property(property: 'totalPriceGross', type: 'string', example: '2459.98'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-01-08 10:00:00'),
        new OA\Property(property: 'address', ref: '#/components/schemas/OrderAddressData'),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrderItemData')
        ),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'Order'),
    ]
)]
class OrderResponse extends EcommerceResponse
{
    public function __construct(
        private readonly Order $order
    ) {
        parent::__construct();
    }

    protected function formatData(): array
    {
        $orderAddress = $this->order->getOrderAddress();

        return [
            'id' => $this->order->getId(),
            'orderNumber' => $this->order->getOrderNumber(),
            'status' => $this->order->getStatus()
                ->value,
            'totalPriceNet' => $this->order->getTotalPriceNet(),
            'totalPriceGross' => $this->order->getTotalPriceGross(),
            'createdAt' => $this->order->getCreatedAt()?->format('Y-m-d H:i:s'),
            'address' => [
                'firstName' => $orderAddress->getFirstName(),
                'lastName' => $orderAddress->getLastName(),
                'street' => $orderAddress->getStreet(),
                'zipcode' => $orderAddress->getZipcode(),
                'city' => $orderAddress->getCity(),
                'country' => $orderAddress->getCountry(),
            ],
            'items' => array_map(
                fn (OrderItem $item) => [
                    'id' => $item->getId(),
                    'productId' => $item->getProduct()
                        ->getId(),
                    'productName' => $item->getProductName(),
                    'quantity' => $item->getQuantity(),
                    'priceNet' => $item->getPriceNet(),
                    'priceGross' => $item->getPriceGross(),
                ],
                $this->order->getItems()
                    ->toArray()
            ),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'Order';
    }
}
