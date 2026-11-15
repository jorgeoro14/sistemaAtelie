<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Exceptions\NaoAutenticadoException;
use App\Repositories\UsuarioRepository;

/** Exige sessão válida (401). Carrega o usuário e o expõe na requisição. */
final class AuthMiddleware
{
    public function __construct(
        private readonly Session $sessao,
        private readonly UsuarioRepository $usuarios,
    ) {
    }

    public function tratar(Request $req): void
    {
        $id = $this->sessao->usuarioId();
        if ($id === null) {
            throw new NaoAutenticadoException();
        }
        $usuario = $this->usuarios->buscarPorId($id);
        if ($usuario === null || !$usuario->ativo) {
            $this->sessao->sair();
            throw new NaoAutenticadoException('Sua sessão não é mais válida. Entre novamente.');
        }
        $req->definir('usuario', $usuario);
    }
}
