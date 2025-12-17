<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Checkout\Order;

interface OrderStatusTransitionInterface
{
    /**
     * Get all statuses that can be transitioned to from the given status.
     *
     * @return OrderStatus[]
     */
    public function getAllowedTransitions(OrderStatus $from): array;

    /**
     * Check if transition from one status to another is allowed.
     */
    public function canTransitionTo(OrderStatus $from, OrderStatus $to): bool;

    /**
     * Get all statuses that should be disabled (not allowed) from current status.
     *
     * @return OrderStatus[]
     */
    public function getDisabledStatuses(OrderStatus $current): array;
}
