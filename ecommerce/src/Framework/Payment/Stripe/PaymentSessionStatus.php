<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Payment\Stripe;

enum PaymentSessionStatus: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
}
