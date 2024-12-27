<?php

namespace Kaluzki\DerReeder;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel;
use Symfony\Component\Routing\Attribute\Route;

class Kernel extends HttpKernel\Kernel
{
    use MicroKernelTrait;

    public static function fromContext(array $context): self
    {
        $env = $context['APP_ENV'] ?? 'prod';
        return new self($env, (bool)($context['APP_DEBUG'] ?? $env !== 'prod'));
    }

    #[Route('/')]
    public function list(): Response
    {
        return new Response(__METHOD__);
    }
}
