<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Order;

use PHPUnit\Framework\Attributes\DataProvider;

class ValidationTest extends AbstractOrderPlacementTest
{
    public function testCannotPlaceOrderWithEmptyCart(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('empty-cart@example.com');
        $address = $this->createAddress($customer);

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Cart is empty', $response['message']);
    }

    public function testCannotPlaceOrderWithInvalidAddressId(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('invalid-address@example.com');
        $product = $this->createProduct('Product X', 10);

        $this->addItemToCart($customer, $product->getId(), 1);

        // Act
        $this->placeOrder($customer, 99999);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Address not found', $response['message']);
    }

    public function testCannotPlaceOrderWithAddressBelongingToDifferentCustomer(): void
    {
        // Arrange
        $customer1 = $this->createCustomerWithToken('customer1@example.com');
        $customer2 = $this->createCustomerWithToken('customer2@example.com');

        $address1 = $this->createAddress($customer1);
        $product = $this->createProduct('Product Y', 10);

        $this->addItemToCart($customer2, $product->getId(), 1);

        // Act - Customer 2 tries to use Customer 1's address
        $this->placeOrder($customer2, $address1->getId());

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Address not found', $response['message']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('invalidPayloadDataProvider')]
    public function testInvalidPayloadValidation(array $payload, string $expectedErrorField): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('validation-' . uniqid() . '@example.com');
        $product = $this->createProduct('Product Z', 10);

        $this->addItemToCart($customer, $product->getId(), 1);

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/order', $payload, $this->getAuthHeaders($customer));

        // Assert
        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * @return array<string, array{payload: array<string, mixed>, expectedErrorField: string}>
     */
    public static function invalidPayloadDataProvider(): array
    {
        return [
            'missing addressId' => [
                'payload' => [],
                'expectedErrorField' => 'addressId',
            ],
            'addressId is null' => [
                'payload' => [
                    'addressId' => null,
                ],
                'expectedErrorField' => 'addressId',
            ],
            'addressId is string' => [
                'payload' => [
                    'addressId' => 'invalid',
                ],
                'expectedErrorField' => 'addressId',
            ],
            'addressId is negative' => [
                'payload' => [
                    'addressId' => -1,
                ],
                'expectedErrorField' => 'addressId',
            ],
            'addressId is zero' => [
                'payload' => [
                    'addressId' => 0,
                ],
                'expectedErrorField' => 'addressId',
            ],
        ];
    }

    public function testCannotPlaceOrderWithoutAuthentication(): void
    {
        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/order', [
            'addressId' => 1,
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(401);
    }
}
