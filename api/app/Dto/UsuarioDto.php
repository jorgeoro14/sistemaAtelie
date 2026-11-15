<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Config;
use App\Core\Validator;
use App\Enums\Perfil;

/** Criação (senha obrigatória) e edição (sem senha) de usuário. */
final class UsuarioDto
{
    private function __construct(
        public readonly string $nome,
        public readonly string $usuario,
        public readonly ?string $senha,
        public readonly Perfil $perfil,
        public readonly ?bool $ativo,
    ) {
    }

    /** @param array<string, mixed> $d */
    public static function deArray(array $d, bool $criacao): self
    {
        $v = new Validator($d);
        $nome = $v->texto('nome', true, 2, 120);
        $usuario = $v->texto('usuario', true, 3, 60);
        if ($usuario !== null && !preg_match('/^[A-Za-z0-9._-]+$/', $usuario)) {
            $v->erro('usuario', 'use apenas letras, números, ponto, hífen ou sublinhado');
        }
        $senha = $criacao ? $v->senha('senha', (int) Config::obter('seguranca.senha_minima', 8)) : null;
        $perfil = $v->opcao('perfil', Perfil::class, false, Perfil::Admin);
        $ativo = $v->booleano('ativo');
        $v->lancarSeInvalido();
        return new self((string) $nome, (string) $usuario, $senha, $perfil, $ativo);
    }
}
