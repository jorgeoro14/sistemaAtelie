<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Clock;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Dto\PeriodoDto;
use App\Services\RelatorioService;

final class RelatorioController
{
    public function __construct(private readonly RelatorioService $relatorios)
    {
    }

    public function resumo(Request $req): Response
    {
        return Response::json($this->relatorios->resumo(PeriodoDto::daRequisicao($req, Clock::hoje())));
    }

    public function costureiras(Request $req): Response
    {
        return Response::json(['dados' => $this->relatorios->costureiras(PeriodoDto::daRequisicao($req, Clock::hoje()))]);
    }

    public function clientes(Request $req): Response
    {
        return Response::json($this->relatorios->clientes(PeriodoDto::daRequisicao($req, Clock::hoje())));
    }

    public function faturamentoMensal(Request $req): Response
    {
        $v = new Validator($req->query());
        $ano = $v->inteiro('ano', false, 2000, 2100) ?? (int) substr(Clock::hoje(), 0, 4);
        $v->lancarSeInvalido();
        return Response::json(['ano' => $ano, 'meses' => $this->relatorios->faturamentoMensal($ano)]);
    }
}
