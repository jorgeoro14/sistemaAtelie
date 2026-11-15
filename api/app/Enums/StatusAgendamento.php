<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusAgendamento: string
{
    use EnumTrait;

    case Marcado = 'marcado';
    case Realizado = 'realizado';
    case Cancelado = 'cancelado';
}
