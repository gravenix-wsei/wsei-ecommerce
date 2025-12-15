<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Order;

use PHPUnit\Framework\Attributes\DataProvider;

class PriceFreezingTest extends AbstractOrderPlacementTest
{
    public function testProductPricesAreFrozenInOrder(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('frozen-prices@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Gaming Console', 20, '300.00', '369.00');

        $this->addItemToCart($customer, $product->getId(), 1);

        // Act
        $this->placeOrder($customer, $address->getId());
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Modify product prices
        $product->setPriceNet('450.00');
        $product->setPriceGross('553.50');
        $this->entityManager->flush();

        // Assert - Order should have original prices
        $this->assertEquals('300.00', $response['items'][0]['priceNet']);
        $this->assertEquals('369.00', $response['items'][0]['priceGross']);
        $this->assertEquals('300.00', $response['totalPriceNet']);
        $this->assertEquals('369.00', $response['totalPriceGross']);
    }

    public function testProductNameIsFrozenInOrder(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('frozen-name@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Original Product Name', 10, '50.00', '61.50');

        $this->addItemToCart($customer, $product->getId(), 2);

        // Act
        $this->placeOrder($customer, $address->getId());
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Modify product name
        $product->setName('Completely Different Name');
        $this->entityManager->flush();

        // Assert - Order item should retain original name
        $this->assertEquals('Original Product Name', $response['items'][0]['productName']);

        // Verify product reference is maintained
        $this->assertEquals($product->getId(), $response['items'][0]['productId']);
    }

    /**
     * @param array<int, array{name: string, stock: int, priceNet: string, priceGross: string, quantity: int}> $items
     */
    #[DataProvider('priceCalculationDataProvider')]
    public function testPriceCalculationsAreAccurate(
        array $items,
        string $expectedTotalNet,
        string $expectedTotalGross
    ): void {
        // Arrange
        $customer = $this->createCustomerWithToken('price-calc-' . uniqid() . '@example.com');
        $address = $this->createAddress($customer);

        foreach ($items as $itemData) {
            $product = $this->createProduct(
                $itemData['name'],
                $itemData['stock'],
                $itemData['priceNet'],
                $itemData['priceGross']
            );
            $this->addItemToCart($customer, $product->getId(), $itemData['quantity']);
        }

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($expectedTotalNet, $response['totalPriceNet']);
        $this->assertEquals($expectedTotalGross, $response['totalPriceGross']);
    }

    /**
     * @return array<string, array{items: array<int, array{name: string, stock: int, priceNet: string, priceGross: string, quantity: int}>, expectedTotalNet: string, expectedTotalGross: string}>
     */
    public static function priceCalculationDataProvider(): array
    {
        return [
            'single item, single quantity' => [
                'items' => [
                    [
                        'name' => 'Item A',
                        'stock' => 10,
                        'priceNet' => '10.00',
                        'priceGross' => '12.30',
                        'quantity' => 1,
                    ],
                ],
                'expectedTotalNet' => '10.00',
                'expectedTotalGross' => '12.30',
            ],
            'single item, multiple quantity' => [
                'items' => [
                    [
                        'name' => 'Item B',
                        'stock' => 20,
                        'priceNet' => '25.50',
                        'priceGross' => '31.37',
                        'quantity' => 5,
                    ],
                ],
                'expectedTotalNet' => '127.50',
                'expectedTotalGross' => '156.85',
            ],
            'multiple items, various quantities' => [
                'items' => [
                    [
                        'name' => 'Item C',
                        'stock' => 30,
                        'priceNet' => '15.00',
                        'priceGross' => '18.45',
                        'quantity' => 3,
                    ],
                    [
                        'name' => 'Item D',
                        'stock' => 40,
                        'priceNet' => '7.99',
                        'priceGross' => '9.83',
                        'quantity' => 10,
                    ],
                    [
                        'name' => 'Item E',
                        'stock' => 50,
                        'priceNet' => '100.00',
                        'priceGross' => '123.00',
                        'quantity' => 2,
                    ],
                ],
                'expectedTotalNet' => '324.90',
                'expectedTotalGross' => '399.65',
            ],
            'decimal precision edge case' => [
                'items' => [
                    [
                        'name' => 'Item F',
                        'stock' => 15,
                        'priceNet' => '33.33',
                        'priceGross' => '40.99',
                        'quantity' => 3,
                    ],
                    [
                        'name' => 'Item G',
                        'stock' => 25,
                        'priceNet' => '0.01',
                        'priceGross' => '0.01',
                        'quantity' => 7,
                    ],
                ],
                'expectedTotalNet' => '100.06', // 33.33 * 3 + 0.01 * 7 = 99.99 + 0.07 = 100.06
                'expectedTotalGross' => '123.04', // 40.99 * 3 + 0.01 * 7 = 122.97 + 0.07 = 123.04
            ],
        ];
    }
}
