<?php

declare(strict_types=1);

namespace App\Models;

final class Cliente
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $telefone,
        public readonly ?string $observacoes,
        public readonly bool $ativo,
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
            (string) $r['telefone'],
            $r['observacoes'] !== null ? (string) $r['observacoes'] : null,
            (bool) $r['ativo'],
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
            'observacoes' => $this->observacoes,
            'ativo' => $this->ativo,
            'criado_em' => $this->criadoEm,
            'atualizado_em' => $this->atualizadoEm,
        ];
    }
}
