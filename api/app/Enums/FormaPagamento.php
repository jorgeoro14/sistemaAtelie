<?php

declare(strict_types=1);

namespace App\Enums;

/** RN10: forma de pagamento (registrada manualmente, depois que aconteceu). */
enum FormaPagamento: string
{
    use EnumTrait;

    case Dinheiro = 'dinheiro';
    case Pix = 'pix';
    case CartaoDebito = 'cartao_debito';
    case CartaoCredito = 'cartao_credito';
    case Outro = 'outro';
}
