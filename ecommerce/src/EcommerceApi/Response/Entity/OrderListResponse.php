<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Order;

class OrderListResponse extends EcommerceResponse
{
    /**
     * @param Order[] $orders
     */
    public function __construct(
        private readonly array $orders,
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
            'data' => \array_map(fn (Order $order) => new OrderResponse($order)->formatResponse(), $this->orders),
            'page' => $this->page,
            'totalPages' => $this->totalPages,
            'nextPage' => $this->nextPage,
            'previousPage' => $this->previousPage,
        ];
    }

    protected function getApiDescription(): ?string
    {
        return 'OrderList';
    }
}
