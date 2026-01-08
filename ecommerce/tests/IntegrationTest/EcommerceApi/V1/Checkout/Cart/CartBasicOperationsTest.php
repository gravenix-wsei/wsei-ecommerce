<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Cart;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartBasicOperationsTest extends WebTestCase
{
    use CartTestHelperTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testGetEmptyCart(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('empty-cart@example.com');

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertIsArray($response['items']);
        static::assertCount(0, $response['items']);
    }

    public function testAddItemToCart(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('add-item@example.com');
        $product = $this->createProduct('Test Product', 100, '19.99');
        $payload = [
            'productId' => $product->getId(),
            'quantity' => 2,
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', $payload, [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertEquals($product->getId(), $response['product']['id']);
        static::assertEquals('Test Product', $response['product']['name']);
        static::assertEquals(2, $response['quantity']);
    }

    public function testAddSameProductIncreasesQuantity(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('increase-qty@example.com');
        $product = $this->createProduct('Product A', 50, '29.99');
        $payload = [
            'productId' => $product->getId(),
            'quantity' => 3,
        ];

        // Act - Add first time
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', $payload, [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        $firstResponse = json_decode($this->client->getResponse()->getContent(), true);
        $itemId = $firstResponse['id'];

        // Act - Add same product again
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', $payload, [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertEquals($itemId, $response['id']); // Same item ID
        static::assertEquals(6, $response['quantity']); // Quantity increased from 3 to 6
    }

    public function testGetCartWithItems(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('cart-with-items@example.com');
        $product1 = $this->createProduct('Product 1', 100, '10.00');
        $product2 = $this->createProduct('Product 2', 50, '20.00');

        // Add products to cart
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product1->getId(),
            'quantity' => 1,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product2->getId(),
            'quantity' => 2,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(2, $response['items']);
    }

    public function testRemoveItemFromCart(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('remove-item@example.com');
        $product = $this->createProduct('Product To Remove', 100, '15.00');

        // Add item to cart
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product->getId(),
            'quantity' => 1,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        $addResponse = json_decode($this->client->getResponse()->getContent(), true);
        $itemId = $addResponse['id'];

        // Act
        $this->client->request('DELETE', '/ecommerce/api/v1/cart/items/' . $itemId, [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(204);

        // Verify item is removed
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(0, $cartResponse['items']);
    }

    public function testClearCart(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('clear-cart@example.com');
        $product1 = $this->createProduct('Product X', 100, '10.00');
        $product2 = $this->createProduct('Product Y', 100, '20.00');

        // Add products to cart
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product1->getId(),
            'quantity' => 1,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product2->getId(),
            'quantity' => 2,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Act
        $this->client->request('DELETE', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(204);

        // Verify cart is empty
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(0, $cartResponse['items']);
    }
}
