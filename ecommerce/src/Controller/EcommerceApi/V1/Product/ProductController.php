<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Product;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;
use Wsei\Ecommerce\EcommerceApi\Payload\SearchProductsPayload;
use Wsei\Ecommerce\EcommerceApi\Response\ProductListResponse;
use Wsei\Ecommerce\Repository\ProductRepository;

#[Route('/ecommerce/api/v1/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository
    ) {
    }

    #[PublicAccess]
    #[Route('/search', name: 'ecommerce_api.products.search', methods: ['POST'])]
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
