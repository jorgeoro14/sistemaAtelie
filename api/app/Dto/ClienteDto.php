<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Validator;

final class ClienteDto
{
    private function __construct(
        public readonly string $nome,
        public readonly string $telefone,
        public readonly ?string $observacoes,
        public readonly ?bool $ativo,
    ) {
    }

    /** @param array<string, mixed> $d */
    public static function deArray(array $d): self
    {
        $v = new Validator($d);
        $nome = $v->texto('nome', true, 2, 120);
        $telefone = $v->telefone('telefone', true);
        $obs = $v->texto('observacoes', false, 0, 2000);
        $ativo = $v->booleano('ativo');
        $v->lancarSeInvalido();
        return new self((string) $nome, (string) $telefone, $obs, $ativo);
    }
}
