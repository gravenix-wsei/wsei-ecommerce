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
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response['data']);
        $this->assertCount(0, $response['data']);
        $this->assertEquals(1, $response['page']);
        $this->assertEquals(0, $response['totalPages']);
        $this->assertEquals('ProductList', $response['apiDescription']);
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
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response['data']);
        $this->assertCount(3, $response['data']);

        // Verify product structure
        foreach ($response['data'] as $product) {
            $this->assertArrayHasKey('id', $product);
            $this->assertArrayHasKey('name', $product);
            $this->assertArrayHasKey('description', $product);
            $this->assertArrayHasKey('stock', $product);
            $this->assertArrayHasKey('priceNet', $product);
            $this->assertArrayHasKey('priceGross', $product);
            $this->assertArrayHasKey('category', $product);
            $this->assertArrayHasKey('apiDescription', $product);
            $this->assertEquals('Product', $product['apiDescription']);
        }

        // Verify product names
        $productNames = array_column($response['data'], 'name');
        $this->assertContains('Laptop', $productNames);
        $this->assertContains('Mouse', $productNames);
        $this->assertContains('Keyboard', $productNames);
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
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response['data']);
        $this->assertCount(2, $response['data']);

        // Verify only electronics products returned
        $productNames = array_column($response['data'], 'name');
        $this->assertContains('Laptop', $productNames);
        $this->assertContains('Mouse', $productNames);
        $this->assertNotContains('Book', $productNames);

        // Verify category is included in response
        foreach ($response['data'] as $product) {
            $this->assertIsArray($product['category']);
            $this->assertEquals('Electronics', $product['category']['name']);
            $this->assertArrayNotHasKey('apiDescription', $product['category']);
        }
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
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response['data']);
        $this->assertCount(0, $response['data']);
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
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $response['data']);

        // Find product without category
        $genericProduct = null;
        foreach ($response['data'] as $product) {
            if ($product['name'] === 'Generic Item') {
                $genericProduct = $product;
                break;
            }
        }

        $this->assertNotNull($genericProduct);
        $this->assertNull($genericProduct['category']);
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
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(10, $response['data']);
        $this->assertEquals(1, $response['page']);
        $this->assertEquals(3, $response['totalPages']);
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
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(10, $response['data']);
        $this->assertEquals(2, $response['page']);
        $this->assertEquals(3, $response['totalPages']);
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
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(5, $response['data']);
        $this->assertEquals(3, $response['page']);
        $this->assertEquals(3, $response['totalPages']);
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
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(10, $response['data']);
        $this->assertEquals(1, $response['page']);
        $this->assertEquals(2, $response['totalPages']);
    }

    public function testSearchProductsEndpointIsPublic(): void
    {
        // Arrange
        $category = $this->createCategory('Public Category');
        $this->createProduct('Public Product', $category);

        // Act - No authentication header provided
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', []);

        // Assert - Should work without authentication
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['data']);
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
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(20, $response['data']);
        $this->assertEquals(1, $response['page']);
        $this->assertEquals(2, $response['totalPages']);
    }

    public function testSearchProductsValidatesPayload(): void
    {
        // Act - Invalid categoryId (negative)
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', [
            'categoryId' => -1,
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(422);
    }

    public function testSearchProductsProductStructure(): void
    {
        // Arrange
        $category = $this->createCategory('Electronics');
        $product = $this->createProduct('Test Product', $category, 50, '99.99', '122.99');

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/products/search', []);

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['data']);

        $productData = $response['data'][0];
        $this->assertEquals('Test Product', $productData['name']);
        $this->assertEquals('Test description for Test Product', $productData['description']);
        $this->assertEquals(50, $productData['stock']);
        $this->assertEquals('99.99', $productData['priceNet']);
        $this->assertEquals('122.99', $productData['priceGross']);
        $this->assertIsArray($productData['category']);
        $this->assertEquals('Electronics', $productData['category']['name']);
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
