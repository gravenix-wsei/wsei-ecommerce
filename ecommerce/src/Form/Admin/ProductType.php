<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wsei\Ecommerce\Entity\Admin\Product;

/**
 * @extends AbstractType<Product>
 */
class ProductType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock',
                'required' => true,
            ])
            ->add('priceNet', MoneyType::class, [
                'label' => 'Price Net',
                'required' => true,
                'currency' => 'EUR',
            ])
            ->add('priceGross', MoneyType::class, [
                'label' => 'Price Gross',
                'required' => true,
                'currency' => 'EUR',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
