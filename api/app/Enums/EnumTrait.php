<?php

declare(strict_types=1);

namespace App\Enums;

/** Utilidades comuns aos enums do domínio. */
trait EnumTrait
{
    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(static fn(self $c) => $c->value, self::cases());
    }
}
