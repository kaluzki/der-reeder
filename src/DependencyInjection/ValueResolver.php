<?php

namespace Kaluzki\DerReeder\DependencyInjection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

readonly class ValueResolver implements ValueResolverInterface
{
    private \Closure $resolver;

    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver(...);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield from ($this->resolver)($argument, $request);
    }
}
