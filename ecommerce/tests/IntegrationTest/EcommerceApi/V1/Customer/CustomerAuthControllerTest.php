<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Customer;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wsei\Ecommerce\Repository\ApiTokenRepository;
use Wsei\Ecommerce\Repository\CustomerRepository;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsCustomers;

class CustomerAuthControllerTest extends WebTestCase
{
    use BuildsCustomers;

    private KernelBrowser $client;

    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
    }

    public function testLoginSuccessfully(): void
    {
        // Arrange
        $email = 'login-test@example.com';
        $password = 'password123';
        $this->createCustomer(email: $email, password: $password);

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/login', [
            'email' => $email,
            'password' => $password,
        ]);

        // Assert
        static::assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('token', $response);
        static::assertArrayHasKey('expiresAt', $response);
        static::assertNotEmpty($response['token']);

        $customerRepository = $this->container->get(CustomerRepository::class);
        $customerFromDb = $customerRepository->findOneBy([
            'email' => $email,
        ]);
        static::assertNotNull($customerFromDb);
        static::assertNotNull($customerFromDb->getApiToken());
        static::assertEquals($response['token'], $customerFromDb->getApiToken()->getToken());
    }

    public function testLoginWithExistingTokenExtendsExpiration(): void
    {
        // Arrange
        $email = 'login-extend@example.com';
        $password = 'password123';
        $customer = $this->createCustomerWithToken(email: $email);

        $entityManager = $this->container->get(EntityManagerInterface::class);
        $apiToken = $customer->getApiToken();
        $oldExpiration = new \DateTime('-5 minutes');
        $apiToken->setExpiresAt($oldExpiration);
        $entityManager->flush();

        $originalToken = $apiToken->getToken();

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/login', [
            'email' => $email,
            'password' => $password,
        ]);

        // Assert
        static::assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        static::assertEquals($originalToken, $response['token']);

        $customerRepository = $this->container->get(CustomerRepository::class);
        $customerFromDb = $customerRepository->findOneBy([
            'email' => $email,
        ]);
        static::assertGreaterThan($oldExpiration, $customerFromDb->getApiToken()->getExpiresAt());
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('invalidLoginDataProvider')]
    public function testLoginWithInvalidData(array $payload, int $expectedStatusCode, string $expectedErrorCode): void
    {
        // Arrange
        $this->createCustomer(email: 'existing@example.com', password: 'correctpassword');

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/login', $payload);

        // Assert
        static::assertResponseStatusCodeSame($expectedStatusCode);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('errorCode', $response);
        static::assertEquals($expectedErrorCode, $response['errorCode']);
    }

    /**
     * @return array<string, mixed>
     */
    public static function invalidLoginDataProvider(): array
    {
        return [
            'invalid credentials' => [
                [
                    'email' => 'existing@example.com',
                    'password' => 'wrongpassword',
                ],
                401,
                'INVALID_CREDENTIALS',
            ],
            'non-existent email' => [
                [
                    'email' => 'nonexistent@example.com',
                    'password' => 'password123',
                ],
                401,
                'INVALID_CREDENTIALS',
            ],
            'missing email' => [
                [
                    'password' => 'password123',
                ],
                400,
                'BAD_REQUEST',
            ],
            'missing password' => [
                [
                    'email' => 'test@example.com',
                ],
                400,
                'BAD_REQUEST',
            ],
            'empty payload' => [[], 400, 'BAD_REQUEST'],
            'empty email' => [
                [
                    'email' => '',
                    'password' => 'password123',
                ],
                401,
                'INVALID_CREDENTIALS',
            ],
            'empty password' => [
                [
                    'email' => 'test@example.com',
                    'password' => '',
                ],
                401,
                'INVALID_CREDENTIALS',
            ],
        ];
    }

    public function testLogoutSuccessfully(): void
    {
        // Arrange
        $email = 'logout-test@example.com';
        $customer = $this->createCustomerWithToken(email: $email);
        $token = $customer->getApiToken()->getToken();

        // Act
        $this->client->request('POST', '/ecommerce/api/v1/customer/logout', [], [], [
            'HTTP_wsei-ecommerce-token' => $token,
        ]);

        // Assert
        static::assertResponseStatusCodeSame(204);

        $apiTokenRepository = $this->container->get(ApiTokenRepository::class);
        $tokenFromDb = $apiTokenRepository->findOneBy([
            'token' => $token,
        ]);
        static::assertNull($tokenFromDb);
    }

    public function testLogoutWithoutToken(): void
    {
        // Act
        $this->client->request('POST', '/ecommerce/api/v1/customer/logout');

        // Assert
        static::assertResponseStatusCodeSame(401);
    }

    public function testLogoutWithInvalidToken(): void
    {
        // Act
        $this->client->request('POST', '/ecommerce/api/v1/customer/logout', [], [], [
            'HTTP_wsei-ecommerce-token' => 'invalid-token-12345',
        ]);

        // Assert
        static::assertResponseStatusCodeSame(401);
    }

    public function testLogoutWithoutApiToken(): void
    {
        // Arrange
        $email = 'logout-notoken@example.com';
        $customer = $this->createCustomerWithToken(email: $email);

        $entityManager = $this->container->get(EntityManagerInterface::class);
        $tokenToRemove = $customer->getApiToken();
        $tokenValue = $tokenToRemove->getToken();
        $customer->setApiToken(null);
        $entityManager->remove($tokenToRemove);
        $entityManager->flush();

        // Act
        $this->client->request('POST', '/ecommerce/api/v1/customer/logout', [], [], [
            'HTTP_wsei-ecommerce-token' => $tokenValue,
        ]);

        // Assert
        static::assertResponseStatusCodeSame(401);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get(EntityManagerInterface::class);
    }
}
