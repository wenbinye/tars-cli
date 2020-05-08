<?php

declare(strict_types=1);

namespace wenbinye\tars\cli;

class ConfigLevel
{
    public const APP = 1;
    public const SET = 2;
    public const AREA = 3;
    public const GROUP = 4;
    public const SERVER = 5;

    private static $NAMES;

    private static function getNames(): array
    {
        if (!self::$NAMES) {
            $reflectionClass = new \ReflectionClass(__CLASS__);
            self::$NAMES = $reflectionClass->getConstants();
        }

        return self::$NAMES;
    }

    public static function getName(int $level): ?string
    {
        $key = array_search($level, self::getNames(), true);

        return false === $key ? null : strtolower((string) $key);
    }

    public static function hasLevel(int $level): bool
    {
        return in_array($level, self::getNames(), true);
    }

    public static function fromName(string $name): ?int
    {
        return self::getNames()[strtoupper($name)] ?? null;
    }
}
