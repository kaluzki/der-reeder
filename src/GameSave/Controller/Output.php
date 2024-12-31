<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Kaluzki\DerReeder\GameSave\Controller;

use Kaluzki\DerReeder\Kernel;
use Stringable;
use Symfony\Component\HttpFoundation\Response;

readonly class Output
{
    public const string BS_CDN = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist';
    public const string BS = 'bs';

    public function __construct(
        private string $assets = self::BS,
        private string $assetsCdn = self::BS_CDN,
        private string $title = '',
        private string $htmlAttributes = 'lang="de"',
        private string $bodyAttributes = '',
    ){}

    private const array ASSETS = [
        self::BS => [
            'head' => <<<HTML
            <link 
                href="%s/css/bootstrap.min.css" 
                rel="stylesheet" 
                integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" 
                crossorigin="anonymous"
            >
            HTML,
            'body' => <<<HTML
            <script 
                src="%s/js/bootstrap.bundle.min.js" 
                integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" 
                crossorigin="anonymous"
            ></script>
            HTML
        ]
    ];

    public function html(
        string|Stringable|callable|iterable $content,
        string|Stringable|callable|iterable|null $title = null,
        string|Stringable|callable|iterable|null $htmlAttributes = null,
        string|Stringable|callable|iterable|null $bodyAttributes = null,
        string|Stringable|callable|iterable|null $headAssets = null,
        string|Stringable|callable|iterable|null $bodyAssets = null,
    ): Response {
        return new Response(sprintf(<<<HTML
            <!doctype html>
            <html %s>
              <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>%s</title>
                %s
              </head>
              <body %s>
                %s
                %s
              </body>
            </html>
            HTML,
            $this($htmlAttributes ?? $this->htmlAttributes),
            $this($title ?? $this->title),
            $this($headAssets ?? sprintf(self::ASSETS[$this->assets]['head'] ?? '', $this->assetsCdn)),
            $this($bodyAttributes ?? $this->bodyAttributes),
            $this($content),
            $this($bodyAssets ?? sprintf(self::ASSETS[$this->assets]['body'] ?? '', $this->assetsCdn)),
        ));
    }

    public function __invoke($from, ?callable $map = null, string $separator = PHP_EOL): string
    {
        is_callable($from) && $from = $from();
        is_iterable($from) && $from = implode($separator, Kernel::arr($from, $map));
        return (string)$from;
    }
}
