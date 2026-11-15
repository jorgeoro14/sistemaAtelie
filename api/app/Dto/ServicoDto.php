<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Validator;
use App\Enums\CategoriaServico;

final class ServicoDto
{
    private function __construct(
        public readonly string $nome,
        public readonly CategoriaServico $categoria,
        public readonly ?int $valorPadraoCentavos,
        public readonly ?bool $ativo,
    ) {
    }

    /** @param array<string, mixed> $d */
    public static function deArray(array $d): self
    {
        $v = new Validator($d);
        $nome = $v->texto('nome', true, 2, 120);
        $categoria = $v->opcao('categoria', CategoriaServico::class, false, CategoriaServico::Outro);
        $valor = $v->inteiro('valor_padrao_centavos', false, 0, 100_000_000);
        $ativo = $v->booleano('ativo');
        $v->lancarSeInvalido();
        return new self((string) $nome, $categoria, $valor, $ativo);
    }
}
