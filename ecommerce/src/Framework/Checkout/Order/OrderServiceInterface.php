<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Checkout\Order;

use Wsei\Ecommerce\Entity\Cart;
use Wsei\Ecommerce\Entity\Order;

interface OrderServiceInterface
{
    /**
     * Place an order from the active cart
     */
    public function placeOrder(Cart $cart, int $addressId): Order;
}
