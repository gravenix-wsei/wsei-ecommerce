<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Order;

use PHPUnit\Framework\Attributes\DataProvider;
use Wsei\Ecommerce\Entity\Product;

class StockManagementTest extends AbstractOrderPlacementTest
{
    public function testStockIsDecreasedAfterOrderPlacement(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('stock-decrease@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Limited Stock Product', 100, '20.00', '24.60');

        $initialStock = $product->getStock();
        $orderedQuantity = 15;

        $this->addItemToCart($customer, $product->getId(), $orderedQuantity);

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        static::assertResponseIsSuccessful();

        // Fetch product from database to check updated stock
        $updatedProduct = $this->entityManager->find(Product::class, $product->getId());
        static::assertEquals($initialStock - $orderedQuantity, $updatedProduct?->getStock());
        static::assertEquals(85, $updatedProduct?->getStock());
    }

    public function testStockDecreasedForMultipleProducts(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('multi-stock@example.com');
        $address = $this->createAddress($customer);

        $product1 = $this->createProduct('Product 1', 50);
        $product2 = $this->createProduct('Product 2', 30, '20.00', '24.60');
        $product3 = $this->createProduct('Product 3', 100, '5.00', '6.15');

        $this->addItemToCart($customer, $product1->getId(), 10);
        $this->addItemToCart($customer, $product2->getId(), 5);
        $this->addItemToCart($customer, $product3->getId(), 25);

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        static::assertResponseIsSuccessful();

        // Fetch products from database to check updated stock
        $updatedProduct1 = $this->entityManager->find(Product::class, $product1->getId());
        $updatedProduct2 = $this->entityManager->find(Product::class, $product2->getId());
        $updatedProduct3 = $this->entityManager->find(Product::class, $product3->getId());

        static::assertEquals(40, $updatedProduct1?->getStock());
        static::assertEquals(25, $updatedProduct2?->getStock());
        static::assertEquals(75, $updatedProduct3?->getStock());
    }

    /**
     * @param array<int, array{name: string, stock: int}> $productsData
     * @param array<int, int> $orderQuantities
     */
    #[DataProvider('insufficientStockDataProvider')]
    public function testInsufficientStockPreventsOrderPlacement(
        array $productsData,
        array $orderQuantities,
        string $expectedErrorMessagePattern
    ): void {
        // Arrange
        $customer = $this->createCustomerWithToken('insufficient-' . uniqid() . '@example.com');
        $address = $this->createAddress($customer);

        $products = [];
        foreach ($productsData as $productData) {
            $products[] = $this->createProduct($productData['name'], $productData['stock']);
        }

        foreach ($orderQuantities as $index => $quantity) {
            $this->addItemToCart($customer, $products[$index]->getId(), $quantity);
        }

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        static::assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertStringContainsString('Insufficient stock', $response['message']);
        static::assertMatchesRegularExpression($expectedErrorMessagePattern, $response['message']);
    }

    /**
     * @return array<string, array{0: array<int, array{name: string, stock: int}>, 1: array<int, int>, 2: string}>
     */
    public static function insufficientStockDataProvider(): array
    {
        return [
            'single product out of stock' => [
                [
                    [
                        'name' => 'Out of Stock Item',
                        'stock' => 5,
                    ],
                ],
                [10],
                '/Out of Stock Item.*requested 10.*available 5/',
            ],
            'multiple products with insufficient stock' => [
                [
                    [
                        'name' => 'Item A',
                        'stock' => 2,
                    ],
                    [
                        'name' => 'Item B',
                        'stock' => 1,
                    ],
                    [
                        'name' => 'Item C',
                        'stock' => 10,
                    ],
                ],
                [5, 3, 3],
                '/Item A.*requested 5.*available 2.*Item B.*requested 3.*available 1/',
            ],
            'one product sufficient, one insufficient' => [
                [
                    [
                        'name' => 'Available Product',
                        'stock' => 100,
                    ],
                    [
                        'name' => 'Low Stock Product',
                        'stock' => 3,
                    ],
                ],
                [50, 10],
                '/Low Stock Product.*requested 10.*available 3/',
            ],
        ];
    }

    public function testStockNotChangedWhenOrderFailsValidation(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('rollback-stock@example.com');
        $address = $this->createAddress($customer);

        $productA = $this->createProduct('Product A', 50);
        $productB = $this->createProduct('Product B', 5, '20.00', '24.60');

        $initialStockA = $productA->getStock();
        $initialStockB = $productB->getStock();

        $this->addItemToCart($customer, $productA->getId(), 10);
        $this->addItemToCart($customer, $productB->getId(), 20); // Exceeds stock

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        static::assertResponseStatusCodeSame(400);

        // Verify stocks unchanged
        $this->entityManager->clear();
        $productARefreshed = $this->entityManager->find(Product::class, $productA->getId());
        $productBRefreshed = $this->entityManager->find(Product::class, $productB->getId());

        static::assertEquals($initialStockA, $productARefreshed?->getStock());
        static::assertEquals($initialStockB, $productBRefreshed?->getStock());
    }

    public function testOrderWithExactStockAmount(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('exact-stock@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Last Units', 7, '15.00', '18.45');

        $this->addItemToCart($customer, $product->getId(), 7);

        // Act
        $this->placeOrder($customer, $address->getId());

        // Assert
        static::assertResponseIsSuccessful();

        // Fetch product from database to check updated stock
        $updatedProduct = $this->entityManager->find(Product::class, $product->getId());
        static::assertEquals(0, $updatedProduct?->getStock());
    }
}
