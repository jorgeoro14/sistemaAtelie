<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\PeriodoDto;
use App\Enums\FormaPagamento;
use App\Repositories\RelatorioRepository;

/** Dashboard de desempenho (T14). Todos os valores em centavos. */
final class RelatorioService
{
    public function __construct(private readonly RelatorioRepository $relatorios)
    {
    }

    /** @return array<string, int> */
    public function resumo(PeriodoDto $p): array
    {
        return $this->relatorios->resumo($p->de, $p->ate);
    }

    /** @return list<array<string, mixed>> */
    public function costureiras(PeriodoDto $p): array
    {
        return array_map(static fn(array $l) => [
            'costureira_id' => $l['costureira_id'] !== null ? (int) $l['costureira_id'] : null,
            'nome' => (string) $l['nome'],
            'os' => (int) $l['os'],
            'concluidas' => (int) $l['concluidas'],
            'valor_total_centavos' => (int) $l['valor_total_centavos'],
            'tempo_medio_dias' => $l['tempo_medio_dias'] !== null ? round((float) $l['tempo_medio_dias'], 1) : null,
        ], $this->relatorios->porCostureira($p->de, $p->ate));
    }

    /** @return array<string, mixed> */
    public function clientes(PeriodoDto $p): array
    {
        $r = $this->relatorios->clientesResumo($p->de, $p->ate);
        $r['melhores'] = array_map(static fn(array $l) => [
            'id' => (int) $l['id'],
            'nome' => (string) $l['nome'],
            'os' => (int) $l['os'],
            'total_centavos' => (int) $l['total_centavos'],
        ], $this->relatorios->melhoresClientes($p->de, $p->ate));
        return $r;
    }

    /** @return list<array<string, mixed>> 12 meses, sempre completos (meses sem dados = 0) */
    public function faturamentoMensal(int $ano): array
    {
        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $meses[$m] = [
                'mes' => $m,
                'total_centavos' => 0,
                'os' => 0,
                'por_forma' => array_fill_keys(FormaPagamento::valores(), 0),
            ];
        }
        foreach ($this->relatorios->faturamentoMensal($ano) as $l) {
            $m = (int) $l['mes'];
            $meses[$m]['total_centavos'] += (int) $l['total_centavos'];
            $meses[$m]['os'] += (int) $l['os'];
            $meses[$m]['por_forma'][(string) $l['forma']] = (int) $l['total_centavos'];
        }
        return array_values($meses);
    }
}
