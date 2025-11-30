<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Wsei\Ecommerce\Entity\Admin\Customer;

/**
 * @extends AbstractType<Customer>
 */
class CustomerType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => true,
                'constraints' => [new NotBlank(), new Email()],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                    ]),
                ],
            ]);

        // Only add password field for new customers or when explicitly resetting password
        if (! $isEdit) {
            $builder->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'invalid_message' => 'The password fields must match.',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 6,
                    ]),
                ],
            ]);
        } else {
            // For edit, make password optional (only if user wants to change it)
            $builder->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'New Password (leave blank to keep current)',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Repeat New Password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'invalid_message' => 'The password fields must match.',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Length([
                        'min' => 6,
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
            'is_edit' => false,
        ]);
    }
}
