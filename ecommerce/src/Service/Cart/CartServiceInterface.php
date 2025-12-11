<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Service\Cart;

use Wsei\Ecommerce\Entity\Cart;
use Wsei\Ecommerce\Entity\CartItem;
use Wsei\Ecommerce\Entity\Customer;

interface CartServiceInterface
{
    /**
     * Get or create an active cart for a customer
     */
    public function getOrCreateActiveCart(Customer $customer): Cart;

    /**
     * Add a product to the cart
     * If the product already exists in the cart, increase the quantity
     */
    public function addItem(Cart $cart, int $productId, int $quantity): CartItem;

    /**
     * Remove a specific item from the cart
     */
    public function removeItem(Cart $cart, int $itemId): void;

    /**
     * Clear all items from the cart
     */
    public function clearCart(Cart $cart): void;
}
