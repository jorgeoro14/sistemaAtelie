<?php

declare(strict_types=1);

namespace App\Exceptions;

/** HTTP 401. */
final class NaoAutenticadoException extends HttpException
{
    public function __construct(string $mensagem = 'Faça login para continuar.', string $codigo = 'nao_autenticado')
    {
        parent::__construct(401, $codigo, $mensagem);
    }
}
