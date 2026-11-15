<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Validator;

final class LoginDto
{
    private function __construct(
        public readonly string $usuario,
        public readonly string $senha,
    ) {
    }

    /** @param array<string, mixed> $d */
    public static function deArray(array $d): self
    {
        $v = new Validator($d);
        $usuario = $v->texto('usuario', true, 1, 60);
        $senha = $d['senha'] ?? null;
        if (!is_string($senha) || $senha === '') {
            $v->erro('senha', 'obrigatório');
        }
        $v->lancarSeInvalido();
        return new self((string) $usuario, (string) $senha);
    }
}
