<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Dto\LoginDto;
use App\Dto\TrocarSenhaDto;
use App\Models\Usuario;
use App\Services\AuthService;

final class AuthController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(Request $req): Response
    {
        $usuario = $this->auth->entrar(LoginDto::deArray($req->corpo()));
        return Response::json($this->auth->perfilDaSessao($usuario));
    }

    public function logout(Request $req): Response
    {
        $this->auth->sair();
        return Response::json(['ok' => true]);
    }

    public function me(Request $req): Response
    {
        /** @var Usuario $usuario */
        $usuario = $req->atributo('usuario');
        return Response::json($this->auth->perfilDaSessao($usuario));
    }

    public function trocarSenha(Request $req): Response
    {
        /** @var Usuario $usuario */
        $usuario = $req->atributo('usuario');
        $this->auth->trocarSenha($usuario, TrocarSenhaDto::deArray($req->corpo()));
        return Response::json(['ok' => true]);
    }
}
