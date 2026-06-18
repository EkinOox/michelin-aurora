<?php

namespace App\Command;

use App\Service\CyclingNewsProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Réchauffe le cache des actualités cyclisme. À planifier (cron) toutes les ~15 min
 * pour que les requêtes utilisateurs tombent toujours sur un cache chaud.
 */
#[AsCommand(name: 'app:news:refresh', description: 'Récupère les flux RSS cyclisme et met à jour le cache.')]
class RefreshNewsCommand extends Command
{
    public function __construct(private CyclingNewsProvider $provider)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = count($this->provider->refresh());
        $io->success(sprintf('Cache actualités mis à jour : %d articles.', $count));

        return Command::SUCCESS;
    }
}
