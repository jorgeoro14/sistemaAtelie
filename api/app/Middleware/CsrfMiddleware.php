<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Exceptions\ProibidoException;

/** Confere o X-CSRF-Token em toda ação que altera dados (403). */
final class CsrfMiddleware
{
    public function __construct(private readonly Session $sessao)
    {
    }

    public function tratar(Request $req): void
    {
        if (!$this->sessao->csrfValido($req->cabecalho('X-CSRF-Token'))) {
            throw new ProibidoException('Token de segurança inválido. Recarregue a página.', 'csrf_invalido');
        }
    }
}
