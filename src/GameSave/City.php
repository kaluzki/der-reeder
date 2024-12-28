<?php declare(strict_types=1);

namespace Kaluzki\DerReeder\GameSave;

class City
{
    public function __construct(private readonly string $data) {}

    public string $name {
        get => Game::subStr($this->data, 0, 20);
    }

    public string $country {
        get => Game::subStr($this->data, 20, 20);
    }
}
