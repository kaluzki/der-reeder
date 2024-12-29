<?php declare(strict_types=1);

namespace Kaluzki\DerReeder\GameSave;

class City
{
    use JsonFromUnpackTrait;

    private const array FIELDS = [
        'name' => ['A20', [self::class, 'utf8']],
        'country' => ['A20', [self::class, 'utf8']],
        '...' => 'H*',
    ];

    public string $name {get => $this->json[__PROPERTY__];}

    public string $country {get => $this->json[__PROPERTY__];}
}
