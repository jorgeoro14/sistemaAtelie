<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Dto\AgendamentoDto;
use App\Models\Agendamento;

final class AgendamentoRepository
{
    private const SELECT = 'SELECT a.*, c.nome AS cliente_nome, o.numero AS os_numero
        FROM agendamentos a
        JOIN clientes c ON c.id = a.cliente_id
        LEFT JOIN ordens_servico o ON o.id = a.os_id';

    public function __construct(private readonly Database $db)
    {
    }

    public function buscarPorId(int $id): ?Agendamento
    {
        $r = $this->db->um(self::SELECT . ' WHERE a.id = :id', ['id' => $id]);
        return $r ? Agendamento::deLinha($r) : null;
    }

    public function criar(AgendamentoDto $dto): int
    {
        $this->db->executar(
            'INSERT INTO agendamentos (cliente_id, os_id, data_hora, tipo, observacao, status)
             VALUES (:c, :o, :dh, :t, :obs, :s)',
            [
                'c' => $dto->clienteId, 'o' => $dto->osId, 'dh' => $dto->dataHora,
                't' => $dto->tipo->value, 'obs' => $dto->observacao, 's' => $dto->status->value,
            ]
        );
        return $this->db->ultimoId();
    }

    public function atualizar(int $id, AgendamentoDto $dto): void
    {
        $this->db->executar(
            'UPDATE agendamentos SET cliente_id = :c, os_id = :o, data_hora = :dh, tipo = :t,
                observacao = :obs, status = :s WHERE id = :id',
            [
                'c' => $dto->clienteId, 'o' => $dto->osId, 'dh' => $dto->dataHora,
                't' => $dto->tipo->value, 'obs' => $dto->observacao, 's' => $dto->status->value, 'id' => $id,
            ]
        );
    }

    public function cancelar(int $id): void
    {
        $this->db->executar("UPDATE agendamentos SET status = 'cancelado' WHERE id = :id", ['id' => $id]);
    }

    /** RN16: outros compromissos ativos no mesmo horário (aviso, não bloqueio). */
    public function contarNoMesmoHorario(string $dataHora, ?int $excetoId): int
    {
        return (int) $this->db->escalar(
            "SELECT COUNT(*) FROM agendamentos WHERE data_hora = :dh AND status <> 'cancelado' AND id <> :id",
            ['dh' => $dataHora, 'id' => $excetoId ?? 0]
        );
    }

    /** @return list<Agendamento> compromissos do período (sem cancelados); filtro opcional por costureira da OS */
    public function noPeriodo(string $de, string $ate, ?int $costureiraId): array
    {
        $params = ['de' => $de . ' 00:00:00', 'ate' => $ate . ' 23:59:59'];
        $filtro = '';
        if ($costureiraId !== null) {
            $filtro = ' AND o.costureira_id = :costureira';
            $params['costureira'] = $costureiraId;
        }
        $linhas = $this->db->todos(
            self::SELECT . " WHERE a.data_hora BETWEEN :de AND :ate AND a.status <> 'cancelado'{$filtro}
             ORDER BY a.data_hora ASC",
            $params
        );
        return array_map(Agendamento::deLinha(...), $linhas);
    }
}
