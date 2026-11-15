<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Dto\CostureiraDto;
use App\Dto\Paginacao;
use App\Services\CostureiraService;

final class CostureiraController
{
    public function __construct(private readonly CostureiraService $costureiras)
    {
    }

    public function listar(Request $req): Response
    {
        return Response::json(
            $this->costureiras->listar(Paginacao::daRequisicao($req), $req->consultaBool('ativa'))->paraArray()
        );
    }

    public function criar(Request $req): Response
    {
        return Response::json($this->costureiras->criar(CostureiraDto::deArray($req->corpo()))->paraArray(), 201);
    }

    public function atualizar(Request $req, int $id): Response
    {
        return Response::json($this->costureiras->atualizar($id, CostureiraDto::deArray($req->corpo()))->paraArray());
    }
}
