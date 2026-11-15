<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Config;
use App\Core\Validator;

final class RedefinirSenhaDto
{
    private function __construct(public readonly string $novaSenha)
    {
    }

    /** @param array<string, mixed> $d */
    public static function deArray(array $d): self
    {
        $v = new Validator($d);
        $nova = $v->senha('nova_senha', (int) Config::obter('seguranca.senha_minima', 8));
        $v->lancarSeInvalido();
        return new self((string) $nova);
    }
}
