<?php

namespace Kaluzki\DerReeder\GameSave\Controller;

use Psr\Http\Message\StreamInterface;

readonly class HexView
{
    public function __construct(private StreamInterface $stream){}

    public function asHtml(Output $out, int $length = 16): string
    {
        $stream = $this->stream;
        $colHex = [];
        $colBytes = [];
        $colAddresses = [];
        $i = 0;
        $mx1 = $out->fn('<span class="mx-1">%s</span>');
        foreach ($this->hex($length) as $bytes => $hex) {
            $colAddresses[] = sprintf('<b>%s</b><br>', $length * $i++);
            $colHex[] = $out(str_split($hex, 2), $mx1) . '&shy;';
            $colBytes[] = htmlentities($this->cp437($bytes)) . '&shy;';
        }

        return <<<HTML
        <pre>{$out(json_encode($stream->getMetadata() + [
            'size' => $stream->getSize(),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))}</pre>
        <div class="d-flex align-items-stretch font-monospace lh-lg">
            <div class="text-end text-wrap mx-3"> 
                {$out($colAddresses)}
            </div>
            <div class="text-wrap border-end border-start border-1" style="width: 30rem; hyphenate-character: '';">
                {$out($colHex)}
            </div>
            <div class="text-wrap px-5" style="width: 30rem; hyphenate-character: ''; letter-spacing: 0.5rem;">
                {$out($colBytes)} 
            </div>
        </div>
        HTML;
    }

    private function hex(int $length): iterable
    {
        foreach (str_split((string)$this->stream, $length) as $string) {
            yield $string => strtoupper(...unpack('H*', $string));
        }
    }

    private function cp437($input, bool $original = false): string
    {
        $utf8 = iconv('CP437', 'UTF-8', (string)$input);
        if ($original) {
            return $utf8;
        }
        static $map;
        $map ??= array_flip(array_map(chr(...), [
            '␀' => 0x00,
            '☺' => 0x01,
            '☻' => 0x02,
            '♥' => 0x03,
            '♦' => 0x04,
            '♣' => 0x05,
            '♠' => 0x06,
            '•' => 0x07,
            '◘' => 0x08,
            '○' => 0x09,
            '◙' => 0x0A,
            '♂' => 0x0B,
            '♀' => 0x0C,
            '♪' => 0x0D,
            '♫' => 0x0E,
            '☼' => 0x0F,
            '►' => 0x10,
            '◄' => 0x11,
            '↕' => 0x12,
            '‼' => 0x13,
            '¶' => 0x14,
            '§' => 0x15,
            '▬' => 0x16,
            '↨' => 0x17,
            '↑' => 0x18,
            '↓' => 0x19,
            '→' => 0x1A,
            '←' => 0x1B,
            '∟' => 0x1C,
            '↔' => 0x1D,
            '▲' => 0x1E,
            '▼' => 0x1F,
            '⌂' => 0x7f,
        ]));
        return strtr($utf8, $map);
    }
}
