<?php

namespace App\Service;

use App\Entity\Card;
use App\Entity\User;
use App\Exception\QuotaExceededException;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;

class CardService
{
    public function __construct(
        private CardRepository $cardRepository,
        private QuotaService $quotaService,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createCard(Card $card, User $user): Card
    {
        // Validate quota
        $this->quotaService->validateQuota($user);

        // Generate slug
        $name = $card->getContent()['name'] ?? 'card';
        $slug = $this->generateUniqueSlug($name);
        $card->setSlug($slug);
        $card->setUser($user);
        $card->setStatus('active');

        $this->entityManager->persist($card);
        $this->entityManager->flush();

        return $card;
    }

    public function updateCard(Card $card): void
    {
        $this->entityManager->flush();
    }

    public function deleteCard(Card $card): void
    {
        $card->delete();
        $this->entityManager->flush();
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = $this->slugify($name);

        if ($this->cardRepository->slugExists($slug)) {
            $slug = $slug . '-' . bin2hex(random_bytes(4));
        }

        return $slug;
    }

    private function slugify(string $text): string
    {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 100);

        // If slug is empty or too short, generate random one
        if (empty($slug) || strlen($slug) < 3) {
            $slug = 'card-' . bin2hex(random_bytes(4));
        }

        return $slug;
    }

    /**
     * @return Card[]
     */
    public function getUserCards(User $user): array
    {
        return $this->cardRepository->findByUser($user);
    }
}

