<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Cart;
use Wsei\Ecommerce\Utility\Defaults;

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
            'createdAt' => $this->cart->getCreatedAt()?->format(Defaults::DEFAULT_DATE_FORMAT),
            'updatedAt' => $this->cart->getUpdatedAt()?->format(Defaults::DEFAULT_DATE_FORMAT),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'Cart';
    }
}
