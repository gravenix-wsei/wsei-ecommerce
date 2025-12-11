<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Wsei\Ecommerce\Entity\ApiToken;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Entity\Product;
use Wsei\Ecommerce\Repository\CartRepository;

class CartControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
    }

    public function testGetEmptyCart(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('empty-cart@example.com');

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response['items']);
        $this->assertCount(0, $response['items']);
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
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($product->getId(), $response['product']['id']);
        $this->assertEquals('Test Product', $response['product']['name']);
        $this->assertEquals(2, $response['quantity']);
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
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        $firstResponse = json_decode($this->client->getResponse()->getContent(), true);
        $itemId = $firstResponse['id'];

        // Act - Add same product again
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', $payload, [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($itemId, $response['id']); // Same item ID
        $this->assertEquals(6, $response['quantity']); // Quantity increased from 3 to 6
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
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product2->getId(),
            'quantity' => 2,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $response['items']);
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
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        $addResponse = json_decode($this->client->getResponse()->getContent(), true);
        $itemId = $addResponse['id'];

        // Act
        $this->client->request('DELETE', '/ecommerce/api/v1/cart/items/' . $itemId, [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(204);

        // Verify item is removed
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $cartResponse['items']);
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
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $product2->getId(),
            'quantity' => 2,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Act
        $this->client->request('DELETE', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(204);

        // Verify cart is empty
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $cartResponse['items']);
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
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('not found', strtolower($response['message']));
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
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('stock', strtolower($response['message']));
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
            'HTTP_wsei-ecommerce-token' => $customer1->getApiToken()->getToken(),
        ]);

        $addResponse = json_decode($this->client->getResponse()->getContent(), true);
        $itemId = $addResponse['id'];

        // Act - Customer 2 tries to remove customer 1's item
        $this->client->request('DELETE', '/ecommerce/api/v1/cart/items/' . $itemId, [], [], [
            'HTTP_wsei-ecommerce-token' => $customer2->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(404);
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
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(422);
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
            'HTTP_wsei-ecommerce-token' => $customer1->getApiToken()->getToken(),
        ]);

        // Customer 2 gets their cart
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer2->getApiToken()->getToken(),
        ]);

        // Assert - Customer 2's cart should be empty
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $response['items']);

        // Customer 1's cart should have the item
        $this->client->request('GET', '/ecommerce/api/v1/cart', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer1->getApiToken()->getToken(),
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['items']);
        $this->assertEquals(5, $response['items'][0]['quantity']);
    }

    private function createCustomerWithToken(string $email): Customer
    {
        $entityManager = $this->container->get(EntityManagerInterface::class);
        $passwordHasher = $this->container->get(UserPasswordHasherInterface::class);

        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setFirstName('Test');
        $customer->setLastName('User');
        $customer->setPassword($passwordHasher->hashPassword($customer, 'password123'));

        $apiToken = new ApiToken();
        $apiToken->setToken(ApiToken::generate());
        $customer->setApiToken($apiToken);

        $entityManager->persist($customer);
        $entityManager->persist($apiToken);
        $entityManager->flush();

        return $customer;
    }

    private function createProduct(string $name, int $stock, string $price): Product
    {
        $entityManager = $this->container->get(EntityManagerInterface::class);

        $product = new Product();
        $product->setName($name);
        $product->setDescription('Test description for ' . $name);
        $product->setStock($stock);
        $product->setPriceNet($price);
        $product->setPriceGross($price);

        $entityManager->persist($product);
        $entityManager->flush();

        return $product;
    }
}

