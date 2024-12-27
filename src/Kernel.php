<?php

namespace Kaluzki\DerReeder;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
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

    private function renderSaves(Finder $files, string $slug = ''): iterable
    {
        if ($slug) {
            yield <<<HTML
            <h1 class="text-danger">$slug</h1>
            HTML;
            return;
        }

         foreach ($files as $file) {
            yield "<p>{$file->getFilename()}</p>";
        }
    }

    private function renderSave(SplFileInfo $file): iterable
    {
        yield "<h1>{$file->getFilename()}</h1>";
    }

    #[Route('/{name}', methods: ['GET'])]
    public function main(string $name = ''): Response
    {
        $out = self::out(...);
        $finder = new Finder()->files()->in('resources/GAMESAVE')->sortByName();
        $file = null;
        if ($name) {
            $file = iterator_to_array(
                $finder->filter(fn(SplFileInfo $file) => $file->getFilename() === $name),
                false
            )[0] ?? null;
        }
        $content = match($file) {
            null => $this->renderSaves($finder->name("*.SVE"), $name),
            default => $this->renderSave($file),
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
