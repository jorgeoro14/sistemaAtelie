<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Request;
use App\Core\Validator;
use App\Enums\Prioridade;
use App\Enums\StatusOs;

/** Filtros de GET /api/os. */
final class FiltroOsDto
{
    private function __construct(
        public readonly ?StatusOs $status,
        public readonly ?Prioridade $prioridade,
        public readonly ?int $costureiraId,
        public readonly ?int $clienteId,
        public readonly ?string $de,
        public readonly ?string $ate,
        public readonly ?string $pagamento,   // 'pendente' | 'pago'
        public readonly bool $atrasadas,
        public readonly bool $abertas,
    ) {
    }

    /** Filtro interno: somente OS abertas e atrasadas (dashboard). */
    public static function somenteAtrasadas(): self
    {
        return new self(null, null, null, null, null, null, null, true, true);
    }

    public static function daRequisicao(Request $req): self
    {
        $q = $req->query();
        $v = new Validator($q);
        $status = $v->opcao('status', StatusOs::class, false);
        $prioridade = $v->opcao('prioridade', Prioridade::class, false);
        $costureira = $v->inteiro('costureira_id', false, 1);
        $cliente = $v->inteiro('cliente_id', false, 1);
        $de = $v->data('de', false);
        $ate = $v->data('ate', false);
        $pagamento = $q['pagamento'] ?? null;
        if ($pagamento !== null && $pagamento !== '' && !in_array($pagamento, ['pendente', 'pago'], true)) {
            $v->erro('pagamento', 'use pendente ou pago');
        }
        $atrasadas = $v->booleano('atrasadas', false) ?? false;
        $abertas = $v->booleano('abertas', false) ?? false;
        $v->lancarSeInvalido();

        return new self(
            $status, $prioridade, $costureira, $cliente, $de, $ate,
            $pagamento === '' ? null : $pagamento, $atrasadas, $abertas
        );
    }
}
