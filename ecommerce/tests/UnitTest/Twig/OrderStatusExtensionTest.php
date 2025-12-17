<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatus;
use Wsei\Ecommerce\Twig\OrderStatusExtension;

class OrderStatusExtensionTest extends TestCase
{
    private OrderStatusExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange
        $this->extension = new OrderStatusExtension();
    }

    public function testGetFiltersReturnsTwoFilters(): void
    {
        // Act
        $filters = $this->extension->getFilters();

        // Assert
        $this->assertCount(2, $filters);
    }

    public function testGetFiltersContainsOrderStatusBadgeFilter(): void
    {
        // Act
        $filters = $this->extension->getFilters();

        // Assert
        $filterNames = array_map(fn (TwigFilter $filter): string => $filter->getName(), $filters);
        $this->assertContains('order_status_badge', $filterNames);
    }

    public function testGetFiltersContainsOrderStatusLabelFilter(): void
    {
        // Act
        $filters = $this->extension->getFilters();

        // Assert
        $filterNames = array_map(fn (TwigFilter $filter): string => $filter->getName(), $filters);
        $this->assertContains('order_status_label', $filterNames);
    }

    #[DataProvider('provideStatusBadgeClasses')]
    public function testGetStatusBadgeClassReturnsCorrectClass(OrderStatus $status, string $expectedClass): void
    {
        // Act
        $badgeClass = $this->extension->getStatusBadgeClass($status);

        // Assert
        $this->assertSame($expectedClass, $badgeClass);
    }

    /**
     * @return array<string, array{status: OrderStatus, expectedClass: string}>
     */
    public static function provideStatusBadgeClasses(): array
    {
        return [
            'NEW status' => [
                'status' => OrderStatus::NEW,
                'expectedClass' => 'badge-new',
            ],
            'PENDING_PAYMENT status' => [
                'status' => OrderStatus::PENDING_PAYMENT,
                'expectedClass' => 'badge-pending',
            ],
            'PAID status' => [
                'status' => OrderStatus::PAID,
                'expectedClass' => 'badge-paid',
            ],
            'SENT status' => [
                'status' => OrderStatus::SENT,
                'expectedClass' => 'badge-sent',
            ],
            'DELIVERED status' => [
                'status' => OrderStatus::DELIVERED,
                'expectedClass' => 'badge-delivered',
            ],
            'CANCELLED status' => [
                'status' => OrderStatus::CANCELLED,
                'expectedClass' => 'badge-cancelled',
            ],
        ];
    }

    #[DataProvider('provideStatusLabels')]
    public function testGetStatusLabelReturnsCorrectLabel(OrderStatus $status, string $expectedLabel): void
    {
        // Act
        $label = $this->extension->getStatusLabel($status);

        // Assert
        $this->assertSame($expectedLabel, $label);
    }

    /**
     * @return array<string, array{status: OrderStatus, expectedLabel: string}>
     */
    public static function provideStatusLabels(): array
    {
        return [
            'NEW status' => [
                'status' => OrderStatus::NEW,
                'expectedLabel' => 'New',
            ],
            'PENDING_PAYMENT status' => [
                'status' => OrderStatus::PENDING_PAYMENT,
                'expectedLabel' => 'Pending Payment',
            ],
            'PAID status' => [
                'status' => OrderStatus::PAID,
                'expectedLabel' => 'Paid',
            ],
            'SENT status' => [
                'status' => OrderStatus::SENT,
                'expectedLabel' => 'Sent',
            ],
            'DELIVERED status' => [
                'status' => OrderStatus::DELIVERED,
                'expectedLabel' => 'Delivered',
            ],
            'CANCELLED status' => [
                'status' => OrderStatus::CANCELLED,
                'expectedLabel' => 'Cancelled',
            ],
        ];
    }
}
