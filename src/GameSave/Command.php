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
        private readonly Provider $saves,
    ) {
        parent::__construct('reeder:gamesave');
    }

    protected function configure(): void
    {
        $this->addArgument('save');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('save');
        if (!$name) {
            $name = $io->choice('Select', Kernel::arr(
                $this->saves,
                fn(Entity $save) => yield $save->name,
                0
            ));
        }
        $save = $this->saves->get($name) ?? throw new InvalidArgumentException("Unknown save `$name`");
        $io->success($save->name);
        return self::SUCCESS;
    }
}
