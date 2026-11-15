<?php

declare(strict_types=1);

namespace App\Dto;

/** Resultado paginado: { dados: [...], paginacao: {...} }. */
final class Pagina
{
    /** @param list<array<string, mixed>> $dados */
    public function __construct(
        public readonly array $dados,
        public readonly int $total,
        public readonly Paginacao $paginacao,
    ) {
    }

    /** @return array<string, mixed> */
    public function paraArray(): array
    {
        return [
            'dados' => $this->dados,
            'paginacao' => [
                'pagina' => $this->paginacao->pagina,
                'por_pagina' => $this->paginacao->porPagina,
                'total' => $this->total,
                'total_paginas' => max(1, (int) ceil($this->total / $this->paginacao->porPagina)),
            ],
        ];
    }
}
