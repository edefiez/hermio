<?php

namespace App\Command;

use App\Entity\Card;
use App\Service\SecureKeyGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cards:generate-access-keys',
    description: 'Generate public access keys for cards that don\'t have one',
)]
class GenerateMissingAccessKeysCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecureKeyGenerator $keyGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Show what would be done without making changes'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        // Find all cards without access key
        $cards = $this->entityManager
            ->getRepository(Card::class)
            ->createQueryBuilder('c')
            ->where('c.publicAccessKey IS NULL')
            ->orWhere('c.publicAccessKey = :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getResult();

        if (empty($cards)) {
            $io->success('All cards already have access keys!');
            return Command::SUCCESS;
        }

        $io->note(sprintf('Found %d card(s) without access key', count($cards)));

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be made');
            foreach ($cards as $card) {
                $io->text(sprintf(
                    '  - Card #%d (slug: %s) - would generate new key',
                    $card->getId(),
                    $card->getSlug()
                ));
            }
            return Command::SUCCESS;
        }

        $progressBar = $io->createProgressBar(count($cards));
        $progressBar->start();

        foreach ($cards as $card) {
            $key = $this->keyGenerator->generateRandomKey();
            $card->setPublicAccessKey($key);
            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf('Generated access keys for %d card(s)', count($cards)));

        return Command::SUCCESS;
    }
}

