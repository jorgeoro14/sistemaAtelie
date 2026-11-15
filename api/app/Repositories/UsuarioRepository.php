<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Sql;
use App\Dto\Paginacao;
use App\Models\Usuario;

final class UsuarioRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $r = $this->db->um('SELECT * FROM usuarios WHERE id = :id', ['id' => $id]);
        return $r ? Usuario::deLinha($r) : null;
    }

    public function buscarPorUsuario(string $usuario): ?Usuario
    {
        $r = $this->db->um('SELECT * FROM usuarios WHERE usuario = :u', ['u' => $usuario]);
        return $r ? Usuario::deLinha($r) : null;
    }

    /** @return array{0: list<Usuario>, 1: int} */
    public function listar(Paginacao $p): array
    {
        $where = '1=1';
        $params = [];
        if ($p->q !== '') {
            $where = '(nome LIKE :q1 OR usuario LIKE :q2)';
            $params['q1'] = $params['q2'] = Sql::contem($p->q);
        }
        $total = (int) $this->db->escalar("SELECT COUNT(*) FROM usuarios WHERE {$where}", $params);
        $params['lim'] = $p->porPagina;
        $params['off'] = $p->offset();
        $linhas = $this->db->todos(
            "SELECT * FROM usuarios WHERE {$where} ORDER BY nome ASC LIMIT :lim OFFSET :off",
            $params
        );
        return [array_map(Usuario::deLinha(...), $linhas), $total];
    }

    public function existeUsuario(string $usuario, ?int $excetoId = null): bool
    {
        return (int) $this->db->escalar(
            'SELECT COUNT(*) FROM usuarios WHERE usuario = :u AND id <> :id',
            ['u' => $usuario, 'id' => $excetoId ?? 0]
        ) > 0;
    }

    public function criar(string $nome, string $usuario, string $senhaHash, string $perfil, bool $ativo): int
    {
        $this->db->executar(
            'INSERT INTO usuarios (nome, usuario, senha_hash, perfil, ativo, trocar_senha)
             VALUES (:nome, :usuario, :hash, :perfil, :ativo, 1)',
            ['nome' => $nome, 'usuario' => $usuario, 'hash' => $senhaHash, 'perfil' => $perfil, 'ativo' => (int) $ativo]
        );
        return $this->db->ultimoId();
    }

    public function atualizar(int $id, string $nome, string $usuario, string $perfil, bool $ativo): void
    {
        $this->db->executar(
            'UPDATE usuarios SET nome = :nome, usuario = :usuario, perfil = :perfil, ativo = :ativo WHERE id = :id',
            ['nome' => $nome, 'usuario' => $usuario, 'perfil' => $perfil, 'ativo' => (int) $ativo, 'id' => $id]
        );
    }

    public function atualizarSenha(int $id, string $hash, bool $trocarSenha): void
    {
        $this->db->executar(
            'UPDATE usuarios SET senha_hash = :h, trocar_senha = :t, tentativas_login = 0, bloqueado_ate = NULL WHERE id = :id',
            ['h' => $hash, 't' => (int) $trocarSenha, 'id' => $id]
        );
    }

    public function registrarFalhaLogin(int $id, int $tentativas, ?string $bloqueadoAte): void
    {
        $this->db->executar(
            'UPDATE usuarios SET tentativas_login = :t, bloqueado_ate = :b WHERE id = :id',
            ['t' => $tentativas, 'b' => $bloqueadoAte, 'id' => $id]
        );
    }

    public function zerarTentativas(int $id): void
    {
        $this->db->executar(
            'UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = :id',
            ['id' => $id]
        );
    }

    public function contarAdminsAtivos(): int
    {
        return (int) $this->db->escalar("SELECT COUNT(*) FROM usuarios WHERE perfil = 'admin' AND ativo = 1");
    }

    /** Usuário com OS criadas tem histórico: nunca é apagado, só inativado (RN13). */
    public function contarOsCriadas(int $id): int
    {
        return (int) $this->db->escalar('SELECT COUNT(*) FROM ordens_servico WHERE criado_por = :id', ['id' => $id]);
    }
}
