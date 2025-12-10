<?php

namespace App\Controller;

use App\Entity\Card;
use App\Exception\QuotaExceededException;
use App\Form\CardFormType;
use App\Repository\CardRepository;
use App\Service\CardService;
use App\Service\QrCodeService;
use App\Service\QuotaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cards')]
#[IsGranted('ROLE_USER')]
class CardController extends AbstractController
{
    public function __construct(
        private CardService $cardService,
        private QuotaService $quotaService,
        private QrCodeService $qrCodeService,
        private CardRepository $cardRepository
    ) {
    }

    #[Route('', name: 'app_card_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $account = $user->getAccount();
        
        $quotaLimit = $account?->getPlanType()?->getQuotaLimit();
        $currentUsage = $this->quotaService->getCurrentUsage($user);
        $canCreateMore = $quotaLimit === null || $currentUsage < $quotaLimit;

        $cards = $this->cardRepository->findByUser($user);

        return $this->render('card/index.html.twig', [
            'cards' => $cards,
            'quotaLimit' => $quotaLimit,
            'currentUsage' => $currentUsage,
            'canCreateMore' => $canCreateMore,
        ]);
    }

    #[Route('/create', name: 'app_card_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $user = $this->getUser();
        $account = $user->getAccount();
        
        $quotaLimit = $account?->getPlanType()?->getQuotaLimit();
        $currentUsage = $this->quotaService->getCurrentUsage($user);
        $canCreateMore = $quotaLimit === null || $currentUsage < $quotaLimit;

        if (!$canCreateMore) {
            $this->addFlash('error', 'card.quota.exceeded');
            return $this->redirectToRoute('app_card_index');
        }

        $card = new Card();
        $form = $this->createForm(CardFormType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Transform form data to card content
                $content = [
                    'name' => $form->get('name')->getData() ?? '',
                    'email' => $form->get('email')->getData() ?? '',
                    'phone' => $form->get('phone')->getData() ?? '',
                    'company' => $form->get('company')->getData() ?? '',
                    'title' => $form->get('title')->getData() ?? '',
                    'bio' => $form->get('bio')->getData() ?? '',
                    'website' => $form->get('website')->getData() ?? '',
                    'social' => [
                        'linkedin' => $form->get('linkedin')->getData() ?? '',
                        'twitter' => $form->get('twitter')->getData() ?? '',
                    ],
                ];
                $card->setContent($content);

                $card = $this->cardService->createCard($card, $user);

                $this->addFlash('success', 'card.created.success', [
                    'slug' => $card->getSlug(),
                ]);

                return $this->redirectToRoute('app_card_index');
            } catch (QuotaExceededException $e) {
                $this->addFlash('error', 'card.quota.exceeded');
            }
        }

        return $this->render('card/create.html.twig', [
            'form' => $form,
            'quotaLimit' => $quotaLimit,
            'currentUsage' => $currentUsage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_card_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $user = $this->getUser();
        $card = $this->cardRepository->find($id);

        if (!$card || $card->getUser() !== $user) {
            throw $this->createNotFoundException('Card not found');
        }

        // Pre-populate form with card content
        $content = $card->getContent();
        $form = $this->createForm(CardFormType::class, $card);
        $form->get('name')->setData($content['name'] ?? '');
        $form->get('email')->setData($content['email'] ?? '');
        $form->get('phone')->setData($content['phone'] ?? '');
        $form->get('company')->setData($content['company'] ?? '');
        $form->get('title')->setData($content['title'] ?? '');
        $form->get('bio')->setData($content['bio'] ?? '');
        $form->get('website')->setData($content['website'] ?? '');
        $form->get('linkedin')->setData($content['social']['linkedin'] ?? '');
        $form->get('twitter')->setData($content['social']['twitter'] ?? '');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Transform form data to card content
            $content = [
                'name' => $form->get('name')->getData() ?? '',
                'email' => $form->get('email')->getData() ?? '',
                'phone' => $form->get('phone')->getData() ?? '',
                'company' => $form->get('company')->getData() ?? '',
                'title' => $form->get('title')->getData() ?? '',
                'bio' => $form->get('bio')->getData() ?? '',
                'website' => $form->get('website')->getData() ?? '',
                'social' => [
                    'linkedin' => $form->get('linkedin')->getData() ?? '',
                    'twitter' => $form->get('twitter')->getData() ?? '',
                ],
            ];
            $card->setContent($content);

            $this->cardService->updateCard($card);

            $this->addFlash('success', 'card.updated.success');

            return $this->redirectToRoute('app_card_index');
        }

        return $this->render('card/edit.html.twig', [
            'card' => $card,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_card_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $user = $this->getUser();
        $card = $this->cardRepository->find($id);

        if (!$card || $card->getUser() !== $user) {
            throw $this->createNotFoundException('Card not found');
        }

        if ($this->isCsrfTokenValid('delete' . $card->getId(), $request->request->get('_token'))) {
            $this->cardService->deleteCard($card);
            $this->addFlash('success', 'card.deleted.success');
        }

        return $this->redirectToRoute('app_card_index');
    }

    #[Route('/{id}/qr-code', name: 'app_card_qr_code', methods: ['GET'])]
    public function qrCode(int $id, Request $request): Response
    {
        $user = $this->getUser();
        $card = $this->cardRepository->find($id);

        if (!$card || $card->getUser() !== $user) {
            throw $this->createNotFoundException('Card not found');
        }

        $publicUrl = $request->getSchemeAndHttpHost() . $card->getPublicUrl();
        $qrCodeData = $this->qrCodeService->generateQrCodeBase64($publicUrl);

        return $this->render('card/qr_code.html.twig', [
            'card' => $card,
            'qrCodeData' => $qrCodeData,
            'publicUrl' => $publicUrl,
        ]);
    }
}

