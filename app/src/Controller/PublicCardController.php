<?php

namespace App\Controller;

use App\Repository\CardRepository;
use App\Service\BrandingService;
use App\Service\TemplateResolverService;
use App\Service\VCardService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PublicCardController extends AbstractController
{
    public function __construct(
        private CardRepository $cardRepository,
        private BrandingService $brandingService,
        private TemplateResolverService $templateResolverService,
        private VCardService $vcardService,
        private LoggerInterface $logger
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

    /**
     * Download a card as a vCard (.vcf) file
     * 
     * @Route('/c/{slug}/download', name: 'public_card_download', methods: ['GET'])
     */
    #[Route('/c/{slug}/download', name: 'public_card_download', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function download(string $slug): Response
    {
        try {
            // Find the card by slug
            $card = $this->cardRepository->findOneBySlug($slug);

            if (!$card) {
                throw $this->createNotFoundException('Card not found');
            }

            // Generate vCard content
            $vcardContent = $this->vcardService->generate($card);
            
            // Generate filename
            $filename = $this->vcardService->generateFilename($card);

            // Create response with vCard content
            $response = new Response($vcardContent);
            
            // Set headers for vCard download
            $response->headers->set('Content-Type', 'text/vcard; charset=utf-8');
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
            $response->headers->set('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
            
            return $response;
        } catch (NotFoundHttpException $e) {
            // Re-throw 404 exceptions
            throw $e;
        } catch (\Exception $e) {
            // Log the error
            $this->logger->error('Failed to generate vCard for download', [
                'slug' => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return user-friendly error message
            throw $this->createNotFoundException('Unable to generate contact card. Please try again later.');
        }
    }
}

