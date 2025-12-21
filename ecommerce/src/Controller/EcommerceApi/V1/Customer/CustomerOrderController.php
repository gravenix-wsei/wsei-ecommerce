<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\OrderListResponse;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Repository\OrderRepository;

#[Route('/ecommerce/api/v1/orders')]
class CustomerOrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    #[Route('', name: 'ecommerce_api.orders.list', methods: ['GET'])]
    public function list(Request $request, Customer $customer): OrderListResponse
    {
        $page = (int) $request->query->get('page', '1');
        $limit = (int) $request->query->get('limit', '20');

        $page = max(1, $page);
        $limit = max(1, $limit);

        $customerId = $customer->getId();
        assert($customerId !== null, 'Customer must have an ID');

        $orders = $this->orderRepository->findByCustomerPaginated($customerId, $page, $limit);
        $totalCount = $this->orderRepository->countByCustomer($customerId);
        $totalPages = (int) ceil($totalCount / $limit);
        $nextPage = $this->getNextPageLink($page, $totalPages, $limit);
        $previousPage = $this->getPreviousPageLink($page, $limit);

        return new OrderListResponse($orders, $page, $totalPages, $nextPage, $previousPage);
    }

    private function getNextPageLink(int $page, int $totalPages, int $limit): ?string
    {
        $nextPage = null;
        if ($page < $totalPages) {
            $nextPage = $this->urlGenerator->generate(
                'ecommerce_api.orders.list',
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
                'ecommerce_api.orders.list',
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

