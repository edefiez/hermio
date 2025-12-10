<?php

namespace App\Form;

use App\Entity\Card;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CardFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'card.name',
                'required' => true,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.name.placeholder'],
                'constraints' => [
                    new NotBlank(message: 'card.name.required'),
                    new Length(max: 255, maxMessage: 'card.name.max_length'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'card.email',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.email.placeholder'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'card.phone',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.phone.placeholder'],
                'constraints' => [
                    new Length(max: 50, maxMessage: 'card.phone.max_length'),
                ],
            ])
            ->add('company', TextType::class, [
                'label' => 'card.company',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.company.placeholder'],
                'constraints' => [
                    new Length(max: 255, maxMessage: 'card.company.max_length'),
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'card.title',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.title.placeholder'],
                'constraints' => [
                    new Length(max: 255, maxMessage: 'card.title.max_length'),
                ],
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'card.bio',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'card.bio.placeholder',
                    'rows' => 5,
                ],
                'constraints' => [
                    new Length(max: 1000, maxMessage: 'card.bio.max_length'),
                ],
            ])
            ->add('website', UrlType::class, [
                'label' => 'card.website',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.website.placeholder'],
            ])
            ->add('linkedin', UrlType::class, [
                'label' => 'card.linkedin',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.linkedin.placeholder'],
            ])
            ->add('twitter', UrlType::class, [
                'label' => 'card.twitter',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.twitter.placeholder'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
        ]);
    }
}

