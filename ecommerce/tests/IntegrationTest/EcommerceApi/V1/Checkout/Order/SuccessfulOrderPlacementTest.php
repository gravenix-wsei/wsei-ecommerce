<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Order;

use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatus;

class SuccessfulOrderPlacementTest extends AbstractOrderPlacementTest
{
    public function testPlaceOrderWithSingleItem(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('order-single@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Laptop', 10, '800.00', '984.00');

        $this->addItemToCart($customer, $product->getId(), 2);

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('orderNumber', $response);
        $this->assertEquals('new', $response['status']);
        $this->assertEquals('1600.00', $response['totalPriceNet']);
        $this->assertEquals('1968.00', $response['totalPriceGross']);
        $this->assertArrayHasKey('createdAt', $response);
        $this->assertCount(1, $response['items']);

        $item = $response['items'][0];
        $this->assertEquals($product->getId(), $item['productId']);
        $this->assertEquals('Laptop', $item['productName']);
        $this->assertEquals(2, $item['quantity']);
        $this->assertEquals('800.00', $item['priceNet']);
        $this->assertEquals('984.00', $item['priceGross']);
    }

    public function testPlaceOrderWithMultipleItems(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('order-multiple@example.com');
        $address = $this->createAddress($customer);
        $product1 = $this->createProduct('Mouse', 50, '20.00', '24.60');
        $product2 = $this->createProduct('Keyboard', 30, '50.00', '61.50');
        $product3 = $this->createProduct('Monitor', 15, '200.00', '246.00');

        $this->addItemToCart($customer, $product1->getId(), 3);
        $this->addItemToCart($customer, $product2->getId(), 1);
        $this->addItemToCart($customer, $product3->getId(), 2);

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(3, $response['items']);
        $this->assertEquals('510.00', $response['totalPriceNet']); // (3*20) + (1*50) + (2*200)
        $this->assertEquals('627.30', $response['totalPriceGross']); // (3*24.60) + (1*61.50) + (2*246)
    }

    public function testOrderNumberFormatAndIncrement(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('order-numbers@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Product A');

        // Act & Assert - First order
        $this->addItemToCart($customer, $product->getId(), 1);
        $this->placeOrder($customer, $address->getId());
        $this->assertResponseIsSuccessful();
        $response1 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $response1['orderNumber']);

        // Act & Assert - Second order
        $this->addItemToCart($customer, $product->getId(), 1);
        $this->placeOrder($customer, $address->getId());
        $this->assertResponseIsSuccessful();
        $response2 = json_decode($this->client->getResponse()->getContent(), true);

        // Verify order number incremented
        $orderNum1 = (int) $response1['orderNumber'];
        $orderNum2 = (int) $response2['orderNumber'];
        $this->assertEquals($orderNum1 + 1, $orderNum2);
    }

    public function testOrderCreatedWithNewStatus(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('order-status@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Product B', 10, '15.00', '18.45');

        $this->addItemToCart($customer, $product->getId(), 1);

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(OrderStatus::NEW->value, $response['status']);

        // Verify in database
        $order = $this->entityManager->getRepository(Order::class)->find($response['id']);
        $this->assertSame(OrderStatus::NEW, $order?->getStatus());
    }

    public function testOrderAddressIsFrozen(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('frozen-address@example.com');
        $address = $this->createAddress($customer, [
            'firstName' => 'Original',
            'lastName' => 'Name',
            'street' => 'Original Street',
            'city' => 'Original City',
        ]);
        $product = $this->createProduct('Product C', 10);

        $this->addItemToCart($customer, $product->getId(), 1);

        // Act
        $this->placeOrder($customer, $address->getId());
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Modify original address
        $address->setFirstName('Modified');
        $address->setLastName('Changed');
        $address->setStreet('New Street');
        $address->setCity('New City');
        $this->entityManager->flush();

        // Assert - Order address should remain unchanged
        $this->assertEquals('Original', $response['address']['firstName']);
        $this->assertEquals('Name', $response['address']['lastName']);
        $this->assertEquals('Original Street', $response['address']['street']);
        $this->assertEquals('Original City', $response['address']['city']);

        // Verify in database
        $order = $this->entityManager->getRepository(Order::class)->find($response['id']);
        $orderAddress = $order?->getOrderAddress();
        $this->assertEquals('Original', $orderAddress?->getFirstName());
        $this->assertEquals('Original Street', $orderAddress?->getStreet());
    }
}
