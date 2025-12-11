<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\CartItem;
use Wsei\Ecommerce\Utility\Defaults;

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
                'priceNet' => $this->cartItem->getProduct()
                    ->getPriceNet(),
                'priceGross' => $this->cartItem->getProduct()
                    ->getPriceGross(),
            ],
            'quantity' => $this->cartItem->getQuantity(),
            'createdAt' => $this->cartItem->getCreatedAt()?->format(Defaults::DEFAULT_DATE_FORMAT),
            'updatedAt' => $this->cartItem->getUpdatedAt()?->format(Defaults::DEFAULT_DATE_FORMAT),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'CartItem';
    }
}
