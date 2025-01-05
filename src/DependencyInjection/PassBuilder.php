<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Kaluzki\DerReeder\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

readonly class PassBuilder
{
    public function __construct(private ContainerBuilder $container) {}

    public function add(CompilerPassInterface|callable ...$passes): self
    {
        foreach ($passes as $pass) {
            if (!$pass instanceof CompilerPassInterface) {
                $pass = new Pass($pass);
            }
            $this->container->addCompilerPass($pass, ...PassPriority::args($pass->priority ?? null));
        }
        return $this;
    }
}
