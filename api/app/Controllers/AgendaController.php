<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Clock;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Dto\AgendamentoDto;
use App\Dto\PeriodoDto;
use App\Services\AgendaService;
use App\Services\AgendamentoService;

final class AgendaController
{
    public function __construct(
        private readonly AgendaService $agenda,
        private readonly AgendamentoService $agendamentos,
    ) {
    }

    public function agenda(Request $req): Response
    {
        $periodo = PeriodoDto::daRequisicao($req, Clock::hoje(), false);
        $v = new Validator($req->query());
        $costureira = $v->inteiro('costureira_id', false, 1);
        $v->lancarSeInvalido();
        return Response::json(['dados' => $this->agenda->periodo($periodo, $costureira)]);
    }

    public function detalhe(Request $req, int $id): Response
    {
        return Response::json($this->agendamentos->obter($id)->paraArray());
    }

    public function criar(Request $req): Response
    {
        return Response::json($this->agendamentos->criar(AgendamentoDto::deArray($req->corpo())), 201);
    }

    public function atualizar(Request $req, int $id): Response
    {
        return Response::json($this->agendamentos->atualizar($id, AgendamentoDto::deArray($req->corpo())));
    }

    public function cancelar(Request $req, int $id): Response
    {
        $this->agendamentos->cancelar($id);
        return Response::json(['ok' => true]);
    }
}
