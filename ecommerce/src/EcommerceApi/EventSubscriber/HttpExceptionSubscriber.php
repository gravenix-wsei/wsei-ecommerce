<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\HttpException;

class HttpExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // Only apply to ecommerce API routes
        if (! $request->attributes->get('is_ecommerce_api', false)) {
            return;
        }

        $exception = $event->getThrowable();

        // Handle our custom HTTP exceptions
        if ($exception instanceof HttpException) {
            $response = new JsonResponse($exception->toArray(), $exception->getStatusCode());

            $event->setResponse($response);
        }
    }
}
