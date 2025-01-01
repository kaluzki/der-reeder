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
        private string $template = '%s',
        private string $htmlAttributes = 'lang="de"',
        private string $bodyAttributes = '',
        private string $separator = '',
    ){}

    public function with(?string $separator = null, ?string $title = null): self
    {
        return new self(...Kernel::args($this, __FUNCTION__, func_get_args(), get_object_vars($this)));
    }

    /** @noinspection HtmlUnknownTarget */
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
        /** @noinspection HtmlRequiredLangAttribute */
        return new Response(sprintf(<<<HTML
        <!doctype html>
        <html {$this($htmlAttributes ?? $this->htmlAttributes)}>
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$this(sprintf($title ?? '%s', $this->title))}</title>
            {$this($headAssets ?? sprintf(self::ASSETS[$this->assets]['head'] ?? '', $this->assetsCdn))}
          </head>
          <body {$this($bodyAttributes ?? $this->bodyAttributes)}>
            %s
            {$this($bodyAssets ?? sprintf(self::ASSETS[$this->assets]['body'] ?? '', $this->assetsCdn))}
          </body>
        </html>
        HTML,
        sprintf($this->template, $this($content))
        ));
    }

    public function __invoke(
        string|Stringable|callable|iterable $input,
        ?callable $map = null,
        ?string $separator = null
    ): string {
        return self::implode($input, $separator ?? $this->separator, $map);
    }

    private static function implode(
        string|Stringable|callable|iterable $input,
        string $separator = '',
        ?callable $map = null,
    ): string {
        if (is_string($input) || $input instanceof Stringable) {
            return (string)$input;
        }
        is_callable($input) && $input = $input();
        is_iterable($input) && $input = implode(
            $separator,
            iterator_to_array(Kernel::gen($input, $map), false)
        );
        return (string)$input;

    }

    public function fn(string|Stringable|callable|iterable $template, ?callable $fn = null): callable
    {
        $template = self::implode($template);
        $fn ??= fn($value) => yield self::implode($value);
        return function (...$args) use ($fn, $template) {
            foreach ($fn(...$args) as $value) {
                yield sprintf($template, $value);
            }
        };
    }
}
