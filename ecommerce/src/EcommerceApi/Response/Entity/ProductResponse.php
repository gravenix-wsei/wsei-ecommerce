<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Product;

class ProductResponse extends EcommerceResponse
{
    public function __construct(
        private readonly Product $product
    ) {
        parent::__construct();
    }

    protected function formatData(): array
    {
        $data = [
            'id' => $this->product->getId(),
            'name' => $this->product->getName(),
            'description' => $this->product->getDescription(),
            'stock' => $this->product->getStock(),
            'priceNet' => $this->product->getPriceNet(),
            'priceGross' => $this->product->getPriceGross(),
            'category' => null,
        ];

        if ($this->product->getCategory() !== null) {
            $categoryResponse = new CategoryResponse($this->product->getCategory());
            $data['category'] = $categoryResponse->formatResponse();
        }

        return $data;
    }

    protected function getApiDescription(): string
    {
        return 'Product';
    }
}
