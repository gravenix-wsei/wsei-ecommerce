<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Checkout\Order;

enum OrderStatus: string
{
    case NEW = 'new';
    case PENDING_PAYMENT = 'pending_payment';
    case PAID = 'paid';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
