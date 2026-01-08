<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Cart;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartValidationTest extends WebTestCase
{
    use CartTestHelperTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCannotAddNonExistentProduct(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('nonexistent@example.com');
        $payload = [
            'productId' => 999999,
            'quantity' => 1,
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', $payload, [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertStringContainsString('not found', strtolower($response['message']));
    }

    public function testCannotAddProductWithZeroStock(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('zero-stock@example.com');
        $product = $this->createProduct('Out of Stock Product', 0, '10.00');
        $payload = [
            'productId' => $product->getId(),
            'quantity' => 1,
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', $payload, [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertStringContainsString('stock', strtolower($response['message']));
    }

    public function testCannotRemoveItemFromAnotherCustomerCart(): void
    {
        // Arrange
        $customer1 = $this->createCustomerWithToken('customer1@example.com');
        $customer2 = $this->createCustomerWithToken('customer2@example.com');
        $product = $this->createProduct('Product Z', 100, '10.00');

        // Customer 1 adds item
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product->getId(),
            'quantity' => 1,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer1->getApiToken()
                ->getToken(),
        ]);

        $addResponse = json_decode($this->client->getResponse()->getContent(), true);
        $itemId = $addResponse['id'];

        // Act - Customer 2 tries to remove customer 1's item
        $this->client->request('DELETE', '/ecommerce/api/v1/cart/items/' . $itemId, [], [], [
            'HTTP_wsei-ecommerce-token' => $customer2->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(404);
    }

    public function testValidationErrorsForInvalidPayload(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('validation@example.com');
        $payload = [
            'productId' => -1,
            'quantity' => 0,
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', $payload, [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(422);
    }
}
