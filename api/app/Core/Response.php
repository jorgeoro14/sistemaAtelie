<?php

declare(strict_types=1);

namespace App\Core;

/** Resposta JSON padronizada. */
final class Response
{
    public function __construct(
        private readonly mixed $dados,
        private readonly int $status = 200,
    ) {
    }

    public static function json(mixed $dados, int $status = 200): self
    {
        return new self($dados, $status);
    }

    public static function vazio(int $status = 204): self
    {
        return new self(null, $status);
    }

    public function enviar(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        if ($this->dados !== null) {
            echo json_encode(
                $this->dados,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION
            );
        }
    }
}
