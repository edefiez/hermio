<?php

namespace App\Command;

use App\Service\TeamInvitationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:team:cleanup-expired-invitations',
    description: 'Mark expired team invitations as expired'
)]
class CleanupExpiredInvitationsCommand extends Command
{
    public function __construct(
        private TeamInvitationService $invitationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Cleaning up expired team invitations');

        try {
            $count = $this->invitationService->markExpiredInvitations();

            if ($count > 0) {
                $io->success(sprintf('Marked %d expired invitation(s) as expired.', $count));
            } else {
                $io->info('No expired invitations found.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error cleaning up expired invitations: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}

