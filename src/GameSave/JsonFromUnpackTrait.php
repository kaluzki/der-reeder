<?php declare(strict_types=1);

namespace Kaluzki\DerReeder\GameSave;

trait JsonFromUnpackTrait
{
    public function __construct(private readonly string $data) {}

    private static function unpack(string $data): array
    {
        static $format = [];
        static $middleware = [];
        foreach ($format ? [] : static::FIELDS as $name => $value) {
            $value = (array)$value;
            $format[] = array_shift($value) . $name;
            $value && $middleware[$name] = $value;
        }
        $unpacked = unpack(implode('/', $format), $data);
        foreach ($middleware as $name => $chain) {
            foreach (isset($unpacked[$name]) ? $chain : [] as $cb) {
                $unpacked[$name] = $cb($unpacked[$name]);
            }
        }
        return $unpacked;
    }

    private static function utf8(string $string): string
    {
        return iconv('CP437', 'UTF-8', $string);
    }

    public array $json {
        get => $this->json ??= $this::unpack($this->data);
    }
}
