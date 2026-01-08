<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\EcommerceApi\EventSubscriber;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;
use Wsei\Ecommerce\EcommerceApi\Attribute\RequestAttributes;
use Wsei\Ecommerce\EcommerceApi\EventSubscriber\ApiTokenAuthenticationSubscriber;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\UnauthorizedException;
use Wsei\Ecommerce\Entity\ApiToken;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Repository\ApiTokenRepository;

class ApiTokenAuthenticationSubscriberTest extends TestCase
{
    private ApiTokenRepository&MockObject $apiTokenRepository;

    private HttpKernelInterface&MockObject $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiTokenRepository = $this->createMock(ApiTokenRepository::class);
        $this->kernel = $this->createMock(HttpKernelInterface::class);
    }

    #[DataProvider('provideNonApiPaths')]
    public function testSkipsNonApiPaths(string $path): void
    {
        // Arrange
        $subscriber = $this->createSubscriber();
        $request = Request::create($path);
        $controller = [$this, 'dummyController'];
        $event = new ControllerEvent($this->kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->apiTokenRepository->expects(static::never())
            ->method('findActiveTokenByValue');

        // Act
        $subscriber->onKernelController($event);

        // Assert
        static::assertFalse($request->attributes->has(RequestAttributes::IS_ECOMMERCE_API));
        static::assertFalse($request->attributes->has(RequestAttributes::AUTHENTICATED_CUSTOMER));
    }

    public function testSetsApiAttributeForApiPaths(): void
    {
        // Arrange
        $subscriber = $this->createSubscriber();
        $request = Request::create('/ecommerce/api/v1/public');
        $controller = [$this, 'publicController'];
        $event = new ControllerEvent($this->kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        // Act
        $subscriber->onKernelController($event);

        // Assert
        static::assertTrue($request->attributes->get(RequestAttributes::IS_ECOMMERCE_API));
    }

    public function testSkipsAuthenticationForPublicEndpoints(): void
    {
        // Arrange
        $subscriber = $this->createSubscriber();
        $request = Request::create('/ecommerce/api/v1/public');
        $controller = [$this, 'publicController'];
        $event = new ControllerEvent($this->kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->apiTokenRepository->expects(static::never())
            ->method('findActiveTokenByValue');

        // Act
        $subscriber->onKernelController($event);

        // Assert
        static::assertTrue($request->attributes->get(RequestAttributes::IS_ECOMMERCE_API));
        static::assertFalse($request->attributes->has(RequestAttributes::AUTHENTICATED_CUSTOMER));
    }

    public function testThrowsUnauthorizedExceptionWhenTokenHeaderMissing(): void
    {
        // Arrange
        $subscriber = $this->createSubscriber();
        $request = Request::create('/ecommerce/api/v1/orders');
        $controller = [$this, 'protectedController'];
        $event = new ControllerEvent($this->kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        // Assert
        $this->expectException(UnauthorizedException::class);

        // Act
        $subscriber->onKernelController($event);
    }

    public function testThrowsUnauthorizedExceptionWhenTokenIsInvalid(): void
    {
        // Arrange
        $subscriber = $this->createSubscriber();
        $request = Request::create('/ecommerce/api/v1/orders');
        $request->headers->set(RequestAttributes::TOKEN_HEADER, 'invalid-token');
        $controller = [$this, 'protectedController'];
        $event = new ControllerEvent($this->kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->apiTokenRepository->expects(static::once())
            ->method('findActiveTokenByValue')
            ->with('invalid-token')
            ->willReturn(null);

        // Assert
        $this->expectException(UnauthorizedException::class);

        // Act
        $subscriber->onKernelController($event);
    }

    public function testSetsAuthenticatedCustomerWhenTokenIsValid(): void
    {
        // Arrange
        $subscriber = $this->createSubscriber();
        $customer = $this->createMock(Customer::class);
        $apiToken = $this->createMock(ApiToken::class);
        $apiToken->expects(static::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $request = Request::create('/ecommerce/api/v1/orders');
        $request->headers->set(RequestAttributes::TOKEN_HEADER, 'valid-token-123');
        $controller = [$this, 'protectedController'];
        $event = new ControllerEvent($this->kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->apiTokenRepository->expects(static::once())
            ->method('findActiveTokenByValue')
            ->with('valid-token-123')
            ->willReturn($apiToken);

        // Act
        $subscriber->onKernelController($event);

        // Assert
        static::assertTrue($request->attributes->get(RequestAttributes::IS_ECOMMERCE_API));
        static::assertSame($customer, $request->attributes->get(RequestAttributes::AUTHENTICATED_CUSTOMER));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideNonApiPaths(): array
    {
        return [
            'admin path' => ['/admin/products'],
            'root path' => ['/'],
            'shop path' => ['/shop/products'],
            'cart path' => ['/cart'],
            'checkout path' => ['/checkout'],
            'api without prefix' => ['/api/products'],
        ];
    }

    #[PublicAccess]
    public function publicController(): void
    {
    }

    public function protectedController(): void
    {
    }

    public function dummyController(): void
    {
    }

    private function createSubscriber(): ApiTokenAuthenticationSubscriber
    {
        return new ApiTokenAuthenticationSubscriber($this->apiTokenRepository);
    }
}
