<?php

namespace App\Controller;

use App\Entity\Card;
use App\Service\QrCodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CardQrCodeController extends AbstractController
{
    public function __construct(
        private QrCodeService $qrCodeService
    ) {
    }

    #[Route('/cards/{id}/qr-code', name: 'card_qr_code_download', methods: ['GET'])]
    public function download(
        Card $card,
        Request $request
    ): Response {
        // Check if user has access to view the card
        $this->denyAccessUnlessGranted('VIEW', $card);

        // Get and validate format parameter
        $format = strtolower($request->query->get('format', 'png'));

        if (!in_array($format, ['png', 'svg', 'pdf', 'eps'], true)) {
            throw $this->createNotFoundException('Unsupported format');
        }

        // Check Enterprise plan requirement for EPS format
        if ($format === 'eps' && !$this->getUser()->isEnterprise()) {
            throw $this->createAccessDeniedException('Enterprise plan required for EPS format');
        }

        // Generate full URL for QR code
        $fullUrl = $request->getSchemeAndHttpHost() . $card->getPublicUrl();

        // Generate QR code file
        $file = $this->qrCodeService->generateFromUrl($fullUrl, $card->getId(), $format);

        // Determine MIME type
        $mimeType = match ($format) {
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'eps' => 'application/postscript',
        };

        // Return file for download
        return $this->file(
            $file->getPathname(),
            sprintf('card-%d-qr.%s', $card->getId(), $format),
            $mimeType
        );
    }
}
