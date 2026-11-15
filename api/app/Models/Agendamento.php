<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusAgendamento;
use App\Enums\TipoAgendamento;

/** Compromisso na agenda (prova, medidas, retirada...). Entregas vêm da OS. */
final class Agendamento
{
    public function __construct(
        public readonly int $id,
        public readonly int $clienteId,
        public readonly ?int $osId,
        public readonly string $dataHora,
        public readonly TipoAgendamento $tipo,
        public readonly ?string $observacao,
        public readonly StatusAgendamento $status,
        public readonly string $criadoEm,
        public readonly string $atualizadoEm,
        public readonly ?string $clienteNome = null,
        public readonly ?int $osNumero = null,
    ) {
    }

    /** @param array<string, mixed> $r */
    public static function deLinha(array $r): self
    {
        return new self(
            (int) $r['id'],
            (int) $r['cliente_id'],
            $r['os_id'] !== null ? (int) $r['os_id'] : null,
            (string) $r['data_hora'],
            TipoAgendamento::from((string) $r['tipo']),
            $r['observacao'] !== null ? (string) $r['observacao'] : null,
            StatusAgendamento::from((string) $r['status']),
            (string) $r['criado_em'],
            (string) $r['atualizado_em'],
            isset($r['cliente_nome']) ? (string) $r['cliente_nome'] : null,
            isset($r['os_numero']) ? (int) $r['os_numero'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->clienteId,
            'cliente_nome' => $this->clienteNome,
            'os_id' => $this->osId,
            'os_numero' => $this->osNumero,
            'data_hora' => substr($this->dataHora, 0, 16),
            'tipo' => $this->tipo->value,
            'observacao' => $this->observacao,
            'status' => $this->status->value,
            'criado_em' => $this->criadoEm,
            'atualizado_em' => $this->atualizadoEm,
        ];
    }
}
