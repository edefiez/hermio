<?php

namespace App\Form;

use App\Entity\TeamMember;
use App\Repository\TeamMemberRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardAssignmentFormType extends AbstractType
{
    public function __construct(
        private TeamMemberRepository $teamMemberRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $account = $options['account'] ?? null;

        $builder->add('teamMembers', EntityType::class, [
            'class' => TeamMember::class,
            'choice_label' => 'email',
            'multiple' => true,
            'expanded' => false,
            'required' => false,
            'label' => 'card.assignments.team_members',
            'query_builder' => function (TeamMemberRepository $repository) use ($account) {
                if (!$account) {
                    return $repository->createQueryBuilder('tm')
                        ->where('1 = 0'); // Empty result
                }
                return $repository->createQueryBuilder('tm')
                    ->where('tm.account = :account')
                    ->andWhere('tm.invitationStatus = :status')
                    ->setParameter('account', $account)
                    ->setParameter('status', 'accepted')
                    ->orderBy('tm.email', 'ASC');
            },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'account' => null,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'card_assignment',
        ]);
    }
}

