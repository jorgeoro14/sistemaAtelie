<?php

declare(strict_types=1);

namespace App\Enums;

/** Decisão fechada nº 2: três níveis de prioridade. */
enum Prioridade: string
{
    use EnumTrait;

    case Normal = 'normal';
    case Alta = 'alta';
    case Urgente = 'urgente';
}
