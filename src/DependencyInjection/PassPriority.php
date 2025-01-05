<?php declare(strict_types=1);

namespace Kaluzki\DerReeder\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;

enum PassPriority: int
{
    private const int UP = 2;
    private const int DOWN = -2;
    private const int HIGH = 32;
    private const int LOW = -32;
    private const int TOP = 100;
    private const int BOTTOM = -100;
    private const int MAX = 255;
    private const int MIN = -255;

    case OPTIMIZE_BEFORE_MAX = self::MAX + self::OPTIMIZE_BEFORE->value;
    case OPTIMIZE_BEFORE_TOP = self::TOP + self::OPTIMIZE_BEFORE->value;
    case OPTIMIZE_BEFORE_HIGH = self::HIGH + self::OPTIMIZE_BEFORE->value;
    case OPTIMIZE_BEFORE_UP = self::UP + self::OPTIMIZE_BEFORE->value;
    case OPTIMIZE_BEFORE = 0b000_0000_0000;
    case OPTIMIZE_BEFORE_DOWN = self::DOWN - self::OPTIMIZE_BEFORE->value;
    case OPTIMIZE_BEFORE_LOW = self::LOW - self::OPTIMIZE_BEFORE->value;
    case OPTIMIZE_BEFORE_BOTTOM = self::BOTTOM - self::OPTIMIZE_BEFORE->value;
    case OPTIMIZE_BEFORE_MIN = self::MIN - self::OPTIMIZE_BEFORE->value;

    case OPTIMIZE_MAX = self::MAX + self::OPTIMIZE->value;
    case OPTIMIZE_TOP = self::TOP + self::OPTIMIZE->value;
    case OPTIMIZE_HIGH = self::HIGH + self::OPTIMIZE->value;
    case OPTIMIZE_UP = self::UP + self::OPTIMIZE->value;
    case OPTIMIZE = 0b001_0000_0000;
    case OPTIMIZE_DOWN = self::DOWN - self::OPTIMIZE->value;
    case OPTIMIZE_LOW = self::LOW - self::OPTIMIZE->value;
    case OPTIMIZE_BOTTOM = self::BOTTOM - self::OPTIMIZE->value;
    case OPTIMIZE_MIN = self::MIN - self::OPTIMIZE->value;

    case REMOVE_BEFORE_MAX = self::MAX + self::REMOVE_BEFORE->value;
    case REMOVE_BEFORE_TOP = self::TOP + self::REMOVE_BEFORE->value;
    case REMOVE_BEFORE_HIGH = self::HIGH + self::REMOVE_BEFORE->value;
    case REMOVE_BEFORE_UP = self::UP + self::REMOVE_BEFORE->value;
    case REMOVE_BEFORE = 0b010_0000_0000;
    case REMOVE_BEFORE_DOWN = self::DOWN - self::REMOVE_BEFORE->value;
    case REMOVE_BEFORE_LOW = self::LOW - self::REMOVE_BEFORE->value;
    case REMOVE_BEFORE_BOTTOM = self::BOTTOM - self::REMOVE_BEFORE->value;
    case REMOVE_BEFORE_MIN = self::MIN - self::REMOVE_BEFORE->value;

    case REMOVE_MAX = self::MAX + self::REMOVE->value;
    case REMOVE_TOP = self::TOP + self::REMOVE->value;
    case REMOVE_HIGH = self::HIGH + self::REMOVE->value;
    case REMOVE_UP = self::UP + self::REMOVE->value;
    case REMOVE = 0b011_0000_0000;
    case REMOVE_DOWN = self::DOWN - self::REMOVE->value;
    case REMOVE_LOW = self::LOW - self::REMOVE->value;
    case REMOVE_BOTTOM = self::BOTTOM - self::REMOVE->value;
    case REMOVE_MIN = self::MIN - self::REMOVE->value;

    case REMOVE_AFTER_MAX = self::MAX + self::REMOVE_AFTER->value;
    case REMOVE_AFTER_TOP = self::TOP + self::REMOVE_AFTER->value;
    case REMOVE_AFTER_HIGH = self::HIGH + self::REMOVE_AFTER->value;
    case REMOVE_AFTER_UP = self::UP + self::REMOVE_AFTER->value;
    case REMOVE_AFTER = 0b100_0000_0000;
    case REMOVE_AFTER_DOWN = self::DOWN - self::REMOVE_AFTER->value;
    case REMOVE_AFTER_LOW = self::LOW - self::REMOVE_AFTER->value;
    case REMOVE_AFTER_BOTTOM = self::BOTTOM - self::REMOVE_AFTER->value;
    case REMOVE_AFTER_MIN = self::MIN - self::REMOVE_AFTER->value;

    public static function args(null|int|self $prio = null): array
    {
        if ($prio === null) {
            return [];
        }
        is_int($prio) || $prio = $prio->value;
        $abs = abs($prio);
        $type = match(self::tryFrom($abs & 0b111_0000_0000)) {
            self::OPTIMIZE_BEFORE => PassConfig::TYPE_BEFORE_OPTIMIZATION,
            self::OPTIMIZE => PassConfig::TYPE_OPTIMIZE,
            self::REMOVE_BEFORE => PassConfig::TYPE_BEFORE_REMOVING,
            self::REMOVE => PassConfig::TYPE_REMOVE,
            self::REMOVE_AFTER => PassConfig::TYPE_AFTER_REMOVING,
            default => throw new \UnexpectedValueException("type = $abs"),
        };
        return [
            'type' => $type,
            'priority' => ($abs & 0b1111_1111) * ($prio >= 0 ? 1 : -1),
        ];
    }
}
