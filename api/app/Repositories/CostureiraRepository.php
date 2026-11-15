<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Sql;
use App\Dto\Paginacao;
use App\Models\Costureira;

final class CostureiraRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function buscarPorId(int $id): ?Costureira
    {
        $r = $this->db->um('SELECT * FROM costureiras WHERE id = :id', ['id' => $id]);
        return $r ? Costureira::deLinha($r) : null;
    }

    /** @return array{0: list<Costureira>, 1: int} */
    public function listar(Paginacao $p, ?bool $ativa): array
    {
        $cond = ['1=1'];
        $params = [];
        if ($p->q !== '') {
            $cond[] = 'nome LIKE :q';
            $params['q'] = Sql::contem($p->q);
        }
        if ($ativa !== null) {
            $cond[] = 'ativa = :ativa';
            $params['ativa'] = (int) $ativa;
        }
        $where = implode(' AND ', $cond);
        $total = (int) $this->db->escalar("SELECT COUNT(*) FROM costureiras WHERE {$where}", $params);
        $params['lim'] = $p->porPagina;
        $params['off'] = $p->offset();
        $linhas = $this->db->todos(
            "SELECT * FROM costureiras WHERE {$where} ORDER BY nome ASC LIMIT :lim OFFSET :off",
            $params
        );
        return [array_map(Costureira::deLinha(...), $linhas), $total];
    }

    public function criar(string $nome, ?string $telefone): int
    {
        $this->db->executar('INSERT INTO costureiras (nome, telefone) VALUES (:n, :t)', ['n' => $nome, 't' => $telefone]);
        return $this->db->ultimoId();
    }

    public function atualizar(int $id, string $nome, ?string $telefone, bool $ativa): void
    {
        $this->db->executar(
            'UPDATE costureiras SET nome = :n, telefone = :t, ativa = :a WHERE id = :id',
            ['n' => $nome, 't' => $telefone, 'a' => (int) $ativa, 'id' => $id]
        );
    }
}
