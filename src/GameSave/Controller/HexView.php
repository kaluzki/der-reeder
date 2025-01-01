<?php

namespace Kaluzki\DerReeder\GameSave\Controller;

use Psr\Http\Message\StreamInterface;

readonly class HexView
{
    public function __construct(private StreamInterface $stream){}

    public function asHtml(Output $out): string
    {
        $stream = $this->stream;
        return <<<HTML
        <pre>{$out(json_encode($stream->getMetadata() + [
            'size' => $stream->getSize(),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))}</pre>
        <pre>
        <div class="text-wrap w-25" style="hyphenate-character: '';">
        {$out($this->hex(), function (string $bytes, string $hex) use ($out) {
            yield $out(str_split($hex, 2), $out->fn('<span class="mx-1">%s</span>'), '&shy;');
        })}
        </div>
        HTML;
    }

    private function hex(): iterable
    {
        $unpacked = strtoupper(...unpack('H*', (string)$this->stream));
        foreach (str_split($unpacked, 16 * 2) as $hex) {
            yield $hex => pack('H*', $hex);
        }
    }
}
