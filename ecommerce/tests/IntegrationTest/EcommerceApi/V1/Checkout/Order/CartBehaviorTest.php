<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Order;

class CartBehaviorTest extends AbstractOrderPlacementTest
{
    public function testCartIsClearedAfterSuccessfulOrder(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('cart-cleared@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Product Clear', 20, );

        $this->addItemToCart($customer, $product->getId(), 3);

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        $this->assertResponseIsSuccessful();

        // Verify cart is empty
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], $this->getAuthHeaders($customer));
        $cartResponse = $this->getJsonResponse();
        $this->assertCount(0, $cartResponse['items']);
    }

    public function testCartNotClearedWhenOrderFails(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('cart-preserved@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Product Fail', 5, );

        $this->addItemToCart($customer, $product->getId(), 10); // Exceeds stock

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        $this->assertResponseStatusCodeSame(400);

        // Verify cart is NOT empty (preserved on failure)
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], $this->getAuthHeaders($customer));
        $cartResponse = $this->getJsonResponse();
        $this->assertCount(1, $cartResponse['items']);
        $this->assertEquals(10, $cartResponse['items'][0]['quantity']);
    }

    public function testMultipleOrdersCanBePlacedFromSameCustomer(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('multiple-orders@example.com');
        $address = $this->createAddress($customer);
        $product1 = $this->createProduct('Product Order1', 50, );
        $product2 = $this->createProduct('Product Order2', 50, '20.00', '24.60');

        // Act & Assert - First order
        $this->addItemToCart($customer, $product1->getId(), 2);
        $this->placeOrder($customer, $address->getId());
        $this->assertResponseIsSuccessful();
        $order1Response = json_decode($this->client->getResponse()->getContent(), true);

        // Act & Assert - Second order
        $this->addItemToCart($customer, $product2->getId(), 3);
        $this->placeOrder($customer, $address->getId());
        $this->assertResponseIsSuccessful();
        $order2Response = json_decode($this->client->getResponse()->getContent(), true);

        // Verify both orders exist with different IDs
        $this->assertNotEquals($order1Response['id'], $order2Response['id']);
        $this->assertNotEquals($order1Response['orderNumber'], $order2Response['orderNumber']);
    }

    public function testCustomersOrdersAreIsolated(): void
    {
        // Arrange
        $customer1 = $this->createCustomerWithToken('isolated1@example.com');
        $customer2 = $this->createCustomerWithToken('isolated2@example.com');

        $address1 = $this->createAddress($customer1);
        $address2 = $this->createAddress($customer2);

        $product = $this->createProduct('Shared Product Order', 100, '15.00', '18.45');

        // Customer 1 places order
        $this->addItemToCart($customer1, $product->getId(), 5);
        $this->placeOrder($customer1, $address1->getId());
        $this->assertResponseIsSuccessful();

        // Customer 2's cart should be empty
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], $this->getAuthHeaders($customer2));
        $cart2Response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $cart2Response['items']);

        // Customer 2 can place their own order
        $this->addItemToCart($customer2, $product->getId(), 3);
        $this->placeOrder($customer2, $address2->getId());
        $this->assertResponseIsSuccessful();

        // Verify stock decreased by both orders
        // Re-fetch the product from database to get updated stock
        $updatedProduct = $this->entityManager->find($product::class, $product->getId());
        $this->assertEquals(92, $updatedProduct?->getStock()); // 100 - 5 - 3
    }

    public function testOrderCanBePlacedWithMultipleAddressesOverTime(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('multi-address@example.com');
        $address1 = $this->createAddress($customer, [
            'street' => 'Home Address',
            'city' => 'City A',
        ]);
        $address2 = $this->createAddress($customer, [
            'street' => 'Work Address',
            'city' => 'City B',
        ]);

        $product = $this->createProduct('Product Multi-Addr', 50, );

        // Order to first address
        $this->addItemToCart($customer, $product->getId(), 2);
        $this->placeOrder($customer, $address1->getId());
        $this->assertResponseIsSuccessful();
        $order1Response = json_decode($this->client->getResponse()->getContent(), true);

        // Order to second address
        $this->addItemToCart($customer, $product->getId(), 3);
        $this->placeOrder($customer, $address2->getId());
        $this->assertResponseIsSuccessful();
        $order2Response = json_decode($this->client->getResponse()->getContent(), true);

        // Verify different addresses were used
        $this->assertEquals('Home Address', $order1Response['address']['street']);
        $this->assertEquals('City A', $order1Response['address']['city']);
        $this->assertEquals('Work Address', $order2Response['address']['street']);
        $this->assertEquals('City B', $order2Response['address']['city']);
    }
}
