<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Product;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;
use Wsei\Ecommerce\EcommerceApi\Payload\SearchProductsPayload;
use Wsei\Ecommerce\EcommerceApi\Response\ProductListResponse;
use Wsei\Ecommerce\Repository\ProductRepository;

#[Route('/ecommerce/api/v1/products')]
#[OA\Tag(name: 'Product')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository
    ) {
    }

    #[PublicAccess]
    #[Route('/search', name: 'ecommerce_api.products.search', methods: ['POST'])]
    #[OA\Post(
        path: '/products/search',
        summary: 'Search products',
        tags: ['Product'],
        description: 'Search and filter products by category with pagination',
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/SearchProductsPayload')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product search results',
                content: new OA\JsonContent(ref: '#/components/schemas/ProductListResponse')
            ),
        ]
    )]
    public function search(#[MapRequestPayload] SearchProductsPayload $payload): ProductListResponse
    {
        $page = max(1, $payload->page);
        $limit = max(1, $payload->limit);

        $products = $this->productRepository->findByCriteria($payload->categoryId, $page, $limit);
        $totalCount = $this->productRepository->countByCriteria($payload->categoryId);
        $totalPages = (int) ceil($totalCount / $limit);

        return new ProductListResponse($products, $page, $totalPages);
    }
}
