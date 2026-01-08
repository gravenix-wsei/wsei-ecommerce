<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Cart;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\BadRequestException;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\NotFoundException;
use Wsei\Ecommerce\EcommerceApi\Payload\InitiatePaymentPayload;
use Wsei\Ecommerce\EcommerceApi\Payload\PlaceOrderPayload;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\OrderResponse;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\PaymentUrlResponse;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Framework\Checkout\Cart\CartServiceInterface;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderServiceInterface;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatus;
use Wsei\Ecommerce\Framework\Payment\PaymentServiceInterface;
use Wsei\Ecommerce\Repository\OrderRepository;

#[Route('/ecommerce/api/v1')]
#[OA\Tag(name: 'Order')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
        private readonly OrderServiceInterface $orderService,
        private readonly PaymentServiceInterface $paymentService,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    #[Route('/cart/order', name: 'ecommerce_api.cart.place_order', methods: ['POST'])]
    #[OA\Post(
        path: '/cart/order',
        summary: 'Place order from cart',
        tags: ['Order'],
        security: [[
            'ApiToken' => [],
        ]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PlaceOrderPayload')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order placed successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/OrderResponse')
            ),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function placeOrder(#[MapRequestPayload] PlaceOrderPayload $payload, Customer $customer): OrderResponse
    {
        $cart = $this->cartService->getOrCreateActiveCart($customer);

        try {
            $order = $this->orderService->placeOrder($cart, $payload->addressId);

            return new OrderResponse($order);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestException($e->getMessage());
        }
    }

    #[Route('/order/pay/{orderId}', name: 'ecommerce_api.order.pay', methods: ['POST'])]
    #[OA\Post(
        path: '/order/pay/{orderId}',
        summary: 'Initiate payment for order',
        tags: ['Order'],
        security: [[
            'ApiToken' => [],
        ]],
        parameters: [
            new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/InitiatePaymentPayload')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment URL generated',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentUrlResponse')
            ),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function pay(
        int $orderId,
        #[MapRequestPayload]
        InitiatePaymentPayload $payload,
        Customer $customer
    ): PaymentUrlResponse {
        // Find order and validate ownership
        $order = $this->orderRepository->find($orderId);
        $this->validateOrderForPayment($order, $customer);

        try {
            $paymentResult = $this->paymentService->pay($order, $payload->returnUrl);

            return new PaymentUrlResponse($paymentResult->getPaymentUrl(), $paymentResult->getToken());
        } catch (\Exception $e) {
            throw new BadRequestException('Failed to initiate payment: ' . $e->getMessage());
        }
    }

    #[PublicAccess]
    #[Route('/order/verify-payment', name: 'ecommerce_api.order.verify_payment', methods: ['GET'])]
    #[OA\Get(
        path: '/order/verify-payment',
        summary: 'Verify payment callback',
        tags: ['Order'],
        description: 'Payment gateway callback endpoint. Redirects to return URL with payment status.',
        parameters: [
            new OA\Parameter(name: 'token', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 302, description: 'Redirect to return URL'),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 404, description: 'Payment not found'),
        ]
    )]
    public function verifyPayment(Request $request): RedirectResponse
    {
        $token = $request->query->get('token');
        if (!$token) {
            throw new BadRequestException('Payment token is required');
        }

        $verificationResult = $this->paymentService->verify($token);

        if (!$verificationResult->isSuccess()) {
            if (!$verificationResult->hasReturnUrl()) {
                throw new NotFoundException($verificationResult->getMessage());
            }

            $message = $verificationResult->getMessage() ?? 'Payment verification failed';
            $returnUrl = $verificationResult->getReturnUrl() . '?payment_error=1&message=' . urlencode($message);
            return new RedirectResponse($returnUrl);
        }

        // Payment successful
        $returnUrl = $verificationResult->getReturnUrl() . '?payment_success=1&order_id=' . $verificationResult->getOrder()->getId();
        return new RedirectResponse($returnUrl);
    }

    private function validateOrderForPayment(?Order $order, Customer $customer): void
    {
        if ($order === null) {
            throw new NotFoundException('Order not found');
        }

        if ($order->getCustomer()->getId() !== $customer->getId()) {
            throw new NotFoundException('Order not found');
        }

        // Validate order status
        if ($order->getStatus() !== OrderStatus::NEW && $order->getStatus() !== OrderStatus::PENDING_PAYMENT) {
            throw new BadRequestException('Order cannot be paid in current status: ' . $order->getStatus()->value);
        }
    }
}
