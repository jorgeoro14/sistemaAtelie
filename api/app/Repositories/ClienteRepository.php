<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Sql;
use App\Dto\Paginacao;
use App\Models\Cliente;

final class ClienteRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function buscarPorId(int $id): ?Cliente
    {
        $r = $this->db->um('SELECT * FROM clientes WHERE id = :id', ['id' => $id]);
        return $r ? Cliente::deLinha($r) : null;
    }

    /** @return array{0: list<Cliente>, 1: int} */
    public function listar(Paginacao $p, ?bool $ativo): array
    {
        $cond = ['1=1'];
        $params = [];
        if ($p->q !== '') {
            $cond[] = '(nome LIKE :q1 OR telefone LIKE :q2)';
            $params['q1'] = Sql::contem($p->q);
            $digitos = preg_replace('/\D+/', '', $p->q) ?? '';
            $params['q2'] = Sql::contem($digitos !== '' ? $digitos : $p->q);
        }
        if ($ativo !== null) {
            $cond[] = 'ativo = :ativo';
            $params['ativo'] = (int) $ativo;
        }
        $where = implode(' AND ', $cond);
        $total = (int) $this->db->escalar("SELECT COUNT(*) FROM clientes WHERE {$where}", $params);
        $params['lim'] = $p->porPagina;
        $params['off'] = $p->offset();
        $linhas = $this->db->todos(
            "SELECT * FROM clientes WHERE {$where} ORDER BY nome ASC LIMIT :lim OFFSET :off",
            $params
        );
        return [array_map(Cliente::deLinha(...), $linhas), $total];
    }

    public function existeTelefone(string $telefone, ?int $excetoId = null): bool
    {
        return (int) $this->db->escalar(
            'SELECT COUNT(*) FROM clientes WHERE telefone = :t AND id <> :id',
            ['t' => $telefone, 'id' => $excetoId ?? 0]
        ) > 0;
    }

    public function criar(string $nome, string $telefone, ?string $observacoes): int
    {
        $this->db->executar(
            'INSERT INTO clientes (nome, telefone, observacoes) VALUES (:n, :t, :o)',
            ['n' => $nome, 't' => $telefone, 'o' => $observacoes]
        );
        return $this->db->ultimoId();
    }

    public function atualizar(int $id, string $nome, string $telefone, ?string $observacoes, bool $ativo): void
    {
        $this->db->executar(
            'UPDATE clientes SET nome = :n, telefone = :t, observacoes = :o, ativo = :a WHERE id = :id',
            ['n' => $nome, 't' => $telefone, 'o' => $observacoes, 'a' => (int) $ativo, 'id' => $id]
        );
    }

    public function inativar(int $id): void
    {
        $this->db->executar('UPDATE clientes SET ativo = 0 WHERE id = :id', ['id' => $id]);
    }

    public function excluir(int $id): void
    {
        $this->db->executar('DELETE FROM clientes WHERE id = :id', ['id' => $id]);
    }

    /** Cliente com OS ou compromissos tem histórico (RN13). */
    public function possuiHistorico(int $id): bool
    {
        $os = (int) $this->db->escalar('SELECT COUNT(*) FROM ordens_servico WHERE cliente_id = :id', ['id' => $id]);
        $ag = (int) $this->db->escalar('SELECT COUNT(*) FROM agendamentos WHERE cliente_id = :id', ['id' => $id]);
        return ($os + $ag) > 0;
    }

    /** Total já gasto = soma das OS pagas, não canceladas. */
    public function totalGastoCentavos(int $id): int
    {
        return (int) $this->db->escalar(
            "SELECT COALESCE(SUM(total_centavos), 0) FROM ordens_servico
             WHERE cliente_id = :id AND data_pagamento IS NOT NULL AND status <> 'cancelado'",
            ['id' => $id]
        );
    }
}
