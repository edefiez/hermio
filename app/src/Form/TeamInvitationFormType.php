<?php

namespace App\Form;

use App\Enum\TeamRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamInvitationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'team.invite.email',
                'required' => true,
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'team.invite.role',
                'choices' => [
                    'team.role.admin' => TeamRole::ADMIN->value,
                    'team.role.member' => TeamRole::MEMBER->value,
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'team_invite',
        ]);
    }
}

