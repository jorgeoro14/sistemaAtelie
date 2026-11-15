<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Dto\ClienteDto;
use App\Dto\Paginacao;
use App\Services\ClienteService;

final class ClienteController
{
    public function __construct(private readonly ClienteService $clientes)
    {
    }

    public function listar(Request $req): Response
    {
        return Response::json(
            $this->clientes->listar(Paginacao::daRequisicao($req), $req->consultaBool('ativo'))->paraArray()
        );
    }

    public function detalhe(Request $req, int $id): Response
    {
        return Response::json($this->clientes->detalhe($id));
    }

    public function criar(Request $req): Response
    {
        return Response::json($this->clientes->criar(ClienteDto::deArray($req->corpo())), 201);
    }

    public function atualizar(Request $req, int $id): Response
    {
        return Response::json($this->clientes->atualizar($id, ClienteDto::deArray($req->corpo())));
    }

    public function excluir(Request $req, int $id): Response
    {
        return Response::json(['acao' => $this->clientes->excluir($id)]);
    }
}
