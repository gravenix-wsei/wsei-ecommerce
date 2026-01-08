<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Cart;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartIsolationTest extends WebTestCase
{
    use CartTestHelperTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testEachCustomerHasOwnActiveCart(): void
    {
        // Arrange
        $customer1 = $this->createCustomerWithToken('isolated1@example.com');
        $customer2 = $this->createCustomerWithToken('isolated2@example.com');
        $product = $this->createProduct('Shared Product', 100, '10.00');

        // Act - Customer 1 adds item
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product->getId(),
            'quantity' => 5,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer1->getApiToken()
                ->getToken(),
        ]);

        // Customer 2 gets their cart
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer2->getApiToken()
                ->getToken(),
        ]);

        // Assert - Customer 2's cart should be empty
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(0, $response['items']);

        // Customer 1's cart should have the item
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer1->getApiToken()
                ->getToken(),
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(1, $response['items']);
        static::assertEquals(5, $response['items'][0]['quantity']);
    }
}
