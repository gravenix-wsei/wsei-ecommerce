<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Form\Admin\OrderStatusType;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatusTransitionInterface;
use Wsei\Ecommerce\Repository\OrderRepository;
use function max;

#[Route('/admin/orders')]
#[IsGranted('ROLE_ADMIN.ORDER')]
class OrderController extends AbstractController
{
    private const ORDERS_PER_PAGE = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly OrderStatusTransitionInterface $statusTransition
    ) {
    }

    #[Route('', name: 'admin.order.index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', '1'));

        $orders = $this->orderRepository->findAllPaginated($page, self::ORDERS_PER_PAGE);
        $totalOrders = $this->orderRepository->countAll();
        $totalPages = (int) ceil($totalOrders / self::ORDERS_PER_PAGE);

        return $this->render('admin/pages/order/index.html.twig', [
            'orders' => $orders,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/{id}', name: 'admin.order.show', methods: ['GET', 'POST'])]
    public function show(Request $request, Order $order): Response
    {
        $originalStatus = $order->getStatus();

        $form = $this->createForm(OrderStatusType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newStatus = $order->getStatus();

            // Validate status transition
            if (!$this->statusTransition->canTransitionTo($originalStatus, $newStatus)) {
                $this->addFlash('error', sprintf(
                    'Invalid status transition from "%s" to "%s".',
                    $originalStatus->value,
                    $newStatus->value
                ));

                return $this->redirectToRoute('admin.order.show', [
                    'id' => $order->getId(),
                ]);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Order status has been updated successfully.');

            return $this->redirectToRoute('admin.order.show', [
                'id' => $order->getId(),
            ]);
        }

        return $this->render('admin/pages/order/show.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }
}
