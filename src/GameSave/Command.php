<?php declare(strict_types=1);

namespace Kaluzki\DerReeder\GameSave;

use Kaluzki\DerReeder\DependencyInjection\Attribute\AsCommand;
use Kaluzki\DerReeder\Kernel;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('reeder:gamesave')]
readonly class Command
{
    public function __construct(private Provider $games) {}

    public function __invoke(SymfonyStyle $io, ?string $game = null): iterable
    {
        if (!$game) {
            $game = $io->choice('Select', Kernel::arr(
                $this->games,
                fn(Game $game) => yield $game->name,
                0
            ));
        }
        $save = $this->games->get($game) ?? throw new InvalidArgumentException("Unknown game `$game`");
        yield $save->name;
        foreach ($save->cities as $city) {
            yield "$city->name ($city->country) [{$city->continent->de()}]";
            yield json_encode($city->json, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }
    }
}
