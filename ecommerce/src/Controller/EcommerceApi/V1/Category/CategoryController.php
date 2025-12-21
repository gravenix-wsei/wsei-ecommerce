<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Category;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;
use Wsei\Ecommerce\EcommerceApi\Response\CategoryListResponse;
use Wsei\Ecommerce\Repository\CategoryRepository;

#[Route('/ecommerce/api/v1/categories')]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    #[PublicAccess]
    #[Route('', name: 'ecommerce_api.categories.list', methods: ['GET'])]
    public function list(Request $request): CategoryListResponse
    {
        $page = (int) $request->query->get('page', '1');
        $limit = (int) $request->query->get('limit', '20');

        $page = max(1, $page);
        $limit = max(1, min(100, $limit));

        $categories = $this->categoryRepository->findAllPaginated($page, $limit);
        $totalCount = $this->categoryRepository->countAll();
        $totalPages = (int) ceil($totalCount / $limit);
        $nextPage = $this->getNextPageLink($page, $totalPages, $limit);
        $previousPage = $this->getPreviousPageLink($page, $limit);

        return new CategoryListResponse($categories, $page, $totalPages, $nextPage, $previousPage);
    }

    private function getNextPageLink(int $page, int $totalPages, int $limit): ?string
    {
        $nextPage = null;
        if ($page < $totalPages) {
            $nextPage = $this->urlGenerator->generate(
                'ecommerce_api.categories.list',
                [
                    'page' => $page + 1,
                    'limit' => $limit,
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }
        return $nextPage;
    }

    private function getPreviousPageLink(int $page, int $limit): ?string
    {
        $previousPage = null;
        if ($page > 1) {
            $previousPage = $this->urlGenerator->generate(
                'ecommerce_api.categories.list',
                [
                    'page' => $page - 1,
                    'limit' => $limit,
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }
        return $previousPage;
    }
}
