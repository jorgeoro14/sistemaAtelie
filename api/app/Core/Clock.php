<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;

/** Relógio único da aplicação, sempre no horário de Brasília. */
final class Clock
{
    private static function agoraObj(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(Config::obter('app.fuso', 'America/Sao_Paulo')));
    }

    public static function hoje(): string
    {
        return self::agoraObj()->format('Y-m-d');
    }

    public static function agora(): string
    {
        return self::agoraObj()->format('Y-m-d H:i:s');
    }

    public static function agoraMais(int $minutos): string
    {
        return self::agoraObj()->modify("+{$minutos} minutes")->format('Y-m-d H:i:s');
    }

    public static function timestamp(): int
    {
        return self::agoraObj()->getTimestamp();
    }
}
