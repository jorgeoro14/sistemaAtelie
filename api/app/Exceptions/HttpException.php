<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Erro de negócio/HTTP com formato padronizado: { erro, mensagem, campos }. */
class HttpException extends RuntimeException
{
    /** @param array<string, string> $campos */
    public function __construct(
        public readonly int $status,
        public readonly string $codigo,
        string $mensagem,
        public readonly array $campos = [],
    ) {
        parent::__construct($mensagem, $status);
    }

    /** @return array<string, mixed> */
    public function paraArray(): array
    {
        $r = ['erro' => $this->codigo, 'mensagem' => $this->getMessage()];
        if ($this->campos !== []) {
            $r['campos'] = $this->campos;
        }
        return $r;
    }
}
