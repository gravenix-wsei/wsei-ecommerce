<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final readonly class AccessDeniedListener
{
    public function __construct(
        private Environment $twig
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle AccessDeniedException for admin routes
        if (!$exception instanceof AccessDeniedException && !$exception instanceof AccessDeniedHttpException) {
            return;
        }

        // Check if request is for admin panel
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/admin') || str_starts_with($path, '/admin/login')) {
            return;
        }

        // Render 403 error within admin layout
        try {
            $content = $this->twig->render('admin/error/403.html.twig', [
                'exception' => $exception,
            ]);

            $response = new Response($content, Response::HTTP_FORBIDDEN);
            $event->setResponse($response);
        } catch (\Exception) {
            // If template rendering fails, let Symfony handle it
            return;
        }
    }
}
