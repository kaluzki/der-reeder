<?php

namespace Kaluzki\DerReeder\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Pass implements CompilerPassInterface
{
    private readonly \Closure $pass;

    public function __construct(
        CompilerPassInterface|callable $pass,
        private(set) int|PassPriority|null $priority = null
    ) {
        $this->priority ??= $pass->priority ?? null;
        if ($pass instanceof CompilerPassInterface) {
            $pass = $pass->process(...);
        }
        $this->pass = $pass(...);
    }

    public function process(ContainerBuilder $container): void
    {
        ($this->pass)(...func_get_args());
    }
}
