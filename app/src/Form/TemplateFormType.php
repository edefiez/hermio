<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length as LengthConstraint;

class TemplateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customTemplate', TextareaType::class, [
                'required' => false,
                'label' => 'branding.template.title',
                'attr' => [
                    'rows' => 20,
                    'placeholder' => "{% extends 'public/card.html.twig' %}\n\n{% block body %}\n    <!-- Your custom template content here -->\n{% endblock %}",
                    'class' => 'template-editor',
                ],
                'constraints' => [
                    new LengthConstraint(
                        max: 50000, // Max 50KB template content
                        maxMessage: 'branding.template.too_large'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'template',
        ]);
    }
}

