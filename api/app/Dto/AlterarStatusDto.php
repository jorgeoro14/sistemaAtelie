<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Validator;
use App\Enums\StatusOs;

final class AlterarStatusDto
{
    private function __construct(
        public readonly StatusOs $status,
        public readonly ?string $data,
    ) {
    }

    /** @param array<string, mixed> $d */
    public static function deArray(array $d): self
    {
        $v = new Validator($d);
        $status = $v->opcao('status', StatusOs::class, true);
        $data = $v->data('data', false);
        $v->lancarSeInvalido();
        return new self($status, $data);
    }
}
