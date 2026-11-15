<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Dto\Paginacao;
use App\Dto\ServicoDto;
use App\Services\ServicoService;

final class ServicoController
{
    public function __construct(private readonly ServicoService $servicos)
    {
    }

    public function listar(Request $req): Response
    {
        return Response::json(
            $this->servicos->listar(Paginacao::daRequisicao($req), $req->consultaBool('ativo'))->paraArray()
        );
    }

    public function criar(Request $req): Response
    {
        return Response::json($this->servicos->criar(ServicoDto::deArray($req->corpo()))->paraArray(), 201);
    }

    public function atualizar(Request $req, int $id): Response
    {
        return Response::json($this->servicos->atualizar($id, ServicoDto::deArray($req->corpo()))->paraArray());
    }
}
