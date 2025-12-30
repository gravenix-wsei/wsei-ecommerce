<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Wsei\Ecommerce\Entity\User;
use Wsei\Ecommerce\Framework\Security\AdminRole;

/**
 * @extends AbstractType<User>
 */
class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'input',
                ],
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $isEdit ? 'New Password (leave blank to keep current)' : 'Password',
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => [
                    'class' => 'input',
                ],
                'constraints' => $isEdit ? [] : [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'min' => 8,
                    ]),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Roles',
                'choices' => AdminRole::getChoices(),
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'checkbox-list',
                ],
                'help' => 'Select all roles that should be assigned to this administrator',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
