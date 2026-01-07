<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\EventListener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;
use Wsei\Ecommerce\EventListener\AccessDeniedListener;

class AccessDeniedListenerTest extends TestCase
{
    private AccessDeniedListener $listener;

    private Environment&MockObject $twig;

    private HttpKernelInterface&MockObject $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twig = $this->createMock(Environment::class);
        $this->kernel = $this->createMock(HttpKernelInterface::class);
        $this->listener = new AccessDeniedListener($this->twig);
    }

    public function testListenerIgnoresNonAccessDeniedExceptions(): void
    {
        // Arrange
        $exception = new NotFoundHttpException('Not found');
        $request = Request::create('/admin/product');
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->never())
            ->method('render');

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testListenerHandlesAccessDeniedException(): void
    {
        // Arrange
        $exception = new AccessDeniedException('Access denied');
        $request = Request::create('/admin/product');
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('admin/error/403.html.twig', [
                'exception' => $exception,
            ])
            ->willReturn('<html>403 Error</html>');

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertNotNull($event->getResponse());
        $this->assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
        $this->assertSame('<html>403 Error</html>', $event->getResponse()->getContent());
    }

    public function testListenerHandlesAccessDeniedHttpException(): void
    {
        // Arrange
        $exception = new AccessDeniedHttpException('Forbidden');
        $request = Request::create('/admin/settings');
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('admin/error/403.html.twig', [
                'exception' => $exception,
            ])
            ->willReturn('<html>403 Error</html>');

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertNotNull($event->getResponse());
        $this->assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    #[DataProvider('provideNonAdminPaths')]
    public function testListenerIgnoresNonAdminPaths(string $path): void
    {
        // Arrange
        $exception = new AccessDeniedException('Access denied');
        $request = Request::create($path);
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->never())
            ->method('render');

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    /**
     * @return iterable<string, array<string>>
     */
    public static function provideNonAdminPaths(): iterable
    {
        return [
            'root path' => ['/'],
            'public product page' => ['/product/123'],
            'api endpoint' => ['/api/products'],
            'cart page' => ['/cart'],
            'empty path' => [''],
            'user profile' => ['/user/profile'],
        ];
    }

    public function testListenerIgnoresAdminLoginPath(): void
    {
        // Arrange
        $exception = new AccessDeniedException('Access denied');
        $request = Request::create('/admin/login');
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->never())
            ->method('render');

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    #[DataProvider('provideAdminPaths')]
    public function testListenerHandlesVariousAdminPaths(string $path): void
    {
        // Arrange
        $exception = new AccessDeniedException('Access denied');
        $request = Request::create($path);
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>403</html>');

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertNotNull($event->getResponse());
        $this->assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    /**
     * @return iterable<string, array<string>>
     */
    public static function provideAdminPaths(): iterable
    {
        return [
            'admin root' => ['/admin'],
            'admin dashboard' => ['/admin/dashboard'],
            'admin product' => ['/admin/product'],
            'admin product new' => ['/admin/product/new'],
            'admin product edit' => ['/admin/product/123/edit'],
            'admin settings' => ['/admin/settings'],
            'admin customer' => ['/admin/customer'],
            'admin orders' => ['/admin/orders'],
            'admin with trailing slash' => ['/admin/product/'],
        ];
    }

    public function testListenerHandlesTemplateRenderingException(): void
    {
        // Arrange
        $exception = new AccessDeniedException('Access denied');
        $request = Request::create('/admin/product');
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->once())
            ->method('render')
            ->willThrowException(new \RuntimeException('Template not found'));

        // Act
        ($this->listener)($event);

        // Assert - Should not set response when rendering fails
        $this->assertNull($event->getResponse());
    }

    public function testListenerHandlesTwigException(): void
    {
        // Arrange
        $exception = new AccessDeniedException('Access denied');
        $request = Request::create('/admin/settings');
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->once())
            ->method('render')
            ->willThrowException(new \Exception('Twig error'));

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testListenerPassesExceptionToTemplate(): void
    {
        // Arrange
        $exception = new AccessDeniedException('Custom access denied message');
        $request = Request::create('/admin/product');
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'admin/error/403.html.twig',
                $this->callback(function (array $context) use ($exception) {
                    return isset($context['exception'])
                        && $context['exception'] === $exception;
                })
            )
            ->willReturn('<html>Error</html>');

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertNotNull($event->getResponse());
    }

    public function testListenerSetsCorrectResponseStatusCode(): void
    {
        // Arrange
        $exception = new AccessDeniedException('Access denied');
        $request = Request::create('/admin/product');
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>403 Error Page</html>');

        // Act
        ($this->listener)($event);

        // Assert
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('<html>403 Error Page</html>', $response->getContent());
    }

    public function testListenerWorksWithAdminPathsContainingLoginSubstring(): void
    {
        // Arrange - Path contains "login" but doesn't start with "/admin/login"
        $exception = new AccessDeniedException('Access denied');
        $request = Request::create('/admin/user/login-history');
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>403</html>');

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertNotNull($event->getResponse());
        $this->assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testListenerIgnoresExactAdminLoginPath(): void
    {
        // Arrange
        $exception = new AccessDeniedException('Access denied');
        $request = Request::create('/admin/login');
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->never())
            ->method('render');

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testListenerIgnoresAdminLoginWithQueryString(): void
    {
        // Arrange
        $exception = new AccessDeniedException('Access denied');
        $request = Request::create('/admin/login?redirect=/admin/product');
        $event = new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->twig->expects($this->never())
            ->method('render');

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertNull($event->getResponse());
    }
}
