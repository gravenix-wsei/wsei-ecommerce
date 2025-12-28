<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Payment\Stripe;

use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Entity\OrderItem;
use Wsei\Ecommerce\Entity\PaymentSession;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatus;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatusTransitionInterface;
use Wsei\Ecommerce\Framework\Payment\PaymentServiceInterface;
use Wsei\Ecommerce\Framework\Payment\Result\PaymentResult;
use Wsei\Ecommerce\Framework\Payment\Result\PaymentVerificationResult;
use Wsei\Ecommerce\Repository\PaymentSessionRepository;

class StripePaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly PaymentSessionRepository $paymentSessionRepository,
        private readonly OrderStatusTransitionInterface $statusTransition,
        private readonly string $stripeSecretKey
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function pay(Order $order, string $returnUrl): PaymentResult
    {
        $this->transitionToPendingPayment($order);

        // Cancel any active payment sessions for this order
        $this->paymentSessionRepository->cancelActiveSessionsForOrder($order);

        // Create new payment session
        $paymentSession = new PaymentSession();
        $paymentSession->setOrder($order);
        $paymentSession->setReturnUrl($returnUrl);

        // Prepare line items for Stripe
        $lineItems = [];
        foreach ($order->getItems() as $orderItem) {
            $product = $orderItem->getProduct();
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $product->getName(),
                    ],
                    'unit_amount' => (int) (bcmul($product->getPriceGross(), '100', 0)), // Convert to cents
                ],
                'quantity' => $orderItem->getQuantity(),
            ];
        }

        $verifyUrl = $this->urlGenerator->generate(
            'ecommerce_api.order.verify_payment',
            [
                'token' => $paymentSession->getToken(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Create Stripe checkout session
        $checkoutSession = Session::create([
            'line_items' => \array_map(
                static fn (OrderItem $orderItem) => [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $orderItem->getProductName(),
                        ],
                        'unit_amount' => (int) (bcmul($orderItem->getPriceGross(), '100', 0)), // Convert to cents
                    ],
                    'quantity' => $orderItem->getQuantity(),
                ],
                $order->getItems()->toArray()
            ),
            'mode' => 'payment',
            'success_url' => $verifyUrl,
            'cancel_url' => $returnUrl . '?payment_cancelled=1',
            'metadata' => [
                'order_id' => (string) $order->getId(),
                'payment_token' => (string) $paymentSession->getToken(),
            ],
        ]);

        // Store Stripe session ID
        $paymentSession->setStripeSessionId($checkoutSession->id);

        // Persist entities
        $this->entityManager->persist($paymentSession);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return new PaymentResult($checkoutSession->url, $paymentSession->getToken());
    }

    public function verify(string $token): PaymentVerificationResult
    {
        // Find valid payment session
        $paymentSession = $this->paymentSessionRepository->findValidByToken($token);
        if ($paymentSession === null) {
            return PaymentVerificationResult::failure('Invalid or expired payment token');
        }

        $order = $paymentSession->getOrder();
        $stripeSessionId = $paymentSession->getStripeSessionId();
        $returnUrl = $paymentSession->getReturnUrl();

        if (!$stripeSessionId) {
            return PaymentVerificationResult::failure('No Stripe session found', $order, $returnUrl);
        }

        try {
            // Retrieve Stripe session
            $stripeSession = Session::retrieve($stripeSessionId);

            if ($stripeSession->payment_status === 'paid') {
                $currentStatus = $order->getStatus();

                // Validate if order can transition to PAID
                if (!$this->statusTransition->canTransitionTo(
                    $currentStatus,
                    OrderStatus::PAID
                ) || $currentStatus === OrderStatus::PAID) {
                    return PaymentVerificationResult::failure(
                        sprintf('Order cannot transition from "%s" to "PAID"', $currentStatus->value),
                        $order,
                        $returnUrl
                    );
                }

                // Update order status to paid
                $order->setStatus(OrderStatus::PAID);

                // Mark payment session as completed
                $paymentSession->complete();

                $this->entityManager->persist($order);
                $this->entityManager->persist($paymentSession);
                $this->entityManager->flush();

                return PaymentVerificationResult::success($order, $returnUrl);
            }
            return PaymentVerificationResult::failure('Payment not completed', $order, $returnUrl);

        } catch (\Exception $e) {
            return PaymentVerificationResult::failure($e->getMessage(), $order, $returnUrl);
        }
    }

    private function transitionToPendingPayment(Order $order): void
    {
        $currentStatus = $order->getStatus();

        // Validate if order can transition to PENDING_PAYMENT
        if (!$this->statusTransition->canTransitionTo(
            $currentStatus,
            OrderStatus::PENDING_PAYMENT
        ) && $currentStatus->value !== OrderStatus::PENDING_PAYMENT->value) {
            throw new \InvalidArgumentException(
                sprintf('Order cannot transition from "%s" to "PENDING_PAYMENT"', $currentStatus->value)
            );
        }

        // Change order status to pending payment
        $order->setStatus(OrderStatus::PENDING_PAYMENT);
    }
}
