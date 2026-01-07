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
    public function testIndexPassesCorrectPageNumberToRepository(
        int|string|null $requestedPage,
        int $expectedPage
    ): void {
        // Arrange
        $request = new Request([], [], [], [], [], [], null);
        if ($requestedPage !== null) {
            $request->query->set('page', $requestedPage);
        }

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
     * @return array<string, array{requestedPage: int|string|null, expectedPage: int}>
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
            'not requested page parameter' => [
                'requestedPage' => null,
                'expectedPage' => 1,
            ],
            'string as page parameter' => [
                'requestedPage' => 'foo',
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
            ->with('admin/pages/order/index.html.twig', static::anything())
            ->willReturn($this->createMock(Response::class));

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
            ->willReturn($this->createMock(Response::class));

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

    #[DataProvider('provideShowRendersOrderDetailsWithForm')]
    public function testShowRendersOrderDetailsWithForm(
        bool $expectRedirectInsteadOfRendering,
        bool $isSubmitted,
        bool $isValid = true,
        bool $isStatusChangeAllowed = true,
        ?string $flashTypeAdded = null,
        string $flashMessage = ''
    ): void {
        // Arrange
        $orderId = 123;
        $request = new Request();
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(OrderStatus::NEW);

        $order->method('getId')->willReturn($orderId);
        $order->method('getStatus')->willReturn(OrderStatus::NEW);
        $this->controller = $this->getMockBuilder(OrderController::class)
            ->setConstructorArgs([$this->entityManager, $this->orderRepository, $this->statusTransition])
            ->onlyMethods(['render', 'addFlash', 'createForm', 'redirectToRoute'])
            ->getMock();

        $form = $this->createMock(FormInterface::class);
        $form->expects(static::once())
            ->method('handleRequest')
            ->with($request);

        $form->expects(static::once())->method('isSubmitted')
            ->willReturn($isSubmitted);

        $form->expects($isSubmitted ? static::once() : static::never())
            ->method('isValid')
            ->willReturn($isValid);

        $this->controller->expects(static::once())
            ->method('createForm')
            ->willReturn($form);

        $this->statusTransition
            ->method('canTransitionTo')
            ->willReturn($isStatusChangeAllowed);

        $this->entityManager
            ->expects(($isSubmitted && $isValid && $isStatusChangeAllowed) ? static::once() : static::never())
            ->method('flush');

        if ($flashTypeAdded) {
            $this->controller->expects(static::once())
                ->method('addFlash')
                ->with($flashTypeAdded, static::stringContains($flashMessage));
        }

        // Assert
        $this->controller->expects($expectRedirectInsteadOfRendering ? static::once() : static::never())
            ->method('redirectToRoute')
            ->with('admin.order.show', [
                'id' => $orderId,
            ])
            ->willReturn($this->createMock(RedirectResponse::class));
        $this->controller->expects($expectRedirectInsteadOfRendering ? static::never() : static::once())
            ->method('render')
            ->with(
                'admin/pages/order/show.html.twig',
                self::callback(fn (array $params) => isset($params['order'], $params['form']))
            )
            ->willReturn($this->createMock(Response::class));

        // Act
        $this->controller->show($request, $order);
    }

    /**
     * @return array<string, array{
     *     expectRedirectInsteadOfRendering: bool,
     *     isSubmitted: bool,
     *     isValid?: bool,
     *     isStatusChangeAllowed?: bool,
     *     flashTypeAdded?: string,
     *     flashMessage?: string}>
     */
    public static function provideShowRendersOrderDetailsWithForm(): array
    {
        return [
            'submitted form with invalid data' => [
                'expectRedirectInsteadOfRendering' => false,
                'isSubmitted' => true,
                'isValid' => false,
            ],
            'submitted form with valid data' => [
                'expectRedirectInsteadOfRendering' => true,
                'isSubmitted' => true,
                'flashTypeAdded' => 'success',
                'flashMessage' => 'updated successfully',
            ],
            'renders form on GET request' => [
                'expectRedirectInsteadOfRendering' => false,
                'isSubmitted' => false,
            ],
            'transition is not allowed' => [
                'expectRedirectInsteadOfRendering' => true,
                'isSubmitted' => true,
                'isStatusChangeAllowed' => false,
                'flashTypeAdded' => 'error',
                'flashMessage' => 'invalid status transition',
            ],
        ];
    }
}
