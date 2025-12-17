<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatus;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatusTransitionInterface;

/**
 * @extends AbstractType<Order>
 */
class OrderStatusType extends AbstractType
{
    public function __construct(
        private readonly OrderStatusTransitionInterface $statusTransition
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order|null $order */
        $order = $builder->getData();
        $currentStatus = $order?->getStatus() ?? OrderStatus::NEW;

        $builder
            ->add('status', EnumType::class, [
                'class' => OrderStatus::class,
                'label' => 'Order Status',
                'required' => true,
                'choice_label' => fn (OrderStatus $status): string => ucfirst(str_replace('_', ' ', $status->value)),
                'choice_attr' => function (OrderStatus $status) use ($currentStatus): array {
                    if (!$this->statusTransition->canTransitionTo($currentStatus, $status)) {
                        return [
                            'disabled' => 'disabled',
                            'class' => 'disabled-option',
                        ];
                    }

                    return [];
                },
                'attr' => [
                    'class' => 'status-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
