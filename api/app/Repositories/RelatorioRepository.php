<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Consultas do dashboard de desempenho (T14).
 * Definição de faturamento: soma do total das OS PAGAS, por data_pagamento (seção 10).
 */
final class RelatorioRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return array<string, int> */
    public function resumo(string $de, string $ate): array
    {
        $fat = $this->db->um(
            "SELECT COALESCE(SUM(total_centavos), 0) AS faturamento, COUNT(*) AS os_pagas
             FROM ordens_servico
             WHERE data_pagamento BETWEEN :de AND :ate AND status <> 'cancelado'",
            ['de' => $de, 'ate' => $ate]
        );
        // "A receber" = OS não canceladas ainda sem pagamento (posição atual, sem filtro de período)
        $receber = (int) $this->db->escalar(
            "SELECT COALESCE(SUM(total_centavos), 0) FROM ordens_servico
             WHERE data_pagamento IS NULL AND status <> 'cancelado'"
        );
        $cont = $this->db->um(
            "SELECT
                COALESCE(SUM(data_recebimento BETWEEN :d1 AND :a1), 0)                        AS recebidas,
                COALESCE(SUM(data_entrega BETWEEN :d2 AND :a2 AND status = 'entregue'), 0)    AS entregues,
                COALESCE(SUM(data_recebimento BETWEEN :d3 AND :a3 AND status = 'cancelado'), 0) AS canceladas
             FROM ordens_servico",
            ['d1' => $de, 'a1' => $ate, 'd2' => $de, 'a2' => $ate, 'd3' => $de, 'a3' => $ate]
        ) ?? [];

        $faturamento = (int) ($fat['faturamento'] ?? 0);
        $osPagas = (int) ($fat['os_pagas'] ?? 0);
        return [
            'faturamento_centavos' => $faturamento,
            'a_receber_centavos' => $receber,
            'os_pagas' => $osPagas,
            'os_recebidas' => (int) ($cont['recebidas'] ?? 0),
            'os_entregues' => (int) ($cont['entregues'] ?? 0),
            'os_canceladas' => (int) ($cont['canceladas'] ?? 0),
            'ticket_medio_centavos' => $osPagas > 0 ? intdiv($faturamento, $osPagas) : 0,
        ];
    }

    /** @return list<array<string, mixed>> uma linha por costureira + "sem costureira" */
    public function porCostureira(string $de, string $ate): array
    {
        return $this->db->todos(
            "SELECT o.costureira_id AS costureira_id,
                    COALESCE(k.nome, 'Sem costureira') AS nome,
                    COUNT(*) AS os,
                    SUM(o.status IN ('pronto','entregue')) AS concluidas,
                    COALESCE(SUM(o.total_centavos), 0) AS valor_total_centavos,
                    AVG(CASE WHEN o.data_pronto IS NOT NULL THEN DATEDIFF(o.data_pronto, o.data_recebimento) END) AS tempo_medio_dias
             FROM ordens_servico o
             LEFT JOIN costureiras k ON k.id = o.costureira_id
             WHERE o.data_recebimento BETWEEN :de AND :ate AND o.status <> 'cancelado'
             GROUP BY o.costureira_id, k.nome
             ORDER BY (o.costureira_id IS NULL) ASC, valor_total_centavos DESC",
            ['de' => $de, 'ate' => $ate]
        );
    }

    /** @return array<string, int> */
    public function clientesResumo(string $de, string $ate): array
    {
        return [
            'total_cadastrados' => (int) $this->db->escalar('SELECT COUNT(*) FROM clientes'),
            'novos_periodo' => (int) $this->db->escalar(
                'SELECT COUNT(*) FROM clientes WHERE DATE(criado_em) BETWEEN :de AND :ate',
                ['de' => $de, 'ate' => $ate]
            ),
            'atendidos_periodo' => (int) $this->db->escalar(
                "SELECT COUNT(DISTINCT cliente_id) FROM ordens_servico
                 WHERE data_recebimento BETWEEN :de AND :ate AND status <> 'cancelado'",
                ['de' => $de, 'ate' => $ate]
            ),
        ];
    }

    /** @return list<array<string, mixed>> os 10 clientes que mais gastaram (OS pagas) no período */
    public function melhoresClientes(string $de, string $ate): array
    {
        return $this->db->todos(
            "SELECT c.id, c.nome, COUNT(*) AS os, SUM(o.total_centavos) AS total_centavos
             FROM ordens_servico o JOIN clientes c ON c.id = o.cliente_id
             WHERE o.data_pagamento BETWEEN :de AND :ate AND o.status <> 'cancelado'
             GROUP BY c.id, c.nome
             ORDER BY total_centavos DESC, c.nome ASC
             LIMIT 10",
            ['de' => $de, 'ate' => $ate]
        );
    }

    /** @return list<array<string, mixed>> faturamento por mês e forma de pagamento no ano */
    public function faturamentoMensal(int $ano): array
    {
        return $this->db->todos(
            "SELECT MONTH(data_pagamento) AS mes, forma_pagamento AS forma,
                    SUM(total_centavos) AS total_centavos, COUNT(*) AS os
             FROM ordens_servico
             WHERE YEAR(data_pagamento) = :ano AND status <> 'cancelado'
             GROUP BY MONTH(data_pagamento), forma_pagamento",
            ['ano' => $ano]
        );
    }
}
