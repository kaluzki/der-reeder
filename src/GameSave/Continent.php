<?php declare(strict_types=1);

namespace Kaluzki\DerReeder\GameSave;

enum Continent: int
{
    case EUROPE = 0;
    case NORTH_AMERICA = 1;
    case MIDDLE_AMERICA = 2;
    case SOUTH_AMERICA = 3;
    case AFRICA = 4;
    case ASIA = 5;
    case AUSTRALIA = 6;

    public function de(): string
    {
        return match($this) {
            self::EUROPE => 'Europa',
            self::NORTH_AMERICA => 'Nordamerika',
            self::MIDDLE_AMERICA => 'Mittelamerika',
            self::SOUTH_AMERICA => 'Südamerika',
            self::AFRICA => 'Afrika',
            self::ASIA => 'Asien',
            self::AUSTRALIA => 'Australien',
        };
    }
}
