<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Validator;
use App\Enums\FormaPagamento;

/** Pagamento único: quita a OS inteira (decisão 1). Valor = total da OS. */
final class PagamentoDto
{
    private function __construct(
        public readonly FormaPagamento $forma,
        public readonly string $dataPagamento,
    ) {
    }

    /** @param array<string, mixed> $d */
    public static function deArray(array $d, string $hoje): self
    {
        $v = new Validator($d);
        $forma = $v->opcao('forma', FormaPagamento::class, true);
        $data = $v->tem('data_pagamento') && $d['data_pagamento'] !== null && $d['data_pagamento'] !== ''
            ? $v->data('data_pagamento')
            : $hoje;
        if ($data !== null && $data > $hoje) {
            $v->erro('data_pagamento', 'o pagamento só é registrado depois que aconteceu (data futura)');
        }
        $v->lancarSeInvalido();
        return new self($forma, (string) $data);
    }
}
