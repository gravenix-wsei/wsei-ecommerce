<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Payment\Stripe;

use Stripe\Checkout\Session;
use Stripe\StripeClient as StripeSDKClient;

class StripeClient implements StripeClientInterface
{
    private readonly StripeSDKClient $client;

    public function __construct(string $stripeSecretKey)
    {
        $this->client = new StripeSDKClient($stripeSecretKey);
    }

    public function createCheckoutSession(array $params): Session
    {
        return $this->client->checkout->sessions->create($params);
    }

    public function retrieveSession(string $sessionId): Session
    {
        return $this->client->checkout->sessions->retrieve($sessionId);
    }
}
