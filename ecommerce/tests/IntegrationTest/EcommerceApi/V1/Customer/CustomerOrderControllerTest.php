<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Customer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsAddresses;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsCustomers;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsProducts;

class CustomerOrderControllerTest extends WebTestCase
{
    use BuildsAddresses;
    use BuildsCustomers;
    use BuildsProducts;

    private KernelBrowser $client;

    private ContainerInterface $container;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
        $entityManager = $this->container->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;
    }

    public function testGetOrdersListReturnsOnlyCustomerOrders(): void
    {
        // Arrange - Create two customers with orders
        $customer1 = $this->createCustomerWithToken('customer1-orders@example.com');
        $customer2 = $this->createCustomerWithToken('customer2-orders@example.com');

        $address1 = $this->createAddress($customer1);
        $address2 = $this->createAddress($customer2);

        $product = $this->createProduct('Test Product', 100);

        // Customer 1 places 2 orders
        $this->placeOrderForCustomer($customer1, $address1, $product, 1);
        $this->placeOrderForCustomer($customer1, $address1, $product, 2);

        // Customer 2 places 1 order
        $this->placeOrderForCustomer($customer2, $address2, $product, 1);

        // Act - Customer 1 retrieves their orders
        $this->client->request('GET', '/ecommerce/api/v1/orders', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer1->getApiToken()->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        static::assertArrayHasKey('data', $response);
        static::assertCount(2, $response['data']); // Only customer1's orders
        static::assertArrayHasKey('page', $response);
        static::assertArrayHasKey('totalPages', $response);
    }

    public function testOrdersAreSortedByCreatedAtDesc(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('sorted-orders@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Test Product', 100);

        // Place 3 orders with slight delays
        $this->placeOrderForCustomer($customer, $address, $product, 1);
        usleep(100000); // 0.1 second delay
        $this->placeOrderForCustomer($customer, $address, $product, 1);
        usleep(100000);
        $this->placeOrderForCustomer($customer, $address, $product, 1);

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/orders', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        static::assertCount(3, $response['data']);

        // Verify orders are sorted from newest to oldest by checking timestamps
        $timestamps = array_map(fn ($order) => strtotime($order['createdAt']), $response['data']);
        for ($i = 0; $i < count($timestamps) - 1; $i++) {
            static::assertGreaterThanOrEqual(
                $timestamps[$i + 1],
                $timestamps[$i],
                'Orders should be sorted DESC by createdAt'
            );
        }
    }

    public function testOrderResponseIncludesAllRequiredFields(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('order-fields@example.com');
        $address = $this->createAddress($customer, [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'street' => '123 Main St',
            'city' => 'Boston',
            'zipcode' => '02101',
            'country' => 'USA',
        ]);
        $product = $this->createProduct('Laptop', 10, '800.00', '984.00');

        $this->placeOrderForCustomer($customer, $address, $product, 2);

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/orders', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        static::assertCount(1, $response['data']);
        $order = $response['data'][0];

        // Verify order data
        static::assertArrayHasKey('id', $order);
        static::assertArrayHasKey('orderNumber', $order);
        static::assertArrayHasKey('status', $order);
        static::assertEquals('new', $order['status']);
        static::assertArrayHasKey('totalPriceNet', $order);
        static::assertArrayHasKey('totalPriceGross', $order);
        static::assertArrayHasKey('createdAt', $order);

        // Verify address snapshot (OrderAddress)
        static::assertArrayHasKey('address', $order);
        static::assertEquals('John', $order['address']['firstName']);
        static::assertEquals('Doe', $order['address']['lastName']);
        static::assertEquals('123 Main St', $order['address']['street']);
        static::assertEquals('Boston', $order['address']['city']);
        static::assertEquals('02101', $order['address']['zipcode']);
        static::assertEquals('USA', $order['address']['country']);

        // Verify order items
        static::assertArrayHasKey('items', $order);
        static::assertCount(1, $order['items']);
        $item = $order['items'][0];
        static::assertArrayHasKey('id', $item);
        static::assertArrayHasKey('productId', $item);
        static::assertArrayHasKey('productName', $item);
        static::assertEquals('Laptop', $item['productName']);
        static::assertArrayHasKey('quantity', $item);
        static::assertEquals(2, $item['quantity']);
        static::assertArrayHasKey('priceNet', $item);
        static::assertEquals('800.00', $item['priceNet']);
        static::assertArrayHasKey('priceGross', $item);
        static::assertEquals('984.00', $item['priceGross']);
    }

    public function testPaginationWorksCorrectly(): void
    {
        // Arrange - Create 25 orders
        $customer = $this->createCustomerWithToken('pagination-orders@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Test Product', 100);

        for ($i = 0; $i < 25; $i++) {
            $this->placeOrderForCustomer($customer, $address, $product, 1);
        }

        // Act - Get first page (default limit is 20)
        $this->client->request('GET', '/ecommerce/api/v1/orders', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert page 1
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        static::assertCount(20, $response['data']); // Default limit
        static::assertEquals(1, $response['page']);
        static::assertEquals(2, $response['totalPages']); // 25 orders / 20 per page = 2 pages
        static::assertNotNull($response['nextPage']);
        static::assertStringContainsString('page=2', $response['nextPage']);
        static::assertNull($response['previousPage']);

        // Act - Get second page
        $this->client->request('GET', '/ecommerce/api/v1/orders?page=2', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert page 2
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        static::assertCount(5, $response['data']); // Remaining 5 orders
        static::assertEquals(2, $response['page']);
        static::assertNull($response['nextPage']);
        static::assertNotNull($response['previousPage']);
        static::assertStringContainsString('page=1', $response['previousPage']);
    }

    public function testCustomLimitParameter(): void
    {
        // Arrange - Create 15 orders
        $customer = $this->createCustomerWithToken('limit-orders@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Test Product', 100);

        for ($i = 0; $i < 15; $i++) {
            $this->placeOrderForCustomer($customer, $address, $product, 1);
        }

        // Act - Request with limit=5
        $this->client->request('GET', '/ecommerce/api/v1/orders?limit=5', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        static::assertCount(5, $response['data']);
        static::assertEquals(1, $response['page']);
        static::assertEquals(3, $response['totalPages']); // 15 orders / 5 per page = 3 pages
    }

    public function testEmptyOrdersListForNewCustomer(): void
    {
        // Arrange - Customer with no orders
        $customer = $this->createCustomerWithToken('no-orders@example.com');

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/orders', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        static::assertArrayHasKey('data', $response);
        static::assertCount(0, $response['data']);
        static::assertEquals(1, $response['page']);
        static::assertEquals(0, $response['totalPages']);
        static::assertNull($response['nextPage']);
        static::assertNull($response['previousPage']);
    }

    public function testCannotAccessOrdersWithoutAuthentication(): void
    {
        // Act
        $this->client->request('GET', '/ecommerce/api/v1/orders');

        // Assert
        static::assertResponseStatusCodeSame(401);
    }

    public function testOrdersWithMultipleItems(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('multi-item-order@example.com');
        $address = $this->createAddress($customer);
        $product1 = $this->createProduct('Mouse', 50, '20.00', '24.60');
        $product2 = $this->createProduct('Keyboard', 30, '50.00', '61.50');

        // Add multiple items and place order
        $this->addItemToCart($customer, $product1->getId(), 2);
        $this->addItemToCart($customer, $product2->getId(), 1);
        $this->placeOrder($customer, $address->getId());

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/orders', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        static::assertCount(1, $response['data']);
        $order = $response['data'][0];

        // Verify multiple order items
        static::assertCount(2, $order['items']);
        static::assertEquals('90.00', $order['totalPriceNet']); // (2*20) + (1*50)
        static::assertEquals('110.70', $order['totalPriceGross']); // (2*24.60) + (1*61.50)
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    private function addItemToCart(Customer $customer, int $productId, int $quantity): void
    {
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $productId,
            'quantity' => $quantity,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()?->getToken(),
        ]);
    }

    private function placeOrder(Customer $customer, int $addressId): void
    {
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/order', [
            'addressId' => $addressId,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()?->getToken(),
        ]);
    }

    private function placeOrderForCustomer(Customer $customer, object $address, object $product, int $quantity): void
    {
        $this->addItemToCart($customer, $product->getId(), $quantity);
        $this->placeOrder($customer, $address->getId());
    }
}
