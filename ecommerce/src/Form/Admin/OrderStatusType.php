<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatus;

/**
 * @extends AbstractType<Order>
 */
class OrderStatusType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', EnumType::class, [
                'class' => OrderStatus::class,
                'label' => 'Order Status',
                'required' => true,
                'choice_label' => fn (OrderStatus $status): string => ucfirst(str_replace('_', ' ', $status->value)),
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
