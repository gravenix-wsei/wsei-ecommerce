<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatus;

final class OrderStatusExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('order_status_badge', [$this, 'getStatusBadgeClass']),
            new TwigFilter('order_status_label', [$this, 'getStatusLabel']),
        ];
    }

    public function getStatusBadgeClass(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::NEW => 'badge-new',
            OrderStatus::PENDING_PAYMENT => 'badge-pending',
            OrderStatus::PAID => 'badge-paid',
            OrderStatus::SENT => 'badge-sent',
            OrderStatus::DELIVERED => 'badge-delivered',
            OrderStatus::CANCELLED => 'badge-cancelled',
        };
    }

    public function getStatusLabel(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::NEW => 'New',
            OrderStatus::PENDING_PAYMENT => 'Pending Payment',
            OrderStatus::PAID => 'Paid',
            OrderStatus::SENT => 'Sent',
            OrderStatus::DELIVERED => 'Delivered',
            OrderStatus::CANCELLED => 'Cancelled',
        };
    }
}
