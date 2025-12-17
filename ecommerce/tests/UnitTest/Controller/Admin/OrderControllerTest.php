<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wsei\Ecommerce\Controller\Admin\OrderController;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatus;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatusTransitionInterface;
use Wsei\Ecommerce\Repository\OrderRepository;

class OrderControllerTest extends TestCase
{
    private OrderController&MockObject $controller;

    private EntityManagerInterface&MockObject $entityManager;

    private OrderRepository&MockObject $orderRepository;

    private OrderStatusTransitionInterface&MockObject $statusTransition;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange - Create mocks for dependencies
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->statusTransition = $this->createMock(OrderStatusTransitionInterface::class);

        // Create partial mock of controller to mock render() method
        $this->controller = $this->getMockBuilder(OrderController::class)
            ->setConstructorArgs([$this->entityManager, $this->orderRepository, $this->statusTransition])
            ->onlyMethods(['render'])
            ->getMock();
    }

    #[DataProvider('providePageParameters')]
    public function testIndexPassesCorrectPageNumberToRepository(int $requestedPage, int $expectedPage): void
    {
        // Arrange
        $request = new Request([], [], [], [], [], [], null);
        $request->query->set('page', $requestedPage);

        // Assert - Verify correct page is passed to repository
        $this->orderRepository->expects(static::once())
            ->method('findAllPaginated')
            ->with($expectedPage, 20)
            ->willReturn([]);

        $this->orderRepository->method('countAll')->willReturn(0);
        $this->controller->method('render')->willReturn(new Response());

        // Act
        $this->controller->index($request);
    }

    /**
     * @return array<string, array{requestedPage: int, expectedPage: int}>
     */
    public static function providePageParameters(): array
    {
        return [
            'valid page 1' => [
                'requestedPage' => 1,
                'expectedPage' => 1,
            ],
            'valid page 5' => [
                'requestedPage' => 5,
                'expectedPage' => 5,
            ],
            'page 0 normalized to 1' => [
                'requestedPage' => 0,
                'expectedPage' => 1,
            ],
            'negative page normalized to 1' => [
                'requestedPage' => -5,
                'expectedPage' => 1,
            ],
        ];
    }

    public function testIndexPassesCorrectTemplateToRender(): void
    {
        // Arrange
        $request = new Request();

        $this->orderRepository->method('findAllPaginated')->willReturn([]);
        $this->orderRepository->method('countAll')->willReturn(0);

        // Assert - Verify correct template path
        $this->controller->expects(static::once())
            ->method('render')
            ->with('admin/pages/order/index.html.twig', $this->anything())
            ->willReturn(new Response());

        // Act
        $this->controller->index($request);
    }

    #[DataProvider('provideTotalPagesCalculation')]
    public function testIndexCalculatesTotalPagesCorrectly(int $totalOrders, int $expectedTotalPages): void
    {
        // Arrange
        $request = new Request();

        $this->orderRepository->method('findAllPaginated')->willReturn([]);

        // Act - Repository returns totalOrders
        $this->orderRepository->expects(static::once())
            ->method('countAll')
            ->willReturn($totalOrders);

        $this->controller->expects(static::once())
            ->method('render')
            ->with(
                'admin/pages/order/index.html.twig',
                self::callback(function (array $params) use ($expectedTotalPages): bool {
                    // Assert - Verify totalPages calculation: ceil(totalOrders / ORDERS_PER_PAGE)
                    return $params['totalPages'] === $expectedTotalPages;
                })
            )
            ->willReturn(new Response());

        // Act
        $this->controller->index($request);
    }

    /**
     * @return array<string, array{totalOrders: int, expectedTotalPages: int}>
     */
    public static function provideTotalPagesCalculation(): array
    {
        // ORDERS_PER_PAGE = 20
        return [
            '0 orders = 0 pages' => [
                'totalOrders' => 0,
                'expectedTotalPages' => 0,
            ],
            '20 orders = 1 page (exact)' => [
                'totalOrders' => 20,
                'expectedTotalPages' => 1,
            ],
            '21 orders = 2 pages (ceil)' => [
                'totalOrders' => 21,
                'expectedTotalPages' => 2,
            ],
            '40 orders = 2 pages (exact)' => [
                'totalOrders' => 40,
                'expectedTotalPages' => 2,
            ],
            '55 orders = 3 pages (ceil)' => [
                'totalOrders' => 55,
                'expectedTotalPages' => 3,
            ],
        ];
    }

    public function testShowRendersOrderDetailsWithForm(): void
    {
        // Arrange
        $request = new Request();
        $order = $this->createMock(Order::class);

        $order->method('getId')->willReturn(123);
        $order->method('getStatus')->willReturn(OrderStatus::NEW);
        $this->controller = $this->getMockBuilder(OrderController::class)
            ->setConstructorArgs([$this->entityManager, $this->orderRepository, $this->statusTransition])
            ->onlyMethods(['render', 'createForm'])
            ->getMock();

        $form = $this->createMock(FormInterface::class);
        $form->expects(static::once())
            ->method('handleRequest')
            ->with($request);

        $form->expects(static::once())
            ->method('isSubmitted')
            ->willReturn(false);

        $this->controller->expects(static::once())
            ->method('createForm')
            ->willReturn($form);

        // Assert
        $this->controller->expects(static::once())
            ->method('render')
            ->with(
                'admin/pages/order/show.html.twig',
                self::callback(fn (array $params) => isset($params['order'], $params['form']))
            )
            ->willReturn(new Response());

        // Act
        $this->controller->show($request, $order);
    }

    public function testShowValidatesStatusTransitionAndRejectsInvalidChange(): void
    {
        // Arrange
        $request = new Request();
        $order = $this->createMock(Order::class);

        $originalStatus = OrderStatus::DELIVERED;
        $newStatus = OrderStatus::NEW;

        $order->method('getId')->willReturn(123);
        // getStatus() is called twice: once for originalStatus, once for newStatus
        $order->expects(static::exactly(2))
            ->method('getStatus')
            ->willReturnOnConsecutiveCalls($originalStatus, $newStatus);

        $this->controller = $this->getMockBuilder(OrderController::class)
            ->setConstructorArgs([$this->entityManager, $this->orderRepository, $this->statusTransition])
            ->onlyMethods(['createForm', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $form = $this->createMock(FormInterface::class);

        // Assert
        $form->expects(static::once())
            ->method('handleRequest')
            ->with($request);

        $form->expects(static::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form->expects(static::once())
            ->method('isValid')
            ->willReturn(true);

        $this->controller->expects(static::once())
            ->method('createForm')
            ->willReturn($form);

        $this->statusTransition->expects(static::once())
            ->method('canTransitionTo')
            ->with($originalStatus, $newStatus)
            ->willReturn(false); // Invalid transition

        $this->entityManager->expects(static::never())
            ->method('flush');

        $this->controller->expects(static::once())
            ->method('addFlash')
            ->with('error', static::stringContains('Invalid status transition'));

        $this->controller->expects(static::once())
            ->method('redirectToRoute')
            ->with('admin.order.show', [
                'id' => 123,
            ])
            ->willReturn(new RedirectResponse('/admin/orders/123'));

        // Act
        $this->controller->show($request, $order);
    }
}
