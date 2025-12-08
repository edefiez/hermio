<?php

namespace App\Form;

use App\Entity\Account;
use App\Enum\PlanType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PlanChangeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentPlan = $options['current_plan'] ?? PlanType::FREE;
        $currentUsage = $options['current_usage'] ?? 0;

        $builder
            ->add('planType', ChoiceType::class, [
                'choices' => [
                    'Free' => PlanType::FREE->value,
                    'Pro' => PlanType::PRO->value,
                    'Enterprise' => PlanType::ENTERPRISE->value,
                ],
                'label' => 'account.plan_type',
                'required' => true,
                'data' => $currentPlan->value,
            ])
            ->add('confirmDowngrade', CheckboxType::class, [
                'label' => 'account.confirm_downgrade',
                'required' => false,
                'mapped' => false,
            ]);

        // Add validation for downgrades
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($currentPlan, $currentUsage) {
            $data = $event->getData();
            $form = $event->getForm();
            
            if (isset($data['planType'])) {
                $newPlan = PlanType::from($data['planType']);
                
                // Check if downgrading
                $isDowngrade = $this->isDowngrade($currentPlan, $newPlan);
                
                if ($isDowngrade) {
                    $newLimit = $newPlan->getQuotaLimit();
                    
                    if ($newLimit !== null && $currentUsage > $newLimit) {
                        // Require confirmation
                        if (empty($data['confirmDowngrade'])) {
                            $form->addError(new \Symfony\Component\Form\FormError(
                                'account.downgrade.confirmation_required'
                            ));
                        }
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Account::class,
            'current_plan' => PlanType::FREE,
            'current_usage' => 0,
        ]);
    }

    private function isDowngrade(PlanType $current, PlanType $new): bool
    {
        $order = [PlanType::FREE, PlanType::PRO, PlanType::ENTERPRISE];
        $currentIndex = array_search($current, $order);
        $newIndex = array_search($new, $order);
        
        return $newIndex < $currentIndex;
    }
}

