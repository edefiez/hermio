<?php

namespace App\Command;

use App\Repository\CardRepository;
use App\Service\CardService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:card:regenerate-key',
    description: 'Regenerate access key for a specific card'
)]
class RegenerateCardKeyCommand extends Command
{
    public function __construct(
        private CardRepository $cardRepository,
        private CardService $cardService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('card-id', InputArgument::REQUIRED, 'The ID of the card')
            ->setHelp('This command allows you to regenerate the access key for a specific card by its ID.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cardId = $input->getArgument('card-id');

        $io->title(sprintf('Regenerating access key for card #%s', $cardId));

        $card = $this->cardRepository->find($cardId);

        if (!$card) {
            $io->error(sprintf('Card with ID %s not found.', $cardId));
            return Command::FAILURE;
        }

        try {
            $oldKey = $card->getPublicAccessKey();
            $this->cardService->regenerateCardAccessKey($card);
            $newKey = $card->getPublicAccessKey();

            $io->success('Access key regenerated successfully!');
            $io->writeln([
                '',
                sprintf('Card: %s', $card->getContent()['name'] ?? 'Unnamed'),
                sprintf('Slug: %s', $card->getSlug()),
                sprintf('Old key: %s', $oldKey ?: '(none)'),
                sprintf('New key: %s', $newKey),
                sprintf('New URL: %s', $card->getPublicUrl()),
                '',
            ]);

            $io->note('The old URL is now invalid. Share the new URL with your contacts.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error regenerating access key: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
