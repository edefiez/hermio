<?php

namespace App\Command;

use App\Repository\CardRepository;
use App\Service\SecureKeyGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:card:migrate-keys',
    description: 'Generate access keys for all cards that don\'t have one'
)]
class MigrateCardKeysCommand extends Command
{
    public function __construct(
        private CardRepository $cardRepository,
        private SecureKeyGenerator $keyGenerator,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be done without making changes')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force regeneration even for cards that already have keys')
            ->setHelp('This command generates access keys for all cards that don\'t have one. Use --dry-run to preview changes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');

        $io->title('Migrating card access keys');

        if ($dryRun) {
            $io->note('DRY RUN MODE - No changes will be made');
        }

        if ($force) {
            $io->warning('FORCE MODE - All cards will have their keys regenerated!');
            if (!$io->confirm('Are you sure you want to continue?', false)) {
                $io->info('Migration cancelled.');
                return Command::SUCCESS;
            }
        }

        try {
            // Find all cards
            $allCards = $this->cardRepository->findAll();
            $totalCards = count($allCards);
            
            if ($totalCards === 0) {
                $io->info('No cards found in the database.');
                return Command::SUCCESS;
            }

            $io->writeln(sprintf('Found %d card(s) in total.', $totalCards));

            $cardsToUpdate = [];
            foreach ($allCards as $card) {
                if ($force || !$card->getPublicAccessKey()) {
                    $cardsToUpdate[] = $card;
                }
            }

            $updateCount = count($cardsToUpdate);

            if ($updateCount === 0) {
                $io->success('All cards already have access keys. Nothing to do!');
                return Command::SUCCESS;
            }

            $io->writeln(sprintf('Cards to update: %d', $updateCount));
            $io->newLine();

            if (!$dryRun && !$io->confirm(sprintf('Generate keys for %d card(s)?', $updateCount), true)) {
                $io->info('Migration cancelled.');
                return Command::SUCCESS;
            }

            $io->progressStart($updateCount);

            $successCount = 0;
            $errorCount = 0;

            foreach ($cardsToUpdate as $card) {
                try {
                    if (!$dryRun) {
                        $newKey = $this->keyGenerator->generateRandomKey();
                        $card->setPublicAccessKey($newKey);
                        $successCount++;
                    } else {
                        $io->writeln(sprintf(
                            '  [DRY RUN] Would generate key for card #%d (%s)',
                            $card->getId(),
                            $card->getContent()['name'] ?? 'Unnamed'
                        ));
                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    if (!$dryRun) {
                        $io->error(sprintf(
                            'Error updating card #%d: %s',
                            $card->getId(),
                            $e->getMessage()
                        ));
                    }
                }
                
                $io->progressAdvance();
            }

            if (!$dryRun) {
                $this->entityManager->flush();
            }

            $io->progressFinish();
            $io->newLine(2);

            if ($errorCount === 0) {
                if ($dryRun) {
                    $io->success(sprintf('DRY RUN: Would successfully generate keys for %d card(s).', $successCount));
                } else {
                    $io->success(sprintf('Successfully generated keys for %d card(s)!', $successCount));
                    $io->note('Users will need to use the new URLs with access keys to view their cards.');
                }
            } else {
                $io->warning(sprintf(
                    'Completed with %d success(es) and %d error(s).',
                    $successCount,
                    $errorCount
                ));
                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Migration failed: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
