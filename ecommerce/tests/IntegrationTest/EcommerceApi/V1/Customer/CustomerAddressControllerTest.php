<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Customer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wsei\Ecommerce\Repository\AddressRepository;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsAddresses;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsCustomers;

class CustomerAddressControllerTest extends WebTestCase
{
    use BuildsAddresses;
    use BuildsCustomers;

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
        $this->createAddress($customer, firstName: 'John', lastName: 'Doe', city: 'New York');
        $this->createAddress($customer, firstName: 'Jane', lastName: 'Smith', city: 'Los Angeles');

        // Act
        $this->client->request('GET', '/ecommerce/api/v1/customer/addresses', [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertCount(2, $response['addresses']);
        static::assertEquals('John', $response['addresses'][1]['firstName']);
        static::assertEquals('Doe', $response['addresses'][1]['lastName']);
        static::assertEquals('New York', $response['addresses'][1]['city']);
        static::assertEquals('Jane', $response['addresses'][0]['firstName']);
        static::assertEquals('Smith', $response['addresses'][0]['lastName']);
        static::assertEquals('Los Angeles', $response['addresses'][0]['city']);
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
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertEquals('Alice', $response['firstName']);
        static::assertNotNull($response['id']);
    }

    public function testUpdateAddress(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('update@example.com');
        $address = $this->createAddress($customer, firstName: 'Bob', lastName: 'Brown', city: 'Boston');
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
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertEquals('Robert', $response['firstName']);
        static::assertEquals('United States', $response['country']);
    }

    public function testDeleteAddress(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('delete@example.com');
        $address = $this->createAddress($customer, firstName: 'Charlie', lastName: 'Davis', city: 'Seattle');
        $addressId = $address->getId();

        // Act
        $this->client->request('DELETE', '/ecommerce/api/v1/customer/addresses/' . $addressId, [], [], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(204);
        $addressRepository = $this->container->get(AddressRepository::class);
        static::assertNull($addressRepository->find($addressId));
    }

    public function testCannotUpdateOtherCustomerAddress(): void
    {
        // Arrange
        $customer1 = $this->createCustomerWithToken('owner@example.com');
        $customer2 = $this->createCustomerWithToken('other@example.com');
        $address = $this->createAddress($customer1, firstName: 'Owner', lastName: 'User', city: 'Portland');
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
            'HTTP_wsei-ecommerce-token' => $customer2->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(404);
    }

    public function testCannotDeleteOtherCustomerAddress(): void
    {
        // Arrange
        $customer1 = $this->createCustomerWithToken('owner2@example.com');
        $customer2 = $this->createCustomerWithToken('other2@example.com');
        $address = $this->createAddress($customer1, firstName: 'Owner', lastName: 'User', city: 'Denver');

        // Act
        $this->client->request('DELETE', '/ecommerce/api/v1/customer/addresses/' . $address->getId(), [], [], [
            'HTTP_wsei-ecommerce-token' => $customer2->getApiToken()
                ->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(404);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get(EntityManagerInterface::class);
    }
}
