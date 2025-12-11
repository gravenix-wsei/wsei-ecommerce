<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\BadRequestException;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\NotFoundException;
use Wsei\Ecommerce\EcommerceApi\Payload\AddCartItemPayload;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\CartItemResponse;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\CartResponse;
use Wsei\Ecommerce\EcommerceApi\Response\SuccessResponse;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Service\Cart\CartServiceInterface;

#[Route('/ecommerce/api/v1/cart')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
    ) {
    }

    #[Route('', name: 'ecommerce_api.cart.show', methods: ['GET'])]
    public function show(Customer $customer): CartResponse
    {
        $cart = $this->cartService->getOrCreateActiveCart($customer);

        return new CartResponse($cart);
    }

    #[Route('/items', name: 'ecommerce_api.cart.add_item', methods: ['POST'])]
    public function addItem(
        #[MapRequestPayload]
        AddCartItemPayload $payload,
        Customer $customer
    ): CartItemResponse {
        $cart = $this->cartService->getOrCreateActiveCart($customer);

        try {
            $cartItem = $this->cartService->addItem($cart, $payload->productId, $payload->quantity);

            return new CartItemResponse($cartItem);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestException($e->getMessage());
        }
    }

    #[Route('/items/{itemId}', name: 'ecommerce_api.cart.remove_item', methods: ['DELETE'])]
    public function removeItem(int $itemId, Customer $customer): Response
    {
        $cart = $this->cartService->getOrCreateActiveCart($customer);

        try {
            $this->cartService->removeItem($cart, $itemId);

            return new SuccessResponse();
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundException($e->getMessage());
        }
    }

    #[Route('', name: 'ecommerce_api.cart.clear', methods: ['DELETE'])]
    public function clear(Customer $customer): Response
    {
        $cart = $this->cartService->getOrCreateActiveCart($customer);
        $this->cartService->clearCart($cart);

        return new SuccessResponse();
    }
}
