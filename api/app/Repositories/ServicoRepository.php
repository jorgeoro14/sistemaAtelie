<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Sql;
use App\Dto\Paginacao;
use App\Models\Servico;

final class ServicoRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function buscarPorId(int $id): ?Servico
    {
        $r = $this->db->um('SELECT * FROM servicos WHERE id = :id', ['id' => $id]);
        return $r ? Servico::deLinha($r) : null;
    }

    /** @return array{0: list<Servico>, 1: int} */
    public function listar(Paginacao $p, ?bool $ativo): array
    {
        $cond = ['1=1'];
        $params = [];
        if ($p->q !== '') {
            $cond[] = 'nome LIKE :q';
            $params['q'] = Sql::contem($p->q);
        }
        if ($ativo !== null) {
            $cond[] = 'ativo = :ativo';
            $params['ativo'] = (int) $ativo;
        }
        $where = implode(' AND ', $cond);
        $total = (int) $this->db->escalar("SELECT COUNT(*) FROM servicos WHERE {$where}", $params);
        $params['lim'] = $p->porPagina;
        $params['off'] = $p->offset();
        $linhas = $this->db->todos(
            "SELECT * FROM servicos WHERE {$where} ORDER BY nome ASC LIMIT :lim OFFSET :off",
            $params
        );
        return [array_map(Servico::deLinha(...), $linhas), $total];
    }

    public function criar(string $nome, string $categoria, ?int $valor): int
    {
        $this->db->executar(
            'INSERT INTO servicos (nome, categoria, valor_padrao_centavos) VALUES (:n, :c, :v)',
            ['n' => $nome, 'c' => $categoria, 'v' => $valor]
        );
        return $this->db->ultimoId();
    }

    public function atualizar(int $id, string $nome, string $categoria, ?int $valor, bool $ativo): void
    {
        $this->db->executar(
            'UPDATE servicos SET nome = :n, categoria = :c, valor_padrao_centavos = :v, ativo = :a WHERE id = :id',
            ['n' => $nome, 'c' => $categoria, 'v' => $valor, 'a' => (int) $ativo, 'id' => $id]
        );
    }
}
