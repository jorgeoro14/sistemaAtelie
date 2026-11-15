<?php

declare(strict_types=1);

namespace App\Models;

final class Costureira
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly ?string $telefone,
        public readonly bool $ativa,
        public readonly string $criadoEm,
        public readonly string $atualizadoEm,
    ) {
    }

    /** @param array<string, mixed> $r */
    public static function deLinha(array $r): self
    {
        return new self(
            (int) $r['id'],
            (string) $r['nome'],
            $r['telefone'] !== null ? (string) $r['telefone'] : null,
            (bool) $r['ativa'],
            (string) $r['criado_em'],
            (string) $r['atualizado_em'],
        );
    }

    /** @return array<string, mixed> */
    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'ativa' => $this->ativa,
            'criado_em' => $this->criadoEm,
            'atualizado_em' => $this->atualizadoEm,
        ];
    }
}
