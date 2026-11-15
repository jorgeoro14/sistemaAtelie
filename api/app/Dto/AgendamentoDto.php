<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Validator;
use App\Enums\StatusAgendamento;
use App\Enums\TipoAgendamento;

/** RN16: compromisso exige cliente, data e hora. */
final class AgendamentoDto
{
    private function __construct(
        public readonly int $clienteId,
        public readonly ?int $osId,
        public readonly string $dataHora,
        public readonly TipoAgendamento $tipo,
        public readonly ?string $observacao,
        public readonly StatusAgendamento $status,
    ) {
    }

    /** @param array<string, mixed> $d */
    public static function deArray(array $d): self
    {
        $v = new Validator($d);
        $cliente = $v->inteiro('cliente_id', true, 1);
        $os = $v->inteiro('os_id', false, 1);
        $dataHora = $v->dataHora('data_hora', true);
        $tipo = $v->opcao('tipo', TipoAgendamento::class, false, TipoAgendamento::Prova);
        $obs = $v->texto('observacao', false, 0, 500);
        $status = $v->opcao('status', StatusAgendamento::class, false, StatusAgendamento::Marcado);
        $v->lancarSeInvalido();
        return new self((int) $cliente, $os, (string) $dataHora, $tipo, $obs, $status);
    }
}
