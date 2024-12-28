<?php declare(strict_types=1);

namespace Kaluzki\DerReeder\GameSave;

use Kaluzki\DerReeder\Kernel;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Command extends SymfonyCommand
{
    public function __construct(
        private readonly Provider $games,
    ) {
        parent::__construct('reeder:gamesave');
    }

    protected function configure(): void
    {
        $this->addArgument('game');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('game');
        if (!$name) {
            $name = $io->choice('Select', Kernel::arr(
                $this->games,
                fn(Game $game) => yield $game->name,
                0
            ));
        }
        $save = $this->games->get($name) ?? throw new InvalidArgumentException("Unknown game `$name`");
        $io->title($save->name);
        foreach ($save->cities as $city) {
            $io->writeln("$city->name ($city->country)");
        }
        return self::SUCCESS;
    }
}
