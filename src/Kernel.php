<?php

namespace Kaluzki\DerReeder;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel;

class Kernel extends HttpKernel\Kernel
{
    use MicroKernelTrait;

    public const string VERSION = '0.0.1';

    public static function fromContext(array $context): self
    {
        $env = $context['APP_ENV'] ?? 'prod';
        return new self($env, (bool)($context['APP_DEBUG'] ?? $env !== 'prod'));
    }

    public Application $cli {
        get {
            $cli = new Application($this);
            $cli->setName(__NAMESPACE__);
            $cli->setVersion(self::VERSION);
            return $cli;
        }
    }

    public static function gen($iter, ?callable $cb = null, ?int $start = null): iterable
    {
        $iter = is_iterable($iter) ? $iter : (array)$iter;
        if (!$cb && $start === null) {
            yield from $iter;
        } else if (!$cb) {
            foreach ($iter as $value) {
                yield $start++ => $value;
            }
        } else if ($start === null) {
            $start = 0;
            foreach ($iter as $key => $value) {
                foreach ($cb($value, $key, $start++) as $mapped) {
                    yield $key => $mapped;
                }
            }
        } else {
            foreach ($iter as $key => $value) {
                foreach ($cb($value, $key, $start) as $mapped) {
                    yield $start++ => $mapped;
                }
            }
        }
    }

    public static function arr($iter, ?callable $cb = null, ?int $start = null): array
    {
        return iterator_to_array(self::gen($iter, $cb, $start));
    }

    public static function args(object $obj, string $method, array $args, array $vars): array
    {
        $ref = new \ReflectionClass($obj);
        foreach ($ref->getMethod($method)->getParameters() as $i => $parameter) {
            isset($args[$i]) && $vars[$parameter->getName()] = $args[$i];
        }
        return $vars;
    }
}
