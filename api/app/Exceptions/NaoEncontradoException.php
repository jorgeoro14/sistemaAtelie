<?php

declare(strict_types=1);

namespace App\Exceptions;

/** HTTP 404. */
final class NaoEncontradoException extends HttpException
{
    public function __construct(string $recurso = 'Registro')
    {
        parent::__construct(404, 'nao_encontrado', "{$recurso} não encontrado(a).");
    }
}
