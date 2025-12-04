<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer\CustomerAddressController;
use Wsei\Ecommerce\Entity\Address;
use Wsei\Ecommerce\Entity\ApiToken;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Repository\AddressRepository;

class CustomerAddressControllerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AddressRepository $addressRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private CustomerAddressController $controller;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->addressRepository = self::getContainer()->get(AddressRepository::class);
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->controller = self::getContainer()->get(CustomerAddressController::class);
    }

    public function testIndexReturnsCustomerAddresses(): void
    {
        $customer = $this->createTestCustomer('index-test@example.com');

        // Create test addresses
        $address1 = new Address();
        $address1->setFirstName('John');
        $address1->setLastName('Doe');
        $address1->setStreet('123 Main St');
        $address1->setZipcode('12345');
        $address1->setCity('New York');
        $address1->setCountry('USA');
        $address1->setCustomer($customer);

        $address2 = new Address();
        $address2->setFirstName('Jane');
        $address2->setLastName('Smith');
        $address2->setStreet('456 Oak Ave');
        $address2->setZipcode('67890');
        $address2->setCity('Los Angeles');
        $address2->setCountry('USA');
        $address2->setCustomer($customer);

        $this->entityManager->persist($address1);
        $this->entityManager->persist($address2);
        $this->entityManager->flush();

        $request = new Request();
        $response = $this->controller->index($customer);

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content['addresses']);
        $this->assertCount(2, $content['addresses']);
    }

    public function testCreateAddsNewAddress(): void
    {
        $customer = $this->createTestCustomer('create-test@example.com');

        $requestData = [
            'firstName' => 'Alice',
            'lastName' => 'Johnson',
            'street' => '789 Elm St',
            'zipcode' => '54321',
            'city' => 'Chicago',
            'country' => 'USA',
        ];

        $request = new Request(content: json_encode($requestData));
        $response = $this->controller->create($request, $customer);

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Alice', $content['firstName']);
        $this->assertEquals('Chicago', $content['city']);
        $this->assertNotNull($content['id']);

        // Verify address was persisted
        $addresses = $this->addressRepository->findByCustomer($customer->getId());
        $this->assertCount(1, $addresses);
    }

    public function testUpdateModifiesExistingAddress(): void
    {
        $customer = $this->createTestCustomer('update-test@example.com');

        // Create initial address
        $address = new Address();
        $address->setFirstName('Bob');
        $address->setLastName('Brown');
        $address->setStreet('321 Pine St');
        $address->setZipcode('11111');
        $address->setCity('Boston');
        $address->setCountry('USA');
        $address->setCustomer($customer);

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        $addressId = $address->getId();

        $requestData = [
            'firstName' => 'Robert',
            'lastName' => 'Brown',
            'street' => '321 Pine Street',
            'zipcode' => '11111',
            'city' => 'Boston',
            'country' => 'United States',
        ];

        $request = new Request(content: json_encode($requestData));
        $response = $this->controller->update($addressId, $request, $customer);

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Robert', $content['firstName']);
        $this->assertEquals('United States', $content['country']);
    }

    public function testDeleteRemovesAddress(): void
    {
        $customer = $this->createTestCustomer('delete-test@example.com');

        // Create address to delete
        $address = new Address();
        $address->setFirstName('Charlie');
        $address->setLastName('Davis');
        $address->setStreet('999 Maple Dr');
        $address->setZipcode('99999');
        $address->setCity('Seattle');
        $address->setCountry('USA');
        $address->setCustomer($customer);

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        $addressId = $address->getId();

        $response = $this->controller->delete($addressId, $customer);

        $this->assertEquals(204, $response->getStatusCode());

        // Verify address was deleted
        $deletedAddress = $this->addressRepository->find($addressId);
        $this->assertNull($deletedAddress);
    }

    public function testUpdateThrowsNotFoundForOtherCustomerAddress(): void
    {
        $customer1 = $this->createTestCustomer('owner@example.com');
        $customer2 = $this->createTestCustomer('other@example.com');

        // Create address for customer1
        $address = new Address();
        $address->setFirstName('Owner');
        $address->setLastName('User');
        $address->setStreet('100 Owner St');
        $address->setZipcode('00000');
        $address->setCity('Portland');
        $address->setCountry('USA');
        $address->setCustomer($customer1);

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        $addressId = $address->getId();

        $requestData = [
            'firstName' => 'Hacker',
            'lastName' => 'Malicious',
            'street' => '100 Owner St',
            'zipcode' => '00000',
            'city' => 'Portland',
            'country' => 'USA',
        ];

        $request = new Request(content: json_encode($requestData));

        $this->expectException(\Wsei\Ecommerce\EcommerceApi\Exception\Http\NotFoundException::class);
        $this->controller->update($addressId, $request, $customer2);
    }

    public function testDeleteThrowsNotFoundForOtherCustomerAddress(): void
    {
        $customer1 = $this->createTestCustomer('owner2@example.com');
        $customer2 = $this->createTestCustomer('other2@example.com');

        // Create address for customer1
        $address = new Address();
        $address->setFirstName('Owner');
        $address->setLastName('User');
        $address->setStreet('200 Owner St');
        $address->setZipcode('00001');
        $address->setCity('Denver');
        $address->setCountry('USA');
        $address->setCustomer($customer1);

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        $addressId = $address->getId();

        $this->expectException(\Wsei\Ecommerce\EcommerceApi\Exception\Http\NotFoundException::class);
        $this->controller->delete($addressId, $customer2);
    }

    private function createTestCustomer(string $email): Customer
    {
        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setFirstName('Test');
        $customer->setLastName('User');
        $hashedPassword = $this->passwordHasher->hashPassword($customer, 'password123');
        $customer->setPassword($hashedPassword);

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }
}

