<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Validator;

final class CostureiraDto
{
    private function __construct(
        public readonly string $nome,
        public readonly ?string $telefone,
        public readonly ?bool $ativa,
    ) {
    }

    /** @param array<string, mixed> $d */
    public static function deArray(array $d): self
    {
        $v = new Validator($d);
        $nome = $v->texto('nome', true, 2, 120);
        $telefone = $v->telefone('telefone', false);
        $ativa = $v->booleano('ativa');
        $v->lancarSeInvalido();
        return new self((string) $nome, $telefone, $ativa);
    }
}
