<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Config;
use App\Core\Validator;

final class TrocarSenhaDto
{
    private function __construct(
        public readonly string $senhaAtual,
        public readonly string $novaSenha,
    ) {
    }

    /** @param array<string, mixed> $d */
    public static function deArray(array $d): self
    {
        $v = new Validator($d);
        $atual = $d['senha_atual'] ?? null;
        if (!is_string($atual) || $atual === '') {
            $v->erro('senha_atual', 'obrigatório');
        }
        $nova = $v->senha('nova_senha', (int) Config::obter('seguranca.senha_minima', 8));
        if ($nova !== null && $nova === $atual) {
            $v->erro('nova_senha', 'a nova senha deve ser diferente da atual');
        }
        if (array_key_exists('confirmacao', $d) && $d['confirmacao'] !== $nova) {
            $v->erro('confirmacao', 'a confirmação não confere com a nova senha');
        }
        $v->lancarSeInvalido();
        return new self((string) $atual, (string) $nova);
    }
}
