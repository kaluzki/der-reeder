<?php

namespace Kaluzki\DerReeder;

use Kaluzki\DerReeder\GameSave\Game;
use Kaluzki\DerReeder\GameSave\Provider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel;
use Symfony\Component\Routing\Attribute\Route;

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
        if (!$cb) {
            if ($start !== null) {
                yield from $iter;
                return;
            }
            foreach ($iter as $value) {
                yield $start++ => $value;
            }
            return;
        }

        $i = $start ?? 0;
        foreach ($iter as $key => $value) {
            foreach ($cb($value, $key, $i) as $mapped) {
                yield ($start === null ? $key : $i) => $mapped;
                $i++;
            }
        }
    }

    public static function arr($iter, ?callable $cb = null, ?int $start = null): array
    {
        return iterator_to_array(self::gen($iter, $cb, $start));
    }

    public static function out($iter, ?callable $cb = null, string $separator = PHP_EOL): string
    {
        $i = 0;
        $stream = '';
        foreach (is_iterable($iter) ? $iter : (array)$iter as $key => $value) {
            foreach ($cb ? $cb($value, $key, $i) : [$value] as $line) {
                if ($i) {
                    $stream .= $separator . $line;
                } else {
                    $stream .= $line;
                }
            }
            $i++;
        }
        return $stream;
    }

    private function renderGames(Provider $games, string $slug = ''): iterable
    {
        if ($slug) {
            yield <<<HTML
            <h1 class="text-danger">$slug</h1>
            HTML;
            return;
        }

         foreach ($games as $name => $file) {
            yield "<p>$name: $file->name</p>";
        }
    }

    private function renderGame(Game $game): iterable
    {
        yield "<h1>$game->name</h1>";
    }

    #[Route('/{name}', methods: ['GET'])]
    public function main(Provider $games, string $name = ''): Response
    {
        $out = self::out(...);

        $game = $name ? $games->get($name) : null;
        $content = match($game) {
            null => $this->renderGames($games, $name),
            default => $this->renderGame($game),
        };

        return new Response(<<<HTML
        <!doctype html>
        <html lang="en">
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Der Reeder</title>
            <link 
                href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
                rel="stylesheet" 
                integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" 
                crossorigin="anonymous"
            >
          </head>
          <body>
            {$out($content)}
            <script 
                src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
                integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" 
                crossorigin="anonymous"
            ></script>
          </body>
        </html>
        HTML);
    }
}
