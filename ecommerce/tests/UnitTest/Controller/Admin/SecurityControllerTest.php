<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Controller\Admin;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Wsei\Ecommerce\Controller\Admin\SecurityController;

class SecurityControllerTest extends TestCase
{
    private SecurityController&MockObject $controller;

    private AuthenticationUtils&MockObject $authenticationUtils;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange - Create mocks for dependencies
        $this->authenticationUtils = $this->createMock(AuthenticationUtils::class);

        // Create partial mock of controller to mock render(), getUser(), and redirectToRoute()
        $this->controller = $this->getMockBuilder(SecurityController::class)
            ->onlyMethods(['render', 'getUser', 'redirectToRoute'])
            ->getMock();
    }

    public function testLoginRendersLoginPageWhenUserIsNotLoggedIn(): void
    {
        // Arrange
        $lastUsername = 'test@example.com';
        $error = null;

        $this->controller->expects(static::once())
            ->method('getUser')
            ->willReturn(null);

        $this->authenticationUtils->expects(static::once())
            ->method('getLastAuthenticationError')
            ->willReturn($error);

        $this->authenticationUtils->expects(static::once())
            ->method('getLastUsername')
            ->willReturn($lastUsername);

        $expectedResponse = new Response('login page');
        $this->controller->expects(static::once())
            ->method('render')
            ->with('admin/security/login.html.twig', [
                'last_username' => $lastUsername,
                'error' => $error,
            ])
            ->willReturn($expectedResponse);

        // Act
        $response = $this->controller->login($this->authenticationUtils);

        // Assert
        static::assertSame($expectedResponse, $response);
    }

    public function testLoginRendersLoginPageWithError(): void
    {
        // Arrange
        $lastUsername = 'test@example.com';
        $error = new AuthenticationException('Invalid credentials');

        $this->controller->expects(static::once())
            ->method('getUser')
            ->willReturn(null);

        $this->authenticationUtils->expects(static::once())
            ->method('getLastAuthenticationError')
            ->willReturn($error);

        $this->authenticationUtils->expects(static::once())
            ->method('getLastUsername')
            ->willReturn($lastUsername);

        $expectedResponse = new Response('login page with error');
        $this->controller->expects(static::once())
            ->method('render')
            ->with('admin/security/login.html.twig', [
                'last_username' => $lastUsername,
                'error' => $error,
            ])
            ->willReturn($expectedResponse);

        // Act
        $response = $this->controller->login($this->authenticationUtils);

        // Assert
        static::assertSame($expectedResponse, $response);
    }

    public function testLoginRedirectsToAdminDashboardWhenUserIsAlreadyLoggedIn(): void
    {
        // Arrange
        $user = $this->createMock(UserInterface::class);

        $this->controller->expects(static::once())
            ->method('getUser')
            ->willReturn($user);

        $expectedResponse = new RedirectResponse('/admin/dashboard');
        $this->controller->expects(static::once())
            ->method('redirectToRoute')
            ->with('admin.dashboard')
            ->willReturn($expectedResponse);

        $this->authenticationUtils->expects($this->never())
            ->method('getLastAuthenticationError');

        $this->authenticationUtils->expects($this->never())
            ->method('getLastUsername');

        $this->controller->expects($this->never())
            ->method('render');

        // Act
        $response = $this->controller->login($this->authenticationUtils);

        // Assert
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame($expectedResponse->getTargetUrl(), $response->getTargetUrl());
    }

    public function testLogoutThrowsLogicException(): void
    {
        // Arrange
        $controller = new SecurityController();

        // Assert
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'This method can be blank - it will be intercepted by the logout key on your firewall.'
        );

        // Act
        $controller->logout();
    }
}
