<?php

namespace App\Form;

use App\Entity\AccountBranding;
use App\Service\BrandingService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Regex;

class BrandingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('primaryColor', TextType::class, [
                'required' => false,
                'label' => 'branding.primary_color',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '#007BFF',
                    'pattern' => '^#[0-9A-Fa-f]{6}$',
                    'maxlength' => 7,
                ],
                'constraints' => [
                    new Regex(
                        pattern: '/^#[0-9A-Fa-f]{6}$/',
                        message: 'branding.color.invalid_format'
                    ),
                ],
            ])
            ->add('secondaryColor', TextType::class, [
                'required' => false,
                'label' => 'branding.secondary_color',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '#6c757d',
                    'pattern' => '^#[0-9A-Fa-f]{6}$',
                    'maxlength' => 7,
                ],
                'constraints' => [
                    new Regex(
                        pattern: '/^#[0-9A-Fa-f]{6}$/',
                        message: 'branding.color.invalid_format'
                    ),
                ],
            ])
            ->add('logo', FileType::class, [
                'required' => false,
                'label' => 'branding.logo.title',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/png,image/jpeg,image/jpg,image/svg+xml',
                ],
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'image/png',
                            'image/jpeg',
                            'image/jpg',
                            'image/svg+xml',
                        ],
                        mimeTypesMessage: 'branding.logo.invalid_type',
                    ),
                ],
            ])
            ->add('logoPosition', ChoiceType::class, [
                'required' => false,
                'label' => 'branding.logo.position',
                'attr' => [
                    'class' => 'form-select',
                ],
                'choices' => [
                    'branding.logo.position.top_left' => 'top-left',
                    'branding.logo.position.top_center' => 'top-center',
                    'branding.logo.position.top_right' => 'top-right',
                    'branding.logo.position.bottom_left' => 'bottom-left',
                    'branding.logo.position.bottom_center' => 'bottom-center',
                    'branding.logo.position.bottom_right' => 'bottom-right',
                ],
            ])
            ->add('logoSize', ChoiceType::class, [
                'required' => false,
                'label' => 'branding.logo.size',
                'attr' => [
                    'class' => 'form-select',
                ],
                'choices' => [
                    'branding.logo.size.small' => 'small',
                    'branding.logo.size.medium' => 'medium',
                    'branding.logo.size.large' => 'large',
                ],
            ])
            ->add('secondLogo', FileType::class, [
                'required' => false,
                'label' => 'branding.second_logo.title',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/png,image/jpeg,image/jpg,image/svg+xml',
                ],
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'image/png',
                            'image/jpeg',
                            'image/jpg',
                            'image/svg+xml',
                        ],
                        mimeTypesMessage: 'branding.logo.invalid_type',
                    ),
                ],
            ])
            ->add('secondLogoPosition', ChoiceType::class, [
                'required' => false,
                'label' => 'branding.second_logo.position',
                'attr' => [
                    'class' => 'form-select',
                ],
                'choices' => [
                    'branding.logo.position.top_left' => 'top-left',
                    'branding.logo.position.top_center' => 'top-center',
                    'branding.logo.position.top_right' => 'top-right',
                    'branding.logo.position.bottom_left' => 'bottom-left',
                    'branding.logo.position.bottom_center' => 'bottom-center',
                    'branding.logo.position.bottom_right' => 'bottom-right',
                ],
            ])
            ->add('secondLogoSize', ChoiceType::class, [
                'required' => false,
                'label' => 'branding.second_logo.size',
                'attr' => [
                    'class' => 'form-select',
                ],
                'choices' => [
                    'branding.logo.size.small' => 'small',
                    'branding.logo.size.medium' => 'medium',
                    'branding.logo.size.large' => 'large',
                ],
            ])
            ->add('fontFamily', ChoiceType::class, [
                'required' => false,
                'label' => 'branding.font.google_fonts',
                'attr' => [
                    'class' => 'form-select',
                ],
                'choices' => array_combine(BrandingService::GOOGLE_FONTS, BrandingService::GOOGLE_FONTS),
                'placeholder' => 'branding.font.select_google_font',
            ])
            ->add('customFont', FileType::class, [
                'required' => false,
                'label' => 'branding.font.upload_ttf',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.ttf,font/ttf',
                ],
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'font/ttf',
                            'application/x-font-ttf',
                            'application/x-font-truetype',
                            'font/sfnt',
                        ],
                        mimeTypesMessage: 'branding.font.invalid_type',
                    ),
                ],
            ])
            ->add('cardTemplate', ChoiceType::class, [
                'required' => false,
                'label' => 'branding.template.choice',
                'attr' => [
                    'class' => 'form-select',
                ],
                'choices' => [
                    'branding.template.default' => 'default',
                    'branding.template.modern' => 'modern',
                    'branding.template.elegant' => 'elegant',
                    'branding.template.minimal' => 'minimal',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountBranding::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'branding',
        ]);
    }
}

