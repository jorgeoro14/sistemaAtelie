<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Validator;

/** Uma linha de serviço da OS (RN02). */
final class ItemOsDto
{
    public function __construct(
        public readonly int $servicoId,
        public readonly string $descricao,
        public readonly int $valorCentavos,
    ) {
    }

    /**
     * @param array<string, mixed> $d
     * @return array{0: ?self, 1: array<string, string>}
     */
    public static function validar(array $d, int $indice): array
    {
        $v = new Validator($d, "itens.{$indice}.");
        $servico = $v->inteiro('servico_id', true, 1);
        $descricao = $v->texto('descricao', true, 2, 255);
        $valor = $v->inteiro('valor_centavos', true, 0, 100_000_000);
        if ($v->temErros()) {
            return [null, $v->erros()];
        }
        return [new self((int) $servico, (string) $descricao, (int) $valor), []];
    }
}
