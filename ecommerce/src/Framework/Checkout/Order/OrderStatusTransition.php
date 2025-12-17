<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Checkout\Order;

class OrderStatusTransition implements OrderStatusTransitionInterface
{
    /**
     * Define allowed status transitions.
     * Rules:
     * - NEW can go to PENDING_PAYMENT or CANCELLED
     * - PENDING_PAYMENT can go to PAID or CANCELLED
     * - PAID can go to SENT or CANCELLED
     * - SENT can go to DELIVERED or CANCELLED
     * - DELIVERED is final (no transitions)
     * - CANCELLED is final (no transitions)
     */
    public function getAllowedTransitions(OrderStatus $from): array
    {
        return match ($from) {
            OrderStatus::NEW => [OrderStatus::NEW, OrderStatus::PENDING_PAYMENT, OrderStatus::CANCELLED],
            OrderStatus::PENDING_PAYMENT => [
                OrderStatus::PENDING_PAYMENT,
                OrderStatus::PAID,
                OrderStatus::CANCELLED,
            ],
            OrderStatus::PAID => [OrderStatus::PAID, OrderStatus::SENT, OrderStatus::CANCELLED],
            OrderStatus::SENT => [OrderStatus::SENT, OrderStatus::DELIVERED, OrderStatus::CANCELLED],
            OrderStatus::DELIVERED => [
                OrderStatus::DELIVERED, // Only stay in DELIVERED
            ],
            OrderStatus::CANCELLED => [
                OrderStatus::CANCELLED, // Only stay in CANCELLED
            ],
        };
    }

    public function canTransitionTo(OrderStatus $from, OrderStatus $to): bool
    {
        $allowedTransitions = $this->getAllowedTransitions($from);

        return in_array($to, $allowedTransitions, true);
    }

    public function getDisabledStatuses(OrderStatus $current): array
    {
        $allStatuses = OrderStatus::cases();
        $allowedStatuses = $this->getAllowedTransitions($current);

        return array_filter(
            $allStatuses,
            fn (OrderStatus $status): bool => !in_array($status, $allowedStatuses, true)
        );
    }
}
