<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

namespace Kaluzki\DerReeder\GameSave;

use IteratorAggregate;
use Psr\Http\Message\StreamFactoryInterface;
use Stringable;

/**
 * @implements IteratorAggregate<string, Entity>
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
            yield ($path = (string)$file) => new Entity($this->factory->createStream($path));
        }
    }

    public function get(string $needle): ?Entity
    {
        /** @noinspection PhpLoopCanBeConvertedToArrayFindInspection */
        foreach ($this as $entity) {
            if ($entity->name === $needle) {
                return $entity;
            }
        }
        return null;
    }
}
