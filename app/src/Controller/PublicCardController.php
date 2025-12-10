<?php

namespace App\Controller;

use App\Repository\CardRepository;
use App\Service\BrandingService;
use App\Service\TemplateResolverService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PublicCardController extends AbstractController
{
    public function __construct(
        private CardRepository $cardRepository,
        private BrandingService $brandingService,
        private TemplateResolverService $templateResolverService
    ) {
    }

    #[Route('/c/{slug}', name: 'app_public_card', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug): Response
    {
        $card = $this->cardRepository->findOneBySlug($slug);

        if (!$card) {
            throw $this->createNotFoundException('Card not found');
        }

        $publicUrl = '/c/' . $slug;
        
        // Get account and branding
        $account = $card->getUser()->getAccount();
        $branding = $account ? $this->brandingService->getBrandingForAccount($account) : null;

        // Resolve template (custom or default)
        $templateName = $account ? $this->templateResolverService->resolveTemplate($account) : null;
        $templateName = $templateName ?? 'public/card.html.twig';

        return $this->render($templateName, [
            'card' => $card,
            'publicUrl' => $publicUrl,
            'account' => $account,
            'branding' => $branding,
        ]);
    }
}

