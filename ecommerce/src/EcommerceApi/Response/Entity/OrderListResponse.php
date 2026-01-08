<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use OpenApi\Attributes as OA;
use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Order;

#[OA\Schema(
    schema: 'OrderListResponse',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrderResponse')
        ),
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'totalPages', type: 'integer', example: 5),
        new OA\Property(property: 'nextPage', type: 'string', format: 'uri', example: 'http://localhost/ecommerce/api/v1/orders?page=2&limit=20', nullable: true),
        new OA\Property(property: 'previousPage', type: 'string', format: 'uri', example: null, nullable: true),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'OrderList'),
    ]
)]
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
