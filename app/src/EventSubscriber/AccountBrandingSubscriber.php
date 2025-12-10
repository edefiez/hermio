<?php

namespace App\EventSubscriber;

use App\Entity\Account;
use App\Entity\AccountBranding;
use App\Service\BrandingService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preRemove)]
class AccountBrandingSubscriber
{
    public function __construct(
        private ?BrandingService $brandingService = null
    ) {
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        
        // Clean up branding files when account is deleted
        if ($entity instanceof Account && $this->brandingService) {
            $this->brandingService->cleanupBrandingFiles($entity);
        }
        
        // Clean up logo file when AccountBranding is deleted
        if ($entity instanceof AccountBranding && $this->brandingService && $entity->getLogoFilename()) {
            $this->brandingService->cleanupBrandingFiles($entity->getAccount());
        }
    }
}

