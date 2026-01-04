<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Current Password',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Please enter your current password'),
                ],
                'attr' => [
                    'placeholder' => 'Enter your current password',
                    'autocomplete' => 'current-password',
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'mapped' => false,
                'first_options' => [
                    'label' => 'New Password',
                    'attr' => [
                        'placeholder' => 'Enter a new password',
                        'autocomplete' => 'new-password',
                    ],
                    'constraints' => [
                        new NotBlank(message: 'Please enter a new password'),
                        new Length(
                            min: 8,
                            minMessage: 'Your password should be at least {{ limit }} characters',
                            max: 4096,
                        ),
                        new Regex(
                            pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
                            message: 'Your password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&)',
                        ),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm New Password',
                    'attr' => [
                        'placeholder' => 'Re-enter your new password',
                        'autocomplete' => 'new-password',
                    ],
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
