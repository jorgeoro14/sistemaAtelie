<?php

declare(strict_types=1);

namespace App\Enums;

/** RN04: recebido → em_andamento → pronto → entregue; cancelado enquanto não entregue. */
enum StatusOs: string
{
    use EnumTrait;

    case Recebido = 'recebido';
    case EmAndamento = 'em_andamento';
    case Pronto = 'pronto';
    case Entregue = 'entregue';
    case Cancelado = 'cancelado';

    /** Posição na linha do fluxo normal (cancelado fica fora). */
    public function ordem(): int
    {
        return match ($this) {
            self::Recebido => 1,
            self::EmAndamento => 2,
            self::Pronto => 3,
            self::Entregue => 4,
            self::Cancelado => 0,
        };
    }

    /** OS "aberta" = ainda não finalizada (RN07/RN08). */
    public function aberta(): bool
    {
        return $this !== self::Entregue && $this !== self::Cancelado;
    }
}
