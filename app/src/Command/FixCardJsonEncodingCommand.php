<?php

namespace App\Command;

use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:card:fix-json-encoding',
    description: 'Convert escaped Unicode characters in card JSON content to UTF-8'
)]
class FixCardJsonEncodingCommand extends Command
{
    public function __construct(
        private CardRepository $cardRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    /**
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Fixing JSON encoding for card content');

        // Get all cards
        $cards = $this->cardRepository->findAll();
        $io->writeln(sprintf('Found %d cards to process', count($cards)));

        $updated = 0;
        $skipped = 0;

        foreach ($cards as $card) {
            $content = $card->getContent();

            // Check if content contains escaped Unicode characters
            $jsonEncoded = json_encode($content, JSON_THROW_ON_ERROR);
            if (str_contains($jsonEncoded, '\\u')) {
                // Re-save the card to trigger the new JSON encoding
                $card->setContent($content);
                $this->entityManager->persist($card);
                $updated++;

                $io->writeln(sprintf(
                    '  ✓ Updated card #%d: %s',
                    $card->getId(),
                    $content['name'] ?? 'No name'
                ));
            } else {
                $skipped++;
            }
        }

        if ($updated > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Updated %d cards, skipped %d cards (already UTF-8)', $updated, $skipped));
        } else {
            $io->success('All cards are already using UTF-8 encoding');
        }

        return Command::SUCCESS;
    }
}

