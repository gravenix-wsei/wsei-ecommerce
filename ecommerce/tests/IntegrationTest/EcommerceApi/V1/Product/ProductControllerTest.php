<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Product;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wsei\Ecommerce\Entity\Category;
use Wsei\Ecommerce\Entity\Product;

class ProductControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
    }

    public function testSearchProductsEmpty(): void
    {
        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', []);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertIsArray($response['data']);
        static::assertCount(0, $response['data']);
        static::assertEquals(1, $response['page']);
        static::assertEquals(0, $response['totalPages']);
        static::assertEquals('ProductList', $response['apiDescription']);
    }

    public function testSearchProductsReturnsAllProducts(): void
    {
        // Arrange
        $category = $this->createCategory('Electronics');
        $product1 = $this->createProduct('Laptop', $category);
        $product2 = $this->createProduct('Mouse', null);
        $product3 = $this->createProduct('Keyboard', $category);

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', []);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertIsArray($response['data']);
        static::assertCount(3, $response['data']);

        // Verify product structure
        foreach ($response['data'] as $product) {
            static::assertArrayHasKey('id', $product);
            static::assertArrayHasKey('name', $product);
            static::assertArrayHasKey('description', $product);
            static::assertArrayHasKey('stock', $product);
            static::assertArrayHasKey('priceNet', $product);
            static::assertArrayHasKey('priceGross', $product);
            static::assertArrayHasKey('category', $product);
            static::assertArrayHasKey('apiDescription', $product);
            static::assertEquals('Product', $product['apiDescription']);
        }

        // Verify product names
        $productNames = array_column($response['data'], 'name');
        static::assertContains('Laptop', $productNames);
        static::assertContains('Mouse', $productNames);
        static::assertContains('Keyboard', $productNames);
    }

    public function testSearchProductsByCategory(): void
    {
        // Arrange
        $electronicsCategory = $this->createCategory('Electronics');
        $booksCategory = $this->createCategory('Books');

        $product1 = $this->createProduct('Laptop', $electronicsCategory);
        $product2 = $this->createProduct('Book', $booksCategory);
        $product3 = $this->createProduct('Mouse', $electronicsCategory);

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', [
            'categoryId' => $electronicsCategory->getId(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertIsArray($response['data']);
        static::assertCount(2, $response['data']);

        // Verify only electronics products returned
        $productNames = array_column($response['data'], 'name');
        static::assertContains('Laptop', $productNames);
        static::assertContains('Mouse', $productNames);
        static::assertNotContains('Book', $productNames);

        // Verify category is included in response
        foreach ($response['data'] as $product) {
            static::assertIsArray($product['category']);
            static::assertEquals('Electronics', $product['category']['name']);
        }

        $productNames = array_column($response['data'], 'name');
        static::assertArrayNotHasKey('Book', $productNames);
    }

    public function testSearchProductsWithNonExistentCategory(): void
    {
        // Arrange
        $category = $this->createCategory('Electronics');
        $this->createProduct('Laptop', $category);

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', [
            'categoryId' => 99999,
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertIsArray($response['data']);
        static::assertCount(0, $response['data']);
    }

    public function testSearchProductsWithoutCategory(): void
    {
        // Arrange
        $category = $this->createCategory('Electronics');
        $productWithCategory = $this->createProduct('Laptop', $category);
        $productWithoutCategory = $this->createProduct('Generic Item', null);

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', []);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(2, $response['data']);

        // Find product without category
        $genericProduct = null;
        foreach ($response['data'] as $product) {
            if ($product['name'] === 'Generic Item') {
                $genericProduct = $product;
                break;
            }
        }

        static::assertNotNull($genericProduct);
        static::assertNull($genericProduct['category']);
    }

    public function testSearchProductsPaginationFirstPage(): void
    {
        // Arrange - Create 25 products
        $category = $this->createCategory('Test');
        for ($i = 1; $i <= 25; $i++) {
            $this->createProduct('Product ' . $i, $category);
        }

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', [
            'page' => 1,
            'limit' => 10,
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(10, $response['data']);
        static::assertEquals(1, $response['page']);
        static::assertEquals(3, $response['totalPages']);
    }

    public function testSearchProductsPaginationSecondPage(): void
    {
        // Arrange - Create 25 products
        $category = $this->createCategory('Test');
        for ($i = 1; $i <= 25; $i++) {
            $this->createProduct('Product ' . $i, $category);
        }

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', [
            'page' => 2,
            'limit' => 10,
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(10, $response['data']);
        static::assertEquals(2, $response['page']);
        static::assertEquals(3, $response['totalPages']);
    }

    public function testSearchProductsPaginationLastPage(): void
    {
        // Arrange - Create 25 products
        $category = $this->createCategory('Test');
        for ($i = 1; $i <= 25; $i++) {
            $this->createProduct('Product ' . $i, $category);
        }

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', [
            'page' => 3,
            'limit' => 10,
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(5, $response['data']);
        static::assertEquals(3, $response['page']);
        static::assertEquals(3, $response['totalPages']);
    }

    public function testSearchProductsPaginationWithCategoryFilter(): void
    {
        // Arrange
        $electronics = $this->createCategory('Electronics');
        $books = $this->createCategory('Books');

        for ($i = 1; $i <= 15; $i++) {
            $this->createProduct('Laptop ' . $i, $electronics);
        }
        for ($i = 1; $i <= 5; $i++) {
            $this->createProduct('Book ' . $i, $books);
        }

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', [
            'categoryId' => $electronics->getId(),
            'page' => 1,
            'limit' => 10,
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(10, $response['data']);
        static::assertEquals(1, $response['page']);
        static::assertEquals(2, $response['totalPages']);
    }

    public function testSearchProductsEndpointIsPublic(): void
    {
        // Arrange
        $category = $this->createCategory('Public Category');
        $this->createProduct('Public Product', $category);

        // Act - No authentication header provided
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', []);

        // Assert - Should work without authentication
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(1, $response['data']);
    }

    public function testSearchProductsDefaultPagination(): void
    {
        // Arrange - Create 30 products
        $category = $this->createCategory('Test');
        for ($i = 1; $i <= 30; $i++) {
            $this->createProduct('Product ' . $i, $category);
        }

        // Act - No pagination params
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', []);

        // Assert - Should use defaults: page=1, limit=20
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(20, $response['data']);
        static::assertEquals(1, $response['page']);
        static::assertEquals(2, $response['totalPages']);
    }

    public function testSearchProductsValidatesPayload(): void
    {
        // Act - Invalid categoryId (negative)
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', [
            'categoryId' => -1,
        ]);

        // Assert
        static::assertResponseStatusCodeSame(422);
    }

    public function testSearchProductsProductStructure(): void
    {
        // Arrange
        $category = $this->createCategory('Electronics');
        $product = $this->createProduct('Test Product', $category, 50, '99.99', '122.99');

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', []);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(1, $response['data']);

        $productData = $response['data'][0];
        static::assertEquals('Test Product', $productData['name']);
        static::assertEquals('Test description for Test Product', $productData['description']);
        static::assertEquals(50, $productData['stock']);
        static::assertEquals('99.99', $productData['priceNet']);
        static::assertEquals('122.99', $productData['priceGross']);
        static::assertIsArray($productData['category']);
        static::assertEquals('Electronics', $productData['category']['name']);
    }

    private function createCategory(string $name): Category
    {
        $entityManager = $this->container->get(EntityManagerInterface::class);

        $category = new Category();
        $category->setName($name);

        $entityManager->persist($category);
        $entityManager->flush();

        return $category;
    }

    private function createProduct(
        string $name,
        ?Category $category,
        int $stock = 100,
        string $priceNet = '10.00',
        string $priceGross = '12.30'
    ): Product {
        $entityManager = $this->container->get(EntityManagerInterface::class);

        $product = new Product();
        $product->setName($name);
        $product->setDescription('Test description for ' . $name);
        $product->setStock($stock);
        $product->setPriceNet($priceNet);
        $product->setPriceGross($priceGross);
        $product->setCategory($category);

        $entityManager->persist($product);
        $entityManager->flush();

        return $product;
    }
}
