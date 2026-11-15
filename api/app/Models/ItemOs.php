<?php

declare(strict_types=1);

namespace App\Models;

/** Linha de serviço de uma OS (valor congelado ao salvar). */
final class ItemOs
{
    public function __construct(
        public readonly int $id,
        public readonly int $osId,
        public readonly int $servicoId,
        public readonly ?string $servicoNome,
        public readonly string $descricao,
        public readonly int $valorCentavos,
        public readonly int $ordem,
    ) {
    }

    /** @param array<string, mixed> $r */
    public static function deLinha(array $r): self
    {
        return new self(
            (int) $r['id'],
            (int) $r['os_id'],
            (int) $r['servico_id'],
            isset($r['servico_nome']) ? (string) $r['servico_nome'] : null,
            (string) $r['descricao'],
            (int) $r['valor_centavos'],
            (int) $r['ordem'],
        );
    }

    /** @return array<string, mixed> */
    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'servico_id' => $this->servicoId,
            'servico_nome' => $this->servicoNome,
            'descricao' => $this->descricao,
            'valor_centavos' => $this->valorCentavos,
            'ordem' => $this->ordem,
        ];
    }
}
