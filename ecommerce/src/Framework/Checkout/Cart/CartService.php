<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Checkout\Cart;

use Doctrine\ORM\EntityManagerInterface;
use Wsei\Ecommerce\Entity\Cart;
use Wsei\Ecommerce\Entity\CartItem;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Repository\CartItemRepository;
use Wsei\Ecommerce\Repository\CartRepository;
use Wsei\Ecommerce\Repository\ProductRepository;

class CartService implements CartServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartRepository $cartRepository,
        private readonly CartItemRepository $cartItemRepository,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function getOrCreateActiveCart(Customer $customer): Cart
    {
        $cart = $this->cartRepository->findActiveByCustomer($customer);

        if ($cart === null) {
            $cart = new Cart();
            $cart->setCustomer($customer);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }

        return $cart;
    }

    public function addItem(Cart $cart, int $productId, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        $product = $this->productRepository->find($productId);

        if ($product === null) {
            throw new \InvalidArgumentException('Product not found');
        }

        if ($product->getStock() === 0) {
            throw new \InvalidArgumentException('Product is out of stock');
        }

        // Check if product already exists in cart
        $existingItem = $cart->findItemByProduct($product);

        if ($existingItem !== null) {
            // Increase quantity of existing item
            $existingItem->increaseQuantity($quantity);
            $cart->updateTimestamp();
            $this->entityManager->flush();

            return $existingItem;
        }

        // Create new cart item
        $cartItem = new CartItem();
        $cartItem->setProduct($product);
        $cartItem->setQuantity($quantity);
        $cart->addItem($cartItem);
        $cart->updateTimestamp();

        $this->entityManager->persist($cartItem);
        $this->entityManager->flush();

        return $cartItem;
    }

    public function removeItem(Cart $cart, int $itemId): void
    {
        $cartItem = $this->cartItemRepository->findOneByCart($itemId, $cart->getId());

        if ($cartItem === null) {
            throw new \InvalidArgumentException('Cart item not found');
        }

        $cart->removeItem($cartItem);
        $cart->updateTimestamp();

        $this->entityManager->remove($cartItem);
        $this->entityManager->flush();
    }

    public function clearCart(Cart $cart): void
    {
        $cart->clearItems();
        $cart->updateTimestamp();

        $this->entityManager->flush();
    }
}
