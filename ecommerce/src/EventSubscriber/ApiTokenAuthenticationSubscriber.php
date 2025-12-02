<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Wsei\Ecommerce\Attribute\PublicAccess;
use Wsei\Ecommerce\Repository\Admin\ApiTokenRepository;

class ApiTokenAuthenticationSubscriber implements EventSubscriberInterface
{
    private const API_PREFIX = '/ecommerce/api/v1/';
    private const TOKEN_HEADER = 'wsei-ecommerce-token';

    public function __construct(
        private ApiTokenRepository $apiTokenRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', -8],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        // Only apply to API routes
        if (! str_starts_with($request->getPathInfo(), self::API_PREFIX)) {
            return;
        }

        // Check if the controller method has PublicAccess attribute
        $controller = $event->getController();

        if (is_array($controller)) {
            $reflectionMethod = new \ReflectionMethod($controller[0], $controller[1]);
            $attributes = $reflectionMethod->getAttributes(PublicAccess::class);

            if (count($attributes) > 0) {
                // Public endpoint, skip authentication
                return;
            }
        }

        // Get token from header
        $token = $request->headers->get(self::TOKEN_HEADER);

        if ($token === null) {
            $event->setController(function () {
                return new JsonResponse([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid or expired token',
                ], Response::HTTP_UNAUTHORIZED);
            });
            return;
        }

        // Validate token
        $apiToken = $this->apiTokenRepository->findActiveTokenByValue($token);

        if ($apiToken === null) {
            $event->setController(function () {
                return new JsonResponse([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid or expired token',
                ], Response::HTTP_UNAUTHORIZED);
            });
            return;
        }

        // Store authenticated customer in request attributes
        $request->attributes->set('authenticated_customer', $apiToken->getCustomer());
    }
}

