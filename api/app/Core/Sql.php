<?php

declare(strict_types=1);

namespace App\Core;

/** Pequenos auxiliares de SQL (sempre usados junto com parâmetros preparados). */
final class Sql
{
    /** Monta o padrão do LIKE "contém", escapando % _ e \ digitados pelo usuário. */
    public static function contem(string $texto): string
    {
        return '%' . addcslashes($texto, '%_\\') . '%';
    }
}
