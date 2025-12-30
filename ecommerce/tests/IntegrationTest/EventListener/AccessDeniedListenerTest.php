<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EventListener;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Wsei\Ecommerce\Entity\User;

class AccessDeniedListenerTest extends WebTestCase
{
    private KernelBrowser $client;

    private ?User $testUser = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function testAccessDeniedInAdminPanelRendersCustom403Page(): void
    {
        // Arrange - Login user without product access
        $user = $this->getUserWithRoles(['ROLE_ADMIN.CATEGORY']);
        $this->client->loginUser($user);

        // Act - Try to access product section
        $this->client->request('GET', '/admin/product');

        // Assert - Should see custom 403 page with admin layout
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertSelectorExists('.error-title');
        static::assertSelectorExists('.error-message');
        static::assertSelectorExists('a[href="/admin/dashboard"]');
    }

    public function testAccessDeniedShowsAdminMenuInErrorPage(): void
    {
        // Arrange
        $user = $this->getUserWithRoles(['ROLE_ADMIN.PRODUCT']);
        $this->client->loginUser($user);

        // Act - Access forbidden settings page
        $this->client->request('GET', '/admin/settings');

        // Assert - Error page includes admin menu
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertSelectorExists('.admin-sidebar');
        static::assertSelectorExists('.admin-menu');
    }

    public function testNonAdminRoutesUseDefaultSymfonyErrorHandling(): void
    {
        // Arrange - Request non-existent public page
        // Act
        $this->client->request('GET', '/non-existent-page');

        // Assert - Should get standard Symfony 404, not custom admin error
        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        static::assertSelectorNotExists('.admin-sidebar');
    }

    public function testLoginPageIsNotAffectedByListener(): void
    {
        // Arrange - Not authenticated
        // Act - Access login page
        $this->client->request('GET', '/admin/login');

        // Assert - Should render normally
        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists('h1:contains("Access Denied")');
    }

    public function testDifferentForbiddenSectionsShowSameErrorLayout(): void
    {
        // Arrange
        $user = $this->getUserWithRoles(['ROLE_ADMIN.PRODUCT']);
        $this->client->loginUser($user);

        $forbiddenPaths = ['/admin/customer', '/admin/orders', '/admin/settings'];

        foreach ($forbiddenPaths as $path) {
            // Act
            $this->client->request('GET', $path);

            // Assert - Each should show consistent 403 error
            static::assertResponseStatusCodeSame(
                Response::HTTP_FORBIDDEN,
                sprintf('Path %s should return 403', $path)
            );
            static::assertSelectorExists('.error-title');
            static::assertSelectorExists('a[href="/admin/dashboard"]');
        }
    }

    public function testUserWithCorrectPermissionDoesNotTriggerListener(): void
    {
        // Arrange
        $user = $this->getUserWithRoles(['ROLE_ADMIN.PRODUCT']);
        $this->client->loginUser($user);

        // Act
        $this->client->request('GET', '/admin/product');

        // Assert - Should access normally, no 403
        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists('.error-title');
    }

    public function testErrorPageHasDashboardReturnLink(): void
    {
        // Arrange
        $user = $this->getUserWithRoles(['ROLE_ADMIN.PRODUCT']);
        $this->client->loginUser($user);

        // Act
        $this->client->request('GET', '/admin/settings');

        // Assert
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertSelectorExists('a.btn-primary[href="/admin/dashboard"]');
    }

    public function testAccessDeniedOnAdminSubpaths(): void
    {
        // Arrange
        $user = $this->getUserWithRoles(['ROLE_ADMIN.CUSTOMER']);
        $this->client->loginUser($user);

        // Act - Try to access product edit page
        $this->client->request('GET', '/admin/product/new');

        // Assert
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertSelectorExists('.error-title');
    }

    /**
     * @param string[] $roles
     */
    private function getUserWithRoles(array $roles): User
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Create a new test user with specific roles
        $this->testUser = new User();
        $this->testUser->setEmail('test_' . uniqid() . '@example.com');
        $this->testUser->setPassword('$2y$13$test_password_hash');
        $this->testUser->setRoles($roles);

        $entityManager->persist($this->testUser);
        $entityManager->flush();

        return $this->testUser;
    }
}
