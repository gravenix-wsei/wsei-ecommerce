<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;
use Wsei\Ecommerce\EcommerceApi\Attribute\RequestAttributes;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\UnauthorizedException;
use Wsei\Ecommerce\Repository\ApiTokenRepository;

class ApiTokenAuthenticationSubscriber
{
    public function __construct(
        private ApiTokenRepository $apiTokenRepository
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        // Only apply to Ecommerce API routes
        if (!str_starts_with($request->getPathInfo(), RequestAttributes::API_PREFIX)) {
            return;
        }
        $request->attributes->set(RequestAttributes::IS_ECOMMERCE_API, true);

        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }
        $reflectionMethod = new \ReflectionMethod($controller[0], $controller[1]);
        $attributes = $reflectionMethod->getAttributes(PublicAccess::class);

        if (count($attributes) > 0) {
            // Public endpoint, skip authentication
            return;
        }

        $token = $request->headers->get(RequestAttributes::TOKEN_HEADER);
        if ($token === null) {
            throw new UnauthorizedException();
        }

        $apiToken = $this->apiTokenRepository->findActiveTokenByValue($token);
        if ($apiToken === null) {
            throw new UnauthorizedException();
        }

        $request->attributes->set(RequestAttributes::AUTHENTICATED_CUSTOMER, $apiToken->getCustomer());
    }
}
