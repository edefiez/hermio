<?php

namespace App\Command;

use App\Entity\Card;
use App\Entity\CardScan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate-test-scans',
    description: 'Populate test scan data for development and testing',
)]
class PopulateTestScansCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Populating Test Scan Data');

        $cardRepository = $this->entityManager->getRepository(Card::class);
        
        // Get all cards
        $cards = $cardRepository->findAll();
        
        if (empty($cards)) {
            $io->error('No cards found. Please create some cards first.');
            return Command::FAILURE;
        }

        $io->text(sprintf('Found %d cards. Creating scan data...', count($cards)));

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ];

        $ips = [
            '192.168.1.0',
            '10.0.0.0',
            '172.16.0.0',
            '203.0.113.0',
            '198.51.100.0',
            '2001:0db8:85a3:0000::',
        ];

        $totalScans = 0;

        // Create scans for the last 30 days
        foreach ($cards as $card) {
            // Random number of scans per card (between 10 and 100)
            $scansForCard = rand(10, 100);
            
            $io->text(sprintf('Creating %d scans for card: %s', $scansForCard, $card->getSlug()));
            
            for ($i = 0; $i < $scansForCard; $i++) {
                $scan = new CardScan();
                $scan->setCard($card);
                
                // Random date in the last 30 days
                $daysAgo = rand(0, 29);
                $hoursAgo = rand(0, 23);
                $minutesAgo = rand(0, 59);
                $date = (new \DateTime())->modify("-{$daysAgo} days -{$hoursAgo} hours -{$minutesAgo} minutes");
                $scan->setScannedAt($date);
                
                // Random IP and user agent
                $scan->setIpAddress($ips[array_rand($ips)]);
                $scan->setUserAgent($userAgents[array_rand($userAgents)]);
                
                $this->entityManager->persist($scan);
                $totalScans++;
            }
            
            // Flush every 100 scans to avoid memory issues
            if ($totalScans % 100 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear(CardScan::class);
            }
        }

        // Final flush
        $this->entityManager->flush();

        $io->success(sprintf('Successfully created %d test scans for %d cards!', $totalScans, count($cards)));
        $io->text('You can now view the analytics in the dashboard.');

        return Command::SUCCESS;
    }
}
