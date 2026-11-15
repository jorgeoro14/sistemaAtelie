<?php

declare(strict_types=1);

namespace App\Enums;

/** Perfil de acesso. Por enquanto só existe "admin" (campo já pronto para o futuro). */
enum Perfil: string
{
    use EnumTrait;

    case Admin = 'admin';
}
