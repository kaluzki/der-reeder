<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

namespace Kaluzki\DerReeder\GameSave;

use IteratorAggregate;
use Psr\Http\Message\StreamFactoryInterface;
use Stringable;

/**
 * @implements IteratorAggregate<string, Game>
 */
readonly class Provider implements IteratorAggregate
{
    /**
     * @param iterable<string|Stringable> $files
     */
    public function __construct(
        private iterable $files,
        private StreamFactoryInterface $factory
    ) {}

    public function getIterator(): \Traversable
    {
        foreach ($this->files as $file) {
            $path = (string)$file;
            $stream = $this->factory->createStreamFromFile($path);
            yield $path => new Game($stream);
        }
    }

    public function get(string $name): ?Game
    {
        /** @noinspection PhpLoopCanBeConvertedToArrayFindInspection */
        foreach ($this as $game) {
            if ($game->name === $name) {
                return $game;
            }
        }
        return null;
    }
}
