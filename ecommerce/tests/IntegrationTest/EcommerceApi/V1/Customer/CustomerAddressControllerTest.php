<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Customer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Wsei\Ecommerce\Entity\Address;
use Wsei\Ecommerce\Entity\ApiToken;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Repository\AddressRepository;

class CustomerAddressControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
    }

    public function testGetAddressesList(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('list@example.com');
        $this->createAddress($customer, 'John', 'Doe', 'New York');
        $this->createAddress($customer, 'Jane', 'Smith', 'Los Angeles');

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/customer/addresses', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $response['addresses']);
    }

    public function testCreateAddress(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('create@example.com');
        $payload = [
            'firstName' => 'Alice',
            'lastName' => 'Johnson',
            'street' => '789 Elm St',
            'zipcode' => '54321',
            'city' => 'Chicago',
            'country' => 'USA',
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/addresses', $payload, [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Alice', $response['firstName']);
        $this->assertNotNull($response['id']);
    }

    public function testUpdateAddress(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('update@example.com');
        $address = $this->createAddress($customer, 'Bob', 'Brown', 'Boston');
        $payload = [
            'firstName' => 'Robert',
            'lastName' => 'Brown',
            'street' => '321 Pine Street',
            'zipcode' => '11111',
            'city' => 'Boston',
            'country' => 'United States',
        ];

        // Act
        $this->client->jsonRequest('PUT', '/ecommerce/api/v1/customer/addresses/' . $address->getId(), $payload, [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Robert', $response['firstName']);
        $this->assertEquals('United States', $response['country']);
    }

    public function testDeleteAddress(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('delete@example.com');
        $address = $this->createAddress($customer, 'Charlie', 'Davis', 'Seattle');
        $addressId = $address->getId();

        // Act
        $this->client->request('DELETE', '/ecommerce/api/v1/customer/addresses/' . $addressId, [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(204);
        $addressRepository = $this->container->get(AddressRepository::class);
        $this->assertNull($addressRepository->find($addressId));
    }

    public function testCannotUpdateOtherCustomerAddress(): void
    {
        // Arrange
        $customer1 = $this->createCustomerWithToken('owner@example.com');
        $customer2 = $this->createCustomerWithToken('other@example.com');
        $address = $this->createAddress($customer1, 'Owner', 'User', 'Portland');
        $payload = [
            'firstName' => 'Hacker',
            'lastName' => 'Malicious',
            'street' => '100 Hack St',
            'zipcode' => '00000',
            'city' => 'Portland',
            'country' => 'USA',
        ];

        // Act
        $this->client->jsonRequest('PUT', '/ecommerce/api/v1/customer/addresses/' . $address->getId(), $payload, [
            'HTTP_wsei-ecommerce-token' => $customer2->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotDeleteOtherCustomerAddress(): void
    {
        // Arrange
        $customer1 = $this->createCustomerWithToken('owner2@example.com');
        $customer2 = $this->createCustomerWithToken('other2@example.com');
        $address = $this->createAddress($customer1, 'Owner', 'User', 'Denver');

        // Act
        $this->client->request('DELETE', '/ecommerce/api/v1/customer/addresses/' . $address->getId(), [], [], [
            'HTTP_wsei-ecommerce-token' => $customer2->getApiToken()->getToken(),
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(404);
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

    private function createAddress(Customer $customer, string $firstName, string $lastName, string $city): Address
    {
        $entityManager = $this->container->get(EntityManagerInterface::class);

        $address = new Address();
        $address->setFirstName($firstName);
        $address->setLastName($lastName);
        $address->setStreet('123 Main St');
        $address->setZipcode('12345');
        $address->setCity($city);
        $address->setCountry('USA');
        $address->setCustomer($customer);

        $entityManager->persist($address);
        $entityManager->flush();

        return $address;
    }
}

