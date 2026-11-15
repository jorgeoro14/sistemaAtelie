<?php

declare(strict_types=1);

namespace App\Exceptions;

/** HTTP 422 — dados inválidos, com o erro de cada campo. */
final class ValidationException extends HttpException
{
    /** @param array<string, string> $campos */
    public function __construct(array $campos, string $mensagem = 'Confira os campos destacados.')
    {
        parent::__construct(422, 'dados_invalidos', $mensagem, $campos);
    }
}
