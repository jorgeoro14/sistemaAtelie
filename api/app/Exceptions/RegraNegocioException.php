<?php

declare(strict_types=1);

namespace App\Exceptions;

/** HTTP 422 — violação de regra de negócio (RNxx). */
final class RegraNegocioException extends HttpException
{
    /** @param array<string, string> $campos */
    public function __construct(string $codigo, string $mensagem, array $campos = [])
    {
        parent::__construct(422, $codigo, $mensagem, $campos);
    }
}
