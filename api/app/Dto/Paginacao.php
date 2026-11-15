<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Request;

/** Parâmetros ?pagina=1&por_pagina=20&q= das listagens. */
final class Paginacao
{
    private function __construct(
        public readonly int $pagina,
        public readonly int $porPagina,
        public readonly string $q,
    ) {
    }

    public static function daRequisicao(Request $req): self
    {
        $pagina = max(1, (int) ($req->consulta('pagina') ?? 1));
        $por = (int) ($req->consulta('por_pagina') ?? 20);
        $por = min(100, max(1, $por));
        $q = mb_substr(trim($req->consulta('q') ?? ''), 0, 100);
        return new self($pagina, $por, $q);
    }

    /** Primeiros N registros (usado em listas curtas, como o dashboard). */
    public static function primeiras(int $n): self
    {
        return new self(1, $n, '');
    }

    public function offset(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }
}
