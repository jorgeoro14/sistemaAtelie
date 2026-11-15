<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Clock;
use App\Core\Database;
use App\Core\Sql;
use App\Dto\FiltroOsDto;
use App\Dto\ItemOsDto;
use App\Dto\OrdemServicoDto;
use App\Dto\Paginacao;
use App\Models\ItemOs;
use App\Models\OrdemServico;

final class OrdemServicoRepository
{
    private const SELECT = "SELECT o.*, c.nome AS cliente_nome, c.telefone AS cliente_telefone,
            k.nome AS costureira_nome, u.nome AS criado_por_nome,
            (SELECT GROUP_CONCAT(i.descricao ORDER BY i.ordem, i.id SEPARATOR ' · ')
               FROM os_itens i WHERE i.os_id = o.id) AS resumo_servicos
        FROM ordens_servico o
        JOIN clientes c ON c.id = o.cliente_id
        LEFT JOIN costureiras k ON k.id = o.costureira_id
        JOIN usuarios u ON u.id = o.criado_por";

    public function __construct(private readonly Database $db)
    {
    }

    public function buscarPorId(int $id): ?OrdemServico
    {
        $r = $this->db->um(self::SELECT . ' WHERE o.id = :id', ['id' => $id]);
        return $r ? OrdemServico::deLinha($r, $this->itensDaOs($id)) : null;
    }

    /** @return list<ItemOs> */
    public function itensDaOs(int $osId): array
    {
        $linhas = $this->db->todos(
            'SELECT i.*, s.nome AS servico_nome FROM os_itens i
             JOIN servicos s ON s.id = i.servico_id
             WHERE i.os_id = :id ORDER BY i.ordem, i.id',
            ['id' => $osId]
        );
        return array_map(ItemOs::deLinha(...), $linhas);
    }

    /** RN15: número sequencial, calculado dentro da transação com a linha bloqueada. */
    public function proximoNumero(): int
    {
        return (int) $this->db->escalar('SELECT COALESCE(MAX(numero), 0) + 1 FROM ordens_servico FOR UPDATE');
    }

    public function criar(OrdemServicoDto $dto, int $numero, int $total, int $usuarioId): int
    {
        $this->db->executar(
            'INSERT INTO ordens_servico
                (numero, cliente_id, costureira_id, prioridade, status, data_recebimento, data_prevista,
                 observacoes, total_centavos, criado_por)
             VALUES (:numero, :cliente, :costureira, :prioridade, :status, :rec, :prev, :obs, :total, :criador)',
            [
                'numero' => $numero,
                'cliente' => $dto->clienteId,
                'costureira' => $dto->costureiraId,
                'prioridade' => $dto->prioridade->value,
                'status' => 'recebido',
                'rec' => $dto->dataRecebimento,
                'prev' => $dto->dataPrevista,
                'obs' => $dto->observacoes,
                'total' => $total,
                'criador' => $usuarioId,
            ]
        );
        return $this->db->ultimoId();
    }

    public function atualizar(int $id, OrdemServicoDto $dto, int $total): void
    {
        $this->db->executar(
            'UPDATE ordens_servico SET cliente_id = :cliente, costureira_id = :costureira, prioridade = :prioridade,
                data_recebimento = :rec, data_prevista = :prev, observacoes = :obs, total_centavos = :total
             WHERE id = :id',
            [
                'cliente' => $dto->clienteId,
                'costureira' => $dto->costureiraId,
                'prioridade' => $dto->prioridade->value,
                'rec' => $dto->dataRecebimento,
                'prev' => $dto->dataPrevista,
                'obs' => $dto->observacoes,
                'total' => $total,
                'id' => $id,
            ]
        );
    }

    /** @param list<ItemOsDto> $itens */
    public function substituirItens(int $osId, array $itens): void
    {
        $this->db->executar('DELETE FROM os_itens WHERE os_id = :id', ['id' => $osId]);
        foreach ($itens as $ordem => $item) {
            $this->db->executar(
                'INSERT INTO os_itens (os_id, servico_id, descricao, valor_centavos, ordem)
                 VALUES (:os, :servico, :descricao, :valor, :ordem)',
                [
                    'os' => $osId,
                    'servico' => $item->servicoId,
                    'descricao' => $item->descricao,
                    'valor' => $item->valorCentavos,
                    'ordem' => $ordem + 1,
                ]
            );
        }
    }

    /** Serviços do catálogo já usados nesta OS (para permitir manter serviço inativado). @return list<int> */
    public function servicosUsados(int $osId): array
    {
        $linhas = $this->db->todos('SELECT DISTINCT servico_id FROM os_itens WHERE os_id = :id', ['id' => $osId]);
        return array_map(static fn(array $l) => (int) $l['servico_id'], $linhas);
    }

    public function atualizarStatus(int $id, string $status, ?string $dataPronto, ?string $dataEntrega): void
    {
        $this->db->executar(
            'UPDATE ordens_servico SET status = :s, data_pronto = :p, data_entrega = :e WHERE id = :id',
            ['s' => $status, 'p' => $dataPronto, 'e' => $dataEntrega, 'id' => $id]
        );
    }

    public function registrarPagamento(int $id, string $forma, string $data): void
    {
        $this->db->executar(
            'UPDATE ordens_servico SET forma_pagamento = :f, data_pagamento = :d WHERE id = :id',
            ['f' => $forma, 'd' => $data, 'id' => $id]
        );
    }

    public function estornarPagamento(int $id): void
    {
        $this->db->executar(
            'UPDATE ordens_servico SET forma_pagamento = NULL, data_pagamento = NULL WHERE id = :id',
            ['id' => $id]
        );
    }

    /** @return list<OrdemServico> histórico de OS de um cliente (mais recentes primeiro) */
    public function listarPorCliente(int $clienteId): array
    {
        $linhas = $this->db->todos(
            self::SELECT . ' WHERE o.cliente_id = :id ORDER BY o.numero DESC',
            ['id' => $clienteId]
        );
        return array_map(static fn(array $r) => OrdemServico::deLinha($r), $linhas);
    }

    /**
     * Listagem com filtros e ordenação por urgência (RN08):
     * abertas primeiro → urgente, alta, normal → atrasadas → data prevista.
     *
     * @return array{0: list<OrdemServico>, 1: int}
     */
    public function listar(FiltroOsDto $f, Paginacao $p): array
    {
        $cond = ['1=1'];
        $params = [];

        if ($f->status !== null) {
            $cond[] = 'o.status = :status';
            $params['status'] = $f->status->value;
        }
        if ($f->prioridade !== null) {
            $cond[] = 'o.prioridade = :prioridade';
            $params['prioridade'] = $f->prioridade->value;
        }
        if ($f->costureiraId !== null) {
            $cond[] = 'o.costureira_id = :costureira';
            $params['costureira'] = $f->costureiraId;
        }
        if ($f->clienteId !== null) {
            $cond[] = 'o.cliente_id = :cliente';
            $params['cliente'] = $f->clienteId;
        }
        if ($f->de !== null) {
            $cond[] = 'o.data_prevista >= :de';
            $params['de'] = $f->de;
        }
        if ($f->ate !== null) {
            $cond[] = 'o.data_prevista <= :ate';
            $params['ate'] = $f->ate;
        }
        if ($f->pagamento === 'pago') {
            $cond[] = 'o.data_pagamento IS NOT NULL';
        } elseif ($f->pagamento === 'pendente') {
            // RN11: OS cancelada não conta como pendente
            $cond[] = "o.data_pagamento IS NULL AND o.status <> 'cancelado'";
        }
        if ($f->atrasadas) {
            $cond[] = "o.status NOT IN ('entregue','cancelado') AND o.data_prevista < :hoje_filtro";
            $params['hoje_filtro'] = Clock::hoje();
        }
        if ($f->abertas) {
            $cond[] = "o.status NOT IN ('entregue','cancelado')";
        }
        if ($p->q !== '') {
            $partes = ['c.nome LIKE :q_nome', 'c.telefone LIKE :q_tel'];
            $params['q_nome'] = Sql::contem($p->q);
            $digitos = preg_replace('/\D+/', '', $p->q) ?? '';
            $params['q_tel'] = Sql::contem($digitos !== '' ? $digitos : $p->q);
            if (ctype_digit(ltrim($p->q, '#'))) {
                $partes[] = 'o.numero = :q_num';
                $params['q_num'] = (int) ltrim($p->q, '#');
            }
            $cond[] = '(' . implode(' OR ', $partes) . ')';
        }

        $where = implode(' AND ', $cond);
        $total = (int) $this->db->escalar(
            "SELECT COUNT(*) FROM ordens_servico o JOIN clientes c ON c.id = o.cliente_id WHERE {$where}",
            $params
        );

        $params['hoje_ordem'] = Clock::hoje();
        $params['lim'] = $p->porPagina;
        $params['off'] = $p->offset();
        $linhas = $this->db->todos(
            self::SELECT . " WHERE {$where}
             ORDER BY (o.status IN ('entregue','cancelado')) ASC,
                      FIELD(o.prioridade, 'urgente', 'alta', 'normal') ASC,
                      (o.status NOT IN ('entregue','cancelado') AND o.data_prevista < :hoje_ordem) DESC,
                      o.data_prevista ASC, o.numero DESC
             LIMIT :lim OFFSET :off",
            $params
        );
        return [array_map(static fn(array $r) => OrdemServico::deLinha($r), $linhas), $total];
    }

    /** @return list<OrdemServico> entregas previstas no período (agenda), sem canceladas */
    public function entregasNoPeriodo(string $de, string $ate, ?int $costureiraId): array
    {
        $params = ['de' => $de, 'ate' => $ate];
        $filtro = '';
        if ($costureiraId !== null) {
            $filtro = ' AND o.costureira_id = :costureira';
            $params['costureira'] = $costureiraId;
        }
        $linhas = $this->db->todos(
            self::SELECT . " WHERE o.data_prevista BETWEEN :de AND :ate AND o.status <> 'cancelado'{$filtro}
             ORDER BY o.data_prevista ASC, o.numero ASC",
            $params
        );
        return array_map(static fn(array $r) => OrdemServico::deLinha($r), $linhas);
    }
}
