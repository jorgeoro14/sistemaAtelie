<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\PeriodoDto;
use App\Exceptions\RegraNegocioException;
use App\Models\Agendamento;
use App\Models\OrdemServico;
use App\Repositories\AgendamentoRepository;
use App\Repositories\OrdemServicoRepository;

/**
 * Agenda unificada: entregas previstas (vêm da OS, nunca duplicadas na tabela
 * agendamentos) + compromissos (prova, medidas, retirada, outro).
 */
final class AgendaService
{
    private const ROTULO_TIPO = [
        'prova' => 'Prova',
        'medidas' => 'Medidas',
        'retirada' => 'Retirada',
        'outro' => 'Compromisso',
    ];

    public function __construct(
        private readonly OrdemServicoRepository $ordens,
        private readonly AgendamentoRepository $agendamentos,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function periodo(PeriodoDto $p, ?int $costureiraId = null): array
    {
        $dias = (strtotime($p->ate) - strtotime($p->de)) / 86400;
        if ($dias > 400) {
            throw new RegraNegocioException('periodo_grande', 'Escolha um período de no máximo 400 dias.');
        }

        $itens = [];
        foreach ($this->ordens->entregasNoPeriodo($p->de, $p->ate, $costureiraId) as $os) {
            $itens[] = $this->itemEntrega($os);
        }
        foreach ($this->agendamentos->noPeriodo($p->de, $p->ate, $costureiraId) as $a) {
            $itens[] = $this->itemCompromisso($a);
        }

        usort($itens, static fn(array $x, array $y) =>
            [$x['data'], $x['hora'] ?? '', $x['titulo']] <=> [$y['data'], $y['hora'] ?? '', $y['titulo']]);
        return $itens;
    }

    /** @return array<string, mixed> */
    private function itemEntrega(OrdemServico $os): array
    {
        return [
            'origem' => 'entrega',
            'id' => $os->id,
            'data' => $os->dataPrevista,
            'hora' => null,
            'titulo' => "Entrega OS #{$os->numero}",
            'cliente_id' => $os->clienteId,
            'cliente_nome' => $os->clienteNome,
            'costureira_id' => $os->costureiraId,
            'costureira_nome' => $os->costureiraNome,
            'os_id' => $os->id,
            'os_numero' => $os->numero,
            'status' => $os->status->value,
            'prioridade' => $os->prioridade->value,
            'atrasada' => $os->atrasada(),
            'link' => "/os/{$os->id}",
        ];
    }

    /** @return array<string, mixed> */
    private function itemCompromisso(Agendamento $a): array
    {
        $rotulo = self::ROTULO_TIPO[$a->tipo->value] ?? 'Compromisso';
        return [
            'origem' => 'compromisso',
            'id' => $a->id,
            'data' => substr($a->dataHora, 0, 10),
            'hora' => substr($a->dataHora, 11, 5),
            'titulo' => "{$rotulo} — {$a->clienteNome}",
            'cliente_id' => $a->clienteId,
            'cliente_nome' => $a->clienteNome,
            'costureira_id' => null,
            'costureira_nome' => null,
            'os_id' => $a->osId,
            'os_numero' => $a->osNumero,
            'tipo' => $a->tipo->value,
            'status' => $a->status->value,
            'link' => "/agenda?compromisso={$a->id}",
        ];
    }
}
