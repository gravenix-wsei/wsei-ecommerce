<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Cart;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\BadRequestException;
use Wsei\Ecommerce\EcommerceApi\Payload\PlaceOrderPayload;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\OrderResponse;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Framework\Checkout\Cart\CartServiceInterface;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderServiceInterface;

#[Route('/ecommerce/api/v1/cart')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
        private readonly OrderServiceInterface $orderService,
    ) {
    }

    #[Route('/order', name: 'ecommerce_api.cart.place_order', methods: ['POST'])]
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
}
