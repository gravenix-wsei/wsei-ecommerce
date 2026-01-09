<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Category;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsCategories;

class CategoryControllerTest extends WebTestCase
{
    use BuildsCategories;

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
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertIsArray($response['data']);
        static::assertCount(0, $response['data']);
        static::assertEquals(1, $response['page']);
        static::assertEquals(0, $response['totalPages']);
        static::assertNull($response['nextPage']);
        static::assertNull($response['previousPage']);
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
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertIsArray($response['data']);
        static::assertCount(3, $response['data']);

        // Verify structure of each category
        foreach ($response['data'] as $category) {
            static::assertArrayHasKey('id', $category);
            static::assertArrayHasKey('name', $category);
            static::assertIsInt($category['id']);
            static::assertIsString($category['name']);
        }

        // Verify specific categories exist
        $categoryNames = array_column($response['data'], 'name');
        static::assertContains('Electronics', $categoryNames);
        static::assertContains('Books', $categoryNames);
        static::assertContains('Clothing', $categoryNames);
    }

    public function testCategoriesEndpointIsPublic(): void
    {
        // Arrange
        $this->createCategory('Public Category');

        // Act - No authentication header provided
        $this->client->request('GET', '/ecommerce/api/v1/categories');

        // Assert - Should still work without authentication
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(1, $response['data']);
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
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(10, $response['data']);
        static::assertEquals(1, $response['page']);
        static::assertEquals(3, $response['totalPages']);
        static::assertStringContainsString('page=2', $response['nextPage']);
        static::assertNull($response['previousPage']);
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
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(10, $response['data']);
        static::assertEquals(2, $response['page']);
        static::assertEquals(3, $response['totalPages']);
        static::assertStringContainsString('page=3', $response['nextPage']);
        static::assertStringContainsString('page=1', $response['previousPage']);
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
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(5, $response['data']); // Only 5 items on last page
        static::assertEquals(3, $response['page']);
        static::assertEquals(3, $response['totalPages']);
        static::assertNull($response['nextPage']);
        static::assertStringContainsString('page=2', $response['previousPage']);
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
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(5, $response['data']);
        static::assertEquals(6, $response['totalPages']); // 30 / 5 = 6 pages
    }

    public function testCategoriesPaginationInvalidPage(): void
    {
        // Arrange
        $this->createCategory('Category');

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/categories?page=999');

        // Assert - Should return empty data but still be successful
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(0, $response['data']);
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
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(15, $response['data']);
        static::assertEquals(1, $response['page']);
        static::assertEquals(1, $response['totalPages']);
    }

    public function testCategoriesResponseStructure(): void
    {
        // Arrange
        $category = $this->createCategory('Test Category');

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/categories');

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Check top-level structure
        static::assertArrayHasKey('data', $response);
        static::assertArrayHasKey('page', $response);
        static::assertArrayHasKey('totalPages', $response);
        static::assertArrayHasKey('nextPage', $response);
        static::assertArrayHasKey('previousPage', $response);

        // Check data structure
        static::assertCount(1, $response['data']);
        $firstCategory = $response['data'][0];
        static::assertArrayHasKey('id', $firstCategory);
        static::assertArrayHasKey('name', $firstCategory);
        static::assertSame('Category', $firstCategory['apiDescription']);
        static::assertEquals($category->getId(), $firstCategory['id']);
        static::assertEquals('Test Category', $firstCategory['name']);

        // Verify no extra fields are exposed
        static::assertCount(3, $firstCategory);
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
        static::assertEquals($response1['data'], $response2['data']);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get(EntityManagerInterface::class);
    }
}
