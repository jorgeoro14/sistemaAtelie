<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Contadores da tela inicial. Cada filtro aqui tem o espelho em GET /api/os (cartões → lista filtrada). */
final class DashboardRepository
{
    private const ABERTA = "status NOT IN ('entregue','cancelado')";

    public function __construct(private readonly Database $db)
    {
    }

    /** @return array<string, int> */
    public function contadores(string $hoje): array
    {
        $a = self::ABERTA;
        $os = $this->db->um(
            "SELECT
                COALESCE(SUM({$a} AND data_prevista < :h1), 0)                       AS atrasadas,
                COALESCE(SUM({$a} AND prioridade = 'urgente'), 0)                    AS urgentes,
                COALESCE(SUM({$a} AND prioridade = 'alta'), 0)                       AS altas,
                COALESCE(SUM({$a} AND data_prevista = :h2), 0)                       AS entregas_hoje,
                COALESCE(SUM(status = 'pronto'), 0)                                  AS prontas_aguardando,
                COALESCE(SUM(status = 'entregue' AND data_pagamento IS NULL), 0)     AS entregues_sem_pagamento,
                COALESCE(SUM(status <> 'cancelado' AND data_pagamento IS NULL), 0)   AS pagamentos_pendentes
             FROM ordens_servico",
            ['h1' => $hoje, 'h2' => $hoje]
        ) ?? [];

        $compromissos = (int) $this->db->escalar(
            "SELECT COUNT(*) FROM agendamentos
             WHERE data_hora BETWEEN :ini AND :fim AND status <> 'cancelado'",
            ['ini' => $hoje . ' 00:00:00', 'fim' => $hoje . ' 23:59:59']
        );

        $r = array_map('intval', $os);
        $r['compromissos_hoje'] = $compromissos;
        return $r;
    }
}
