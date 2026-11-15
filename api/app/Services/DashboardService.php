<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Clock;
use App\Dto\FiltroOsDto;
use App\Dto\Paginacao;
use App\Dto\PeriodoDto;
use App\Models\OrdemServico;
use App\Repositories\DashboardRepository;
use App\Repositories\OrdemServicoRepository;

/** Tela inicial: contadores e listas curtas do dia. */
final class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $dashboard,
        private readonly OrdemServicoRepository $ordens,
        private readonly AgendaService $agenda,
    ) {
    }

    /** @return array<string, mixed> */
    public function resumo(): array
    {
        $hoje = Clock::hoje();
        [$atrasadas] = $this->ordens->listar(FiltroOsDto::somenteAtrasadas(), Paginacao::primeiras(5));
        $doDia = $this->agenda->periodo(PeriodoDto::daRequisicaoDireta($hoje, $hoje));

        return [
            'data' => $hoje,
            'contadores' => $this->dashboard->contadores($hoje),
            'atrasadas' => array_map(static fn(OrdemServico $o) => $o->paraArray(false), $atrasadas),
            'agenda_hoje' => $doDia,
        ];
    }
}
