<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Dto\Paginacao;
use App\Dto\RedefinirSenhaDto;
use App\Dto\UsuarioDto;
use App\Models\Usuario;
use App\Services\UsuarioService;

final class UsuarioController
{
    public function __construct(private readonly UsuarioService $usuarios)
    {
    }

    public function listar(Request $req): Response
    {
        return Response::json($this->usuarios->listar(Paginacao::daRequisicao($req))->paraArray());
    }

    public function criar(Request $req): Response
    {
        $u = $this->usuarios->criar(UsuarioDto::deArray($req->corpo(), true));
        return Response::json($u->paraArray(), 201);
    }

    public function atualizar(Request $req, int $id): Response
    {
        /** @var Usuario $executor */
        $executor = $req->atributo('usuario');
        $u = $this->usuarios->atualizar($executor, $id, UsuarioDto::deArray($req->corpo(), false));
        return Response::json($u->paraArray());
    }

    public function redefinirSenha(Request $req, int $id): Response
    {
        $this->usuarios->redefinirSenha($id, RedefinirSenhaDto::deArray($req->corpo()));
        return Response::json(['ok' => true]);
    }
}
