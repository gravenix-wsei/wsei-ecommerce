<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Payment\Stripe;

use Stripe\Checkout\Session;

interface StripeClientInterface
{
    /**
     * @param array<string, mixed> $params
     */
    public function createCheckoutSession(array $params): Session;

    public function retrieveSession(string $sessionId): Session;
}
