<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response;

use Wsei\Ecommerce\EcommerceApi\Response\Entity\CategoryResponse;
use Wsei\Ecommerce\Entity\Category;

class CategoryListResponse extends EcommerceResponse
{
    /**
     * @param Category[] $categories
     */
    public function __construct(
        private readonly array $categories,
        private readonly int $page,
        private readonly int $totalPages,
        private readonly ?string $nextPage,
        private readonly ?string $previousPage
    ) {
        parent::__construct();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatData(): array
    {
        return [
            'data' => \array_map(
                fn (Category $category) => new CategoryResponse($category)->formatResponse(),
                $this->categories
            ),
            'page' => $this->page,
            'totalPages' => $this->totalPages,
            'nextPage' => $this->nextPage,
            'previousPage' => $this->previousPage,
        ];
    }

    protected function getApiDescription(): ?string
    {
        return 'CategoryList';
    }
}
