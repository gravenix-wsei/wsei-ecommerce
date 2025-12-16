<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Checkout\Order;

use Doctrine\ORM\EntityManagerInterface;
use Wsei\Ecommerce\Entity\Cart;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Entity\OrderItem;
use Wsei\Ecommerce\Repository\AddressRepository;
use Wsei\Ecommerce\Repository\OrderRepository;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly AddressRepository $addressRepository,
    ) {
    }

    public function placeOrder(Cart $cart, int $addressId): Order
    {
        $customer = $cart->getCustomer();
        assert($customer !== null, 'Cart must have a customer');

        // Validate address belongs to customer
        $customerId = $customer->getId();
        assert($customerId !== null, 'Customer must have an ID');
        $address = $this->addressRepository->findOneByCustomer($addressId, $customerId);
        if ($address === null) {
            throw new \InvalidArgumentException('Address not found or does not belong to customer');
        }

        // Validate cart has items
        if ($cart->getItems()->isEmpty()) {
            throw new \InvalidArgumentException('Cart is empty');
        }

        // Validate stock for all items
        $stockErrors = [];
        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getProduct();
            assert($product !== null, 'CartItem must have a product');
            $requestedQty = $cartItem->getQuantity();
            $availableStock = $product->getStock();

            if ($availableStock < $requestedQty) {
                $stockErrors[] = sprintf(
                    '%s (requested %d, available %d)',
                    $product->getName(),
                    $requestedQty,
                    $availableStock
                );
            }
        }

        if (!empty($stockErrors)) {
            throw new \InvalidArgumentException('Insufficient stock for: ' . implode(', ', $stockErrors));
        }

        // Start transaction
        $this->entityManager->beginTransaction();

        try {
            // Generate order number
            $orderNumber = $this->orderRepository->getNextOrderNumber();

            // Create frozen order address
            $orderAddress = $address->toOrderAddress();

            // Create order
            $order = new Order();
            $order->setOrderNumber($orderNumber);
            $order->setStatus(OrderStatus::NEW);
            $order->setCustomer($customer);
            $order->setOrderAddress($orderAddress);

            $totalPriceNet = '0.00';
            $totalPriceGross = '0.00';

            // Create order items and update stock
            foreach ($cart->getItems() as $cartItem) {
                $product = $cartItem->getProduct();
                assert($product !== null, 'CartItem must have a product');
                $quantity = $cartItem->getQuantity();

                // Create order item with frozen prices and product name
                $productName = $product->getName();
                assert($productName !== null, 'Product must have a name');
                $priceNet = $product->getPriceNet();
                assert($priceNet !== null, 'Product must have a net price');
                $priceGross = $product->getPriceGross();
                assert($priceGross !== null, 'Product must have a gross price');

                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setProductName($productName);
                $orderItem->setQuantity($quantity);
                $orderItem->setPriceNet($priceNet);
                $orderItem->setPriceGross($priceGross);

                $order->addItem($orderItem);

                // Update stock
                $newStock = $product->getStock() - $quantity;
                $product->setStock($newStock);

                // Calculate totals using bcmath for precision
                $itemTotalNet = bcmul($priceNet, (string) $quantity, 2);
                $itemTotalGross = bcmul($priceGross, (string) $quantity, 2);

                $totalPriceNet = bcadd($totalPriceNet, $itemTotalNet, 2);
                $totalPriceGross = bcadd($totalPriceGross, $itemTotalGross, 2);
            }

            $order->setTotalPriceNet($totalPriceNet);
            $order->setTotalPriceGross($totalPriceGross);

            // Clear cart
            $cart->clearItems();

            // Persist order
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            // Commit transaction
            $this->entityManager->commit();

            return $order;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
