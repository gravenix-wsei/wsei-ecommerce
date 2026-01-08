<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Cart;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartTotalPriceCalculationTest extends WebTestCase
{
    use CartTestHelperTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testEmptyCartReturnsTotalPricesAsZero(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('empty-totals@example.com');

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertEquals('0.00', $response['totalPriceNet']);
        static::assertEquals('0.00', $response['totalPriceGross']);
    }

    public function testCartTotalPricesWithSingleItem(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('single-item-totals@example.com');
        $product = $this->createProduct('Single Product', 100, '25.50', '30.00');

        // Add product to cart
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product->getId(),
            'quantity' => 3,
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
        static::assertEquals('76.50', $response['totalPriceNet']); // 25.50 * 3
        static::assertEquals('90.00', $response['totalPriceGross']); // 30.00 * 3
    }

    public function testCartTotalPricesWithMultipleItems(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('multiple-items-totals@example.com');
        $product1 = $this->createProduct('Product A', 100, '10.00', '12.00');
        $product2 = $this->createProduct('Product B', 50, '15.50', '18.60');
        $product3 = $this->createProduct('Product C', 30, '7.25', '8.70');

        // Add products to cart
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product1->getId(),
            'quantity' => 2,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product2->getId(),
            'quantity' => 3,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product3->getId(),
            'quantity' => 5,
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
        // (10.00 * 2) + (15.50 * 3) + (7.25 * 5) = 20.00 + 46.50 + 36.25 = 102.75
        static::assertEquals('102.75', $response['totalPriceNet']);
        // (12.00 * 2) + (18.60 * 3) + (8.70 * 5) = 24.00 + 55.80 + 43.50 = 123.30
        static::assertEquals('123.30', $response['totalPriceGross']);
    }

    public function testCartItemResponseContainsUnitAndTotalPrices(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('item-prices@example.com');
        $product = $this->createProduct('Priced Product', 100, '19.99', '23.99');

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product->getId(),
            'quantity' => 4,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertEquals('19.99', $response['unitPriceNet']);
        static::assertEquals('23.99', $response['unitPriceGross']);
        static::assertEquals('79.96', $response['totalPriceNet']); // 19.99 * 4
        static::assertEquals('95.96', $response['totalPriceGross']); // 23.99 * 4
    }

    public function testCartTotalUpdatesWhenItemQuantityIncreases(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('quantity-update@example.com');
        $product = $this->createProduct('Updateable Product', 100, '12.50', '15.00');

        // Add initial quantity
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product->getId(),
            'quantity' => 2,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Add same product again to increase quantity
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product->getId(),
            'quantity' => 3,
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
        static::assertEquals('62.50', $response['totalPriceNet']); // 12.50 * 5
        static::assertEquals('75.00', $response['totalPriceGross']); // 15.00 * 5
    }

    public function testCartTotalBecomesZeroAfterClearingCart(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('clear-totals@example.com');
        $product = $this->createProduct('Product to Clear', 100, '50.00', '60.00');

        // Add product to cart
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product->getId(),
            'quantity' => 2,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Clear cart
        $this->client->request('DELETE', '/ecommerce/api/v1/cart', [], [], [
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
        static::assertEquals('0.00', $response['totalPriceNet']);
        static::assertEquals('0.00', $response['totalPriceGross']);
    }
}
