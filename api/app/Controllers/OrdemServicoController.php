<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Clock;
use App\Core\Request;
use App\Core\Response;
use App\Dto\AlterarStatusDto;
use App\Dto\FiltroOsDto;
use App\Dto\OrdemServicoDto;
use App\Dto\PagamentoDto;
use App\Dto\Paginacao;
use App\Models\Usuario;
use App\Services\OrdemServicoService;

final class OrdemServicoController
{
    public function __construct(private readonly OrdemServicoService $ordens)
    {
    }

    public function listar(Request $req): Response
    {
        $pagina = $this->ordens->listar(FiltroOsDto::daRequisicao($req), Paginacao::daRequisicao($req));
        return Response::json($pagina->paraArray());
    }

    public function detalhe(Request $req, int $id): Response
    {
        return Response::json($this->ordens->obter($id)->paraArray());
    }

    public function criar(Request $req): Response
    {
        /** @var Usuario $usuario */
        $usuario = $req->atributo('usuario');
        $os = $this->ordens->criar(OrdemServicoDto::deArray($req->corpo(), Clock::hoje()), $usuario);
        return Response::json($os->paraArray(), 201);
    }

    public function atualizar(Request $req, int $id): Response
    {
        $os = $this->ordens->atualizar($id, OrdemServicoDto::deArray($req->corpo(), Clock::hoje()));
        return Response::json($os->paraArray());
    }

    public function mudarStatus(Request $req, int $id): Response
    {
        return Response::json($this->ordens->mudarStatus($id, AlterarStatusDto::deArray($req->corpo()))->paraArray());
    }

    public function registrarPagamento(Request $req, int $id): Response
    {
        $os = $this->ordens->registrarPagamento($id, PagamentoDto::deArray($req->corpo(), Clock::hoje()));
        return Response::json($os->paraArray());
    }

    public function estornarPagamento(Request $req, int $id): Response
    {
        return Response::json($this->ordens->estornarPagamento($id)->paraArray());
    }
}
