<?php

namespace App\Controller;

use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PublicCardController extends AbstractController
{
    public function __construct(
        private CardRepository $cardRepository
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

        return $this->render('public/card.html.twig', [
            'card' => $card,
            'publicUrl' => $publicUrl,
        ]);
    }
}

