<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Cart;

use OpenApi\Attributes as OA;
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
use Wsei\Ecommerce\Framework\Checkout\Cart\CartServiceInterface;

#[Route('/ecommerce/api/v1/cart')]
#[OA\Tag(name: 'Cart')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
    ) {
    }

    #[Route('', name: 'ecommerce_api.cart.show', methods: ['GET'])]
    #[OA\Get(
        path: '/cart',
        summary: 'Get current cart',
        tags: ['Cart'],
        security: [[
            'ApiToken' => [],
        ]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current cart contents',
                content: new OA\JsonContent(ref: '#/components/schemas/CartResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(Customer $customer): CartResponse
    {
        $cart = $this->cartService->getOrCreateActiveCart($customer);

        return new CartResponse($cart);
    }

    #[Route('/items', name: 'ecommerce_api.cart.add_item', methods: ['POST'])]
    #[OA\Post(
        path: '/cart/items',
        summary: 'Add item to cart',
        tags: ['Cart'],
        security: [[
            'ApiToken' => [],
        ]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddCartItemPayload')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Item added to cart',
                content: new OA\JsonContent(ref: '#/components/schemas/CartItemResponse')
            ),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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
    #[OA\Delete(
        path: '/cart/items/{itemId}',
        summary: 'Remove item from cart',
        tags: ['Cart'],
        security: [[
            'ApiToken' => [],
        ]],
        parameters: [
            new OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Item removed'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Item not found'),
        ]
    )]
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
    #[OA\Delete(
        path: '/cart',
        summary: 'Clear cart',
        tags: ['Cart'],
        security: [[
            'ApiToken' => [],
        ]],
        responses: [
            new OA\Response(response: 204, description: 'Cart cleared'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function clear(Customer $customer): Response
    {
        $cart = $this->cartService->getOrCreateActiveCart($customer);
        $this->cartService->clearCart($cart);

        return new SuccessResponse();
    }
}
