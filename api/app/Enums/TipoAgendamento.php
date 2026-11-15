<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoAgendamento: string
{
    use EnumTrait;

    case Prova = 'prova';
    case Medidas = 'medidas';
    case Retirada = 'retirada';
    case Outro = 'outro';
}
