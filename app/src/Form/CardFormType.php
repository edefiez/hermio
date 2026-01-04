<?php

namespace App\Form;

use App\Entity\Card;
use App\Validator\Constraints\SocialProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CardFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'card.name.label',
                'required' => true,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.name.placeholder'],
                'constraints' => [
                    new NotBlank(message: 'card.name.required'),
                    new Length(max: 255, maxMessage: 'card.name.max_length'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'card.email.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.email.placeholder'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'card.phone.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.phone.placeholder'],
                'constraints' => [
                    new Length(max: 50, maxMessage: 'card.phone.max_length'),
                ],
            ])
            ->add('company', TextType::class, [
                'label' => 'card.company.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.company.placeholder'],
                'constraints' => [
                    new Length(max: 255, maxMessage: 'card.company.max_length'),
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'card.title.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.title.placeholder'],
                'constraints' => [
                    new Length(max: 255, maxMessage: 'card.title.max_length'),
                ],
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'card.bio.label',
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
                'label' => 'card.website.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.website.placeholder'],
            ])
            ->add('linkedin', UrlType::class, [
                'label' => 'card.social.linkedin.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.linkedin.placeholder'],
            ])
            // New social network fields (Feature 008)
            ->add('instagram', UrlType::class, [
                'label' => 'card.social.instagram.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.social.instagram.placeholder'],
                'constraints' => [
                    new SocialProfile(platform: 'instagram'),
                ],
            ])
            ->add('tiktok', UrlType::class, [
                'label' => 'card.social.tiktok.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.social.tiktok.placeholder'],
                'constraints' => [
                    new SocialProfile(platform: 'tiktok'),
                ],
            ])
            ->add('facebook', UrlType::class, [
                'label' => 'card.social.facebook.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.social.facebook.placeholder'],
                'constraints' => [
                    new SocialProfile(platform: 'facebook'),
                ],
            ])
            ->add('x', UrlType::class, [
                'label' => 'card.social.x.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.social.x.placeholder'],
                'constraints' => [
                    new SocialProfile(platform: 'x'),
                ],
            ])
            ->add('bluebirds', UrlType::class, [
                'label' => 'card.social.bluebirds.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.social.bluebirds.placeholder'],
                'constraints' => [
                    new SocialProfile(platform: 'bluebirds'),
                ],
            ])
            ->add('snapchat', UrlType::class, [
                'label' => 'card.social.snapchat.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.social.snapchat.placeholder'],
                'constraints' => [
                    new SocialProfile(platform: 'snapchat'),
                ],
            ])
            ->add('planity', UrlType::class, [
                'label' => 'card.social.planity.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.social.planity.placeholder'],
                'constraints' => [
                    new SocialProfile(platform: 'planity'),
                ],
            ])
            ->add('other', UrlType::class, [
                'label' => 'card.social.other.label',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'card.social.other.placeholder'],
                'constraints' => [
                    new SocialProfile(platform: 'other'),
                ],
            ]);

        // Map form data to Card.content['social'] array on form submission
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $card = $event->getData();

            if (!$card instanceof Card) {
                return;
            }

            $content = $card->getContent();
            if (!isset($content['social'])) {
                $content['social'] = [];
            }

            // Map new social network fields to content['social']
            $socialFields = ['instagram', 'tiktok', 'facebook', 'x', 'bluebirds', 'snapchat', 'planity', 'other'];
            foreach ($socialFields as $field) {
                $value = $form->get($field)->getData();
                if (!empty($value)) {
                    $content['social'][$field] = $value;
                } else {
                    // Remove empty values from the array
                    unset($content['social'][$field]);
                }
            }

            $card->setContent($content);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
        ]);
    }
}

