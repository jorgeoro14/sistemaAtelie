<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Perfil;

/** Usuário do sistema (login e gestão de acesso). */
final class Usuario
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $usuario,
        public readonly string $senhaHash,
        public readonly Perfil $perfil,
        public readonly bool $ativo,
        public readonly bool $trocarSenha,
        public readonly int $tentativasLogin,
        public readonly ?string $bloqueadoAte,
        public readonly string $criadoEm,
        public readonly string $atualizadoEm,
    ) {
    }

    /** @param array<string, mixed> $r */
    public static function deLinha(array $r): self
    {
        return new self(
            (int) $r['id'],
            (string) $r['nome'],
            (string) $r['usuario'],
            (string) $r['senha_hash'],
            Perfil::from((string) $r['perfil']),
            (bool) $r['ativo'],
            (bool) $r['trocar_senha'],
            (int) $r['tentativas_login'],
            $r['bloqueado_ate'] !== null ? (string) $r['bloqueado_ate'] : null,
            (string) $r['criado_em'],
            (string) $r['atualizado_em'],
        );
    }

    public function estaBloqueado(string $agora): bool
    {
        return $this->bloqueadoAte !== null && $this->bloqueadoAte > $agora;
    }

    /** Nunca expõe senha_hash. @return array<string, mixed> */
    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'usuario' => $this->usuario,
            'perfil' => $this->perfil->value,
            'ativo' => $this->ativo,
            'trocar_senha' => $this->trocarSenha,
            'criado_em' => $this->criadoEm,
            'atualizado_em' => $this->atualizadoEm,
        ];
    }
}
