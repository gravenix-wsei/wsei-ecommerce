<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Category;

class CategoryResponse extends EcommerceResponse
{
    public function __construct(
        private readonly Category $category
    ) {
        parent::__construct();
    }

    protected function formatData(): array
    {
        return [
            'id' => $this->category->getId(),
            'name' => $this->category->getName(),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'Category';
    }
}
