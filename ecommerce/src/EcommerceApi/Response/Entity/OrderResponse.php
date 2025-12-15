<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Entity\OrderItem;

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
                    'productId' => $item->getProduct()->getId(),
                    'productName' => $item->getProductName(),
                    'quantity' => $item->getQuantity(),
                    'priceNet' => $item->getPriceNet(),
                    'priceGross' => $item->getPriceGross(),
                ],
                $this->order->getItems()->toArray()
            ),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'Order';
    }
}
