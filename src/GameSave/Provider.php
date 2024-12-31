<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

namespace Kaluzki\DerReeder\GameSave;

use IteratorAggregate;
use Psr\Http\Message\StreamFactoryInterface;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @implements IteratorAggregate<string, Game>
 */
#[AsTargetedValueResolver]
readonly class Provider implements IteratorAggregate, ValueResolverInterface
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

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() === Game::class) {
            yield $this->get($request->attributes->get($argument->getName()));
        }
    }
}
