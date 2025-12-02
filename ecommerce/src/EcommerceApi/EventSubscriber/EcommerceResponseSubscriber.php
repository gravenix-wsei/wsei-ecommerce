<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ViewEvent;
use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;

class EcommerceResponseSubscriber
{
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();

        if (! $request->attributes->get('is_ecommerce_api', false)) {
            return;
        }

        $controllerResult = $event->getControllerResult();

        if ($controllerResult instanceof EcommerceResponse) {
            $event->setResponse($controllerResult);
        }
    }
}
