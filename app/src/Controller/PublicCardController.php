<?php

namespace App\Controller;

use App\Repository\CardRepository;
use App\Service\BrandingService;
use App\Service\CardService;
use App\Service\TemplateResolverService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PublicCardController extends AbstractController
{
    public function __construct(
        private CardRepository $cardRepository,
        private BrandingService $brandingService,
        private TemplateResolverService $templateResolverService,
        private CardService $cardService
    ) {
    }

    #[Route('/c/{slug}', name: 'app_public_card', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug, Request $request): Response
    {
        $card = $this->cardRepository->findOneBySlug($slug);

        if (!$card) {
            throw $this->createNotFoundException('Card not found');
        }

        // Extract access key from query parameter
        $providedKey = $request->query->get('k');

        // Validate access key
        if (!$this->cardService->validateAccessKey($card, $providedKey)) {
            return $this->render('error/403_invalid_key.html.twig', [
                'slug' => $slug,
            ], new Response('', Response::HTTP_FORBIDDEN));
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

