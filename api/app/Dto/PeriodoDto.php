<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Request;
use App\Core\Validator;

/** Período ?de=&ate= (YYYY-MM-DD). Padrão: mês corrente. */
final class PeriodoDto
{
    private function __construct(
        public readonly string $de,
        public readonly string $ate,
    ) {
    }

    /** Período montado pelo código (sem requisição HTTP). */
    public static function daRequisicaoDireta(string $de, string $ate): self
    {
        return new self($de, $ate);
    }

    public static function daRequisicao(Request $req, string $hoje, bool $padraoMes = true): self
    {
        $v = new Validator($req->query());
        $de = $v->data('de', false);
        $ate = $v->data('ate', false);
        if ($padraoMes) {
            $de ??= substr($hoje, 0, 8) . '01';
            $ate ??= date('Y-m-t', strtotime($de));
        } else {
            if ($de === null) {
                $v->erro('de', 'obrigatória');
            }
            if ($ate === null) {
                $v->erro('ate', 'obrigatória');
            }
        }
        if ($de !== null && $ate !== null && $ate < $de) {
            $v->erro('ate', 'não pode ser anterior à data inicial');
        }
        $v->lancarSeInvalido();
        return new self((string) $de, (string) $ate);
    }
}
