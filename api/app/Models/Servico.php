<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoriaServico;

/** Tipo de serviço do catálogo (o valor padrão é só sugestão). */
final class Servico
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly CategoriaServico $categoria,
        public readonly ?int $valorPadraoCentavos,
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
            CategoriaServico::from((string) $r['categoria']),
            $r['valor_padrao_centavos'] !== null ? (int) $r['valor_padrao_centavos'] : null,
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
            'categoria' => $this->categoria->value,
            'valor_padrao_centavos' => $this->valorPadraoCentavos,
            'ativo' => $this->ativo,
            'criado_em' => $this->criadoEm,
            'atualizado_em' => $this->atualizadoEm,
        ];
    }
}
