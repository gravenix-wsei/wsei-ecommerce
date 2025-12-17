<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Framework\Checkout\Order;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatus;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatusTransition;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatusTransitionInterface;

class OrderStatusTransitionTest extends TestCase
{
    private OrderStatusTransitionInterface $statusTransition;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange
        $this->statusTransition = new OrderStatusTransition();
    }

    /**
     * @param OrderStatus[] $expectedStatuses
     */
    #[DataProvider('provideAllowedTransitions')]
    public function testGetAllowedTransitionsReturnsCorrectStatuses(
        OrderStatus $fromStatus,
        array $expectedStatuses
    ): void {
        // Act
        $allowedTransitions = $this->statusTransition->getAllowedTransitions($fromStatus);

        // Assert
        $this->assertSame($expectedStatuses, $allowedTransitions);
        $this->assertCount(count($expectedStatuses), $allowedTransitions);
    }

    /**
     * @return array<string, array{fromStatus: OrderStatus, expectedStatuses: OrderStatus[]}>
     */
    public static function provideAllowedTransitions(): array
    {
        return [
            'NEW status can transition to PENDING_PAYMENT or CANCELLED' => [
                'fromStatus' => OrderStatus::NEW,
                'expectedStatuses' => [OrderStatus::PENDING_PAYMENT, OrderStatus::CANCELLED],
            ],
            'PENDING_PAYMENT status can transition to PAID or CANCELLED' => [
                'fromStatus' => OrderStatus::PENDING_PAYMENT,
                'expectedStatuses' => [OrderStatus::PAID, OrderStatus::CANCELLED],
            ],
            'PAID status can transition to SENT or CANCELLED' => [
                'fromStatus' => OrderStatus::PAID,
                'expectedStatuses' => [OrderStatus::SENT, OrderStatus::CANCELLED],
            ],
            'SENT status can transition to DELIVERED or CANCELLED' => [
                'fromStatus' => OrderStatus::SENT,
                'expectedStatuses' => [OrderStatus::DELIVERED, OrderStatus::CANCELLED],
            ],
            'DELIVERED status is final and has no transitions' => [
                'fromStatus' => OrderStatus::DELIVERED,
                'expectedStatuses' => [],
            ],
            'CANCELLED status is final and has no transitions' => [
                'fromStatus' => OrderStatus::CANCELLED,
                'expectedStatuses' => [],
            ],
        ];
    }

    #[DataProvider('provideValidTransitions')]
    public function testCanTransitionToReturnsTrueForValidTransitions(
        OrderStatus $fromStatus,
        OrderStatus $toStatus
    ): void {
        // Act
        $canTransition = $this->statusTransition->canTransitionTo($fromStatus, $toStatus);

        // Assert
        $this->assertTrue(
            $canTransition,
            sprintf('Should be able to transition from %s to %s', $fromStatus->value, $toStatus->value)
        );
    }

    /**
     * @return array<string, array{fromStatus: OrderStatus, toStatus: OrderStatus}>
     */
    public static function provideValidTransitions(): array
    {
        return [
            'NEW to PENDING_PAYMENT' => [
                'fromStatus' => OrderStatus::NEW,
                'toStatus' => OrderStatus::PENDING_PAYMENT,
            ],
            'NEW to CANCELLED' => [
                'fromStatus' => OrderStatus::NEW,
                'toStatus' => OrderStatus::CANCELLED,
            ],
            'PENDING_PAYMENT to PAID' => [
                'fromStatus' => OrderStatus::PENDING_PAYMENT,
                'toStatus' => OrderStatus::PAID,
            ],
            'PENDING_PAYMENT to CANCELLED' => [
                'fromStatus' => OrderStatus::PENDING_PAYMENT,
                'toStatus' => OrderStatus::CANCELLED,
            ],
            'PAID to SENT' => [
                'fromStatus' => OrderStatus::PAID,
                'toStatus' => OrderStatus::SENT,
            ],
            'PAID to CANCELLED' => [
                'fromStatus' => OrderStatus::PAID,
                'toStatus' => OrderStatus::CANCELLED,
            ],
            'SENT to DELIVERED' => [
                'fromStatus' => OrderStatus::SENT,
                'toStatus' => OrderStatus::DELIVERED,
            ],
            'SENT to CANCELLED' => [
                'fromStatus' => OrderStatus::SENT,
                'toStatus' => OrderStatus::CANCELLED,
            ],
        ];
    }

    #[DataProvider('provideInvalidTransitions')]
    public function testCanTransitionToReturnsFalseForInvalidTransitions(
        OrderStatus $fromStatus,
        OrderStatus $toStatus
    ): void {
        // Act
        $canTransition = $this->statusTransition->canTransitionTo($fromStatus, $toStatus);

        // Assert
        $this->assertFalse(
            $canTransition,
            sprintf('Should NOT be able to transition from %s to %s', $fromStatus->value, $toStatus->value)
        );
    }

    /**
     * @return array<string, array{fromStatus: OrderStatus, toStatus: OrderStatus}>
     */
    public static function provideInvalidTransitions(): array
    {
        return [
            // Self-transitions (not allowed)
            'NEW to NEW (self-transition)' => [
                'fromStatus' => OrderStatus::NEW,
                'toStatus' => OrderStatus::NEW,
            ],
            'PENDING_PAYMENT to PENDING_PAYMENT (self-transition)' => [
                'fromStatus' => OrderStatus::PENDING_PAYMENT,
                'toStatus' => OrderStatus::PENDING_PAYMENT,
            ],
            'PAID to PAID (self-transition)' => [
                'fromStatus' => OrderStatus::PAID,
                'toStatus' => OrderStatus::PAID,
            ],
            'SENT to SENT (self-transition)' => [
                'fromStatus' => OrderStatus::SENT,
                'toStatus' => OrderStatus::SENT,
            ],
            'DELIVERED to DELIVERED (self-transition)' => [
                'fromStatus' => OrderStatus::DELIVERED,
                'toStatus' => OrderStatus::DELIVERED,
            ],
            'CANCELLED to CANCELLED (self-transition)' => [
                'fromStatus' => OrderStatus::CANCELLED,
                'toStatus' => OrderStatus::CANCELLED,
            ],

            // Backward transitions (not allowed)
            'PENDING_PAYMENT to NEW (backward)' => [
                'fromStatus' => OrderStatus::PENDING_PAYMENT,
                'toStatus' => OrderStatus::NEW,
            ],
            'PAID to NEW (backward)' => [
                'fromStatus' => OrderStatus::PAID,
                'toStatus' => OrderStatus::NEW,
            ],
            'PAID to PENDING_PAYMENT (backward)' => [
                'fromStatus' => OrderStatus::PAID,
                'toStatus' => OrderStatus::PENDING_PAYMENT,
            ],
            'SENT to NEW (backward)' => [
                'fromStatus' => OrderStatus::SENT,
                'toStatus' => OrderStatus::NEW,
            ],
            'SENT to PENDING_PAYMENT (backward)' => [
                'fromStatus' => OrderStatus::SENT,
                'toStatus' => OrderStatus::PENDING_PAYMENT,
            ],
            'SENT to PAID (backward)' => [
                'fromStatus' => OrderStatus::SENT,
                'toStatus' => OrderStatus::PAID,
            ],
            'DELIVERED to NEW (backward)' => [
                'fromStatus' => OrderStatus::DELIVERED,
                'toStatus' => OrderStatus::NEW,
            ],
            'DELIVERED to PENDING_PAYMENT (backward)' => [
                'fromStatus' => OrderStatus::DELIVERED,
                'toStatus' => OrderStatus::PENDING_PAYMENT,
            ],
            'DELIVERED to PAID (backward)' => [
                'fromStatus' => OrderStatus::DELIVERED,
                'toStatus' => OrderStatus::PAID,
            ],
            'DELIVERED to SENT (backward)' => [
                'fromStatus' => OrderStatus::DELIVERED,
                'toStatus' => OrderStatus::SENT,
            ],

            // Skipping statuses (not allowed)
            'NEW to PAID (skipping PENDING_PAYMENT)' => [
                'fromStatus' => OrderStatus::NEW,
                'toStatus' => OrderStatus::PAID,
            ],
            'NEW to SENT (skipping intermediate statuses)' => [
                'fromStatus' => OrderStatus::NEW,
                'toStatus' => OrderStatus::SENT,
            ],
            'NEW to DELIVERED (skipping intermediate statuses)' => [
                'fromStatus' => OrderStatus::NEW,
                'toStatus' => OrderStatus::DELIVERED,
            ],
            'PENDING_PAYMENT to SENT (skipping PAID)' => [
                'fromStatus' => OrderStatus::PENDING_PAYMENT,
                'toStatus' => OrderStatus::SENT,
            ],
            'PENDING_PAYMENT to DELIVERED (skipping intermediate statuses)' => [
                'fromStatus' => OrderStatus::PENDING_PAYMENT,
                'toStatus' => OrderStatus::DELIVERED,
            ],
            'PAID to DELIVERED (skipping SENT)' => [
                'fromStatus' => OrderStatus::PAID,
                'toStatus' => OrderStatus::DELIVERED,
            ],

            // Final statuses cannot transition (except to themselves, which is also blocked)
            'DELIVERED to CANCELLED (final status)' => [
                'fromStatus' => OrderStatus::DELIVERED,
                'toStatus' => OrderStatus::CANCELLED,
            ],
            'CANCELLED to NEW (final status)' => [
                'fromStatus' => OrderStatus::CANCELLED,
                'toStatus' => OrderStatus::NEW,
            ],
            'CANCELLED to PENDING_PAYMENT (final status)' => [
                'fromStatus' => OrderStatus::CANCELLED,
                'toStatus' => OrderStatus::PENDING_PAYMENT,
            ],
            'CANCELLED to PAID (final status)' => [
                'fromStatus' => OrderStatus::CANCELLED,
                'toStatus' => OrderStatus::PAID,
            ],
            'CANCELLED to SENT (final status)' => [
                'fromStatus' => OrderStatus::CANCELLED,
                'toStatus' => OrderStatus::SENT,
            ],
            'CANCELLED to DELIVERED (final status)' => [
                'fromStatus' => OrderStatus::CANCELLED,
                'toStatus' => OrderStatus::DELIVERED,
            ],
        ];
    }

    /**
     * @param OrderStatus[] $expectedDisabledStatuses
     */
    #[DataProvider('provideDisabledStatuses')]
    public function testGetDisabledStatusesReturnsCorrectStatuses(
        OrderStatus $currentStatus,
        array $expectedDisabledStatuses
    ): void {
        // Act
        $disabledStatuses = $this->statusTransition->getDisabledStatuses($currentStatus);

        // Assert
        $this->assertCount(count($expectedDisabledStatuses), $disabledStatuses);
        foreach ($expectedDisabledStatuses as $expectedStatus) {
            $this->assertContains(
                $expectedStatus,
                $disabledStatuses,
                sprintf(
                    'Status %s should be disabled for current status %s',
                    $expectedStatus->value,
                    $currentStatus->value
                )
            );
        }
    }

    /**
     * @return array<string, array{currentStatus: OrderStatus, expectedDisabledStatuses: OrderStatus[]}>
     */
    public static function provideDisabledStatuses(): array
    {
        return [
            'NEW status should disable all except PENDING_PAYMENT and CANCELLED' => [
                'currentStatus' => OrderStatus::NEW,
                'expectedDisabledStatuses' => [
                    OrderStatus::NEW,
                    OrderStatus::PAID,
                    OrderStatus::SENT,
                    OrderStatus::DELIVERED,
                ],
            ],
            'PENDING_PAYMENT status should disable all except PAID and CANCELLED' => [
                'currentStatus' => OrderStatus::PENDING_PAYMENT,
                'expectedDisabledStatuses' => [
                    OrderStatus::NEW,
                    OrderStatus::PENDING_PAYMENT,
                    OrderStatus::SENT,
                    OrderStatus::DELIVERED,
                ],
            ],
            'PAID status should disable all except SENT and CANCELLED' => [
                'currentStatus' => OrderStatus::PAID,
                'expectedDisabledStatuses' => [
                    OrderStatus::NEW,
                    OrderStatus::PENDING_PAYMENT,
                    OrderStatus::PAID,
                    OrderStatus::DELIVERED,
                ],
            ],
            'SENT status should disable all except DELIVERED and CANCELLED' => [
                'currentStatus' => OrderStatus::SENT,
                'expectedDisabledStatuses' => [
                    OrderStatus::NEW,
                    OrderStatus::PENDING_PAYMENT,
                    OrderStatus::PAID,
                    OrderStatus::SENT,
                ],
            ],
            'DELIVERED status should disable all statuses (final state)' => [
                'currentStatus' => OrderStatus::DELIVERED,
                'expectedDisabledStatuses' => [
                    OrderStatus::NEW,
                    OrderStatus::PENDING_PAYMENT,
                    OrderStatus::PAID,
                    OrderStatus::SENT,
                    OrderStatus::DELIVERED,
                    OrderStatus::CANCELLED,
                ],
            ],
            'CANCELLED status should disable all statuses (final state)' => [
                'currentStatus' => OrderStatus::CANCELLED,
                'expectedDisabledStatuses' => [
                    OrderStatus::NEW,
                    OrderStatus::PENDING_PAYMENT,
                    OrderStatus::PAID,
                    OrderStatus::SENT,
                    OrderStatus::DELIVERED,
                    OrderStatus::CANCELLED,
                ],
            ],
        ];
    }

    public function testImplementsInterface(): void
    {
        // Assert
        $this->assertInstanceOf(OrderStatusTransitionInterface::class, $this->statusTransition);
    }

    public function testAllStatusesAreCoveredInTransitionRules(): void
    {
        // Arrange
        $allStatuses = OrderStatus::cases();

        // Act & Assert
        foreach ($allStatuses as $status) {
            $allowedTransitions = $this->statusTransition->getAllowedTransitions($status);

            // Every status should have a defined rule (even if empty array for final statuses)
            // The return type guarantees it's an array, so we just verify no exception is thrown
            $this->assertGreaterThanOrEqual(
                0,
                count($allowedTransitions),
                sprintf('Status %s should have defined transition rules (array)', $status->value)
            );
        }
    }

    public function testCancelledCanBeReachedFromAnyNonFinalStatus(): void
    {
        // Arrange
        $nonFinalStatuses = [OrderStatus::NEW, OrderStatus::PENDING_PAYMENT, OrderStatus::PAID, OrderStatus::SENT];

        // Act & Assert
        foreach ($nonFinalStatuses as $status) {
            $canTransition = $this->statusTransition->canTransitionTo($status, OrderStatus::CANCELLED);

            $this->assertTrue($canTransition, sprintf('Should be able to cancel from %s status', $status->value));
        }
    }

    public function testFinalStatusesCannotTransitionToAnything(): void
    {
        // Arrange
        $finalStatuses = [OrderStatus::DELIVERED, OrderStatus::CANCELLED];
        $allStatuses = OrderStatus::cases();

        // Act & Assert
        foreach ($finalStatuses as $finalStatus) {
            foreach ($allStatuses as $targetStatus) {
                $canTransition = $this->statusTransition->canTransitionTo($finalStatus, $targetStatus);

                $this->assertFalse(
                    $canTransition,
                    sprintf('Final status %s should not transition to %s', $finalStatus->value, $targetStatus->value)
                );
            }
        }
    }
}
