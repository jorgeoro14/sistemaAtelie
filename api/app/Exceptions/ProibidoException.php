<?php

declare(strict_types=1);

namespace App\Exceptions;

/** HTTP 403. */
final class ProibidoException extends HttpException
{
    public function __construct(string $mensagem = 'Você não tem permissão para esta ação.', string $codigo = 'proibido')
    {
        parent::__construct(403, $codigo, $mensagem);
    }
}
