<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Category;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wsei\Ecommerce\Entity\Category;

class CategoryControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
    }

    public function testGetCategoriesListEmpty(): void
    {
        // Act
        $this->client->request('GET', '/ecommerce/api/v1/categories');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response['data']);
        $this->assertCount(0, $response['data']);
        $this->assertEquals(1, $response['page']);
        $this->assertEquals(0, $response['totalPages']);
        $this->assertNull($response['nextPage']);
        $this->assertNull($response['previousPage']);
    }

    public function testGetCategoriesListWithData(): void
    {
        // Arrange
        $category1 = $this->createCategory('Electronics');
        $category2 = $this->createCategory('Books');
        $category3 = $this->createCategory('Clothing');

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/categories');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response['data']);
        $this->assertCount(3, $response['data']);

        // Verify structure of each category
        foreach ($response['data'] as $category) {
            $this->assertArrayHasKey('id', $category);
            $this->assertArrayHasKey('name', $category);
            $this->assertIsInt($category['id']);
            $this->assertIsString($category['name']);
        }

        // Verify specific categories exist
        $categoryNames = array_column($response['data'], 'name');
        $this->assertContains('Electronics', $categoryNames);
        $this->assertContains('Books', $categoryNames);
        $this->assertContains('Clothing', $categoryNames);
    }

    public function testCategoriesEndpointIsPublic(): void
    {
        // Arrange
        $this->createCategory('Public Category');

        // Act - No authentication header provided
        $this->client->request('GET', '/ecommerce/api/v1/categories');

        // Assert - Should still work without authentication
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['data']);
    }

    public function testCategoriesPaginationFirstPage(): void
    {
        // Arrange - Create 25 categories
        for ($i = 1; $i <= 25; $i++) {
            $this->createCategory('Category ' . $i);
        }

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/categories?page=1&limit=10');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(10, $response['data']);
        $this->assertEquals(1, $response['page']);
        $this->assertEquals(3, $response['totalPages']);
        $this->assertStringContainsString('page=2', $response['nextPage']);
        $this->assertNull($response['previousPage']);
    }

    public function testCategoriesPaginationMiddlePage(): void
    {
        // Arrange - Create 25 categories
        for ($i = 1; $i <= 25; $i++) {
            $this->createCategory('Category ' . $i);
        }

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/categories?page=2&limit=10');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(10, $response['data']);
        $this->assertEquals(2, $response['page']);
        $this->assertEquals(3, $response['totalPages']);
        $this->assertStringContainsString('page=3', $response['nextPage']);
        $this->assertStringContainsString('page=1', $response['previousPage']);
    }

    public function testCategoriesPaginationLastPage(): void
    {
        // Arrange - Create 25 categories
        for ($i = 1; $i <= 25; $i++) {
            $this->createCategory('Category ' . $i);
        }

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/categories?page=3&limit=10');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(5, $response['data']); // Only 5 items on last page
        $this->assertEquals(3, $response['page']);
        $this->assertEquals(3, $response['totalPages']);
        $this->assertNull($response['nextPage']);
        $this->assertStringContainsString('page=2', $response['previousPage']);
    }

    public function testCategoriesPaginationCustomLimit(): void
    {
        // Arrange - Create 30 categories
        for ($i = 1; $i <= 30; $i++) {
            $this->createCategory('Category ' . $i);
        }

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/categories?limit=5');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(5, $response['data']);
        $this->assertEquals(6, $response['totalPages']); // 30 / 5 = 6 pages
    }

    public function testCategoriesPaginationInvalidPage(): void
    {
        // Arrange
        $this->createCategory('Category');

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/categories?page=999');

        // Assert - Should return empty data but still be successful
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $response['data']);
    }

    public function testCategoriesPaginationDefaultValues(): void
    {
        // Arrange - Create 15 categories
        for ($i = 1; $i <= 15; $i++) {
            $this->createCategory('Category ' . $i);
        }

        // Act - No pagination params provided
        $this->client->request('GET', '/ecommerce/api/v1/categories');

        // Assert - Should use default limit (probably 20)
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(15, $response['data']);
        $this->assertEquals(1, $response['page']);
        $this->assertEquals(1, $response['totalPages']);
    }

    public function testCategoriesResponseStructure(): void
    {
        // Arrange
        $category = $this->createCategory('Test Category');

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/categories');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Check top-level structure
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('page', $response);
        $this->assertArrayHasKey('totalPages', $response);
        $this->assertArrayHasKey('nextPage', $response);
        $this->assertArrayHasKey('previousPage', $response);

        // Check data structure
        $this->assertCount(1, $response['data']);
        $firstCategory = $response['data'][0];
        $this->assertArrayHasKey('id', $firstCategory);
        $this->assertArrayHasKey('name', $firstCategory);
        $this->assertSame('Category', $firstCategory['apiDescription']);
        $this->assertEquals($category->getId(), $firstCategory['id']);
        $this->assertEquals('Test Category', $firstCategory['name']);

        // Verify no extra fields are exposed
        $this->assertCount(3, $firstCategory);
    }

    public function testCategoriesOrderConsistency(): void
    {
        // Arrange
        $category1 = $this->createCategory('Zebra');
        $category2 = $this->createCategory('Apple');
        $category3 = $this->createCategory('Mango');

        // Act - Request twice
        $this->client->request('GET', '/ecommerce/api/v1/categories');
        $response1 = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('GET', '/ecommerce/api/v1/categories');
        $response2 = json_decode($this->client->getResponse()->getContent(), true);

        // Assert - Order should be consistent
        $this->assertEquals($response1['data'], $response2['data']);
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
}
