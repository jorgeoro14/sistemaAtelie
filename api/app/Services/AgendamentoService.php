<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\AgendamentoDto;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\ValidationException;
use App\Models\Agendamento;
use App\Repositories\AgendamentoRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\OrdemServicoRepository;

/** RN16: compromisso exige cliente, data e hora; choque de horário só avisa. */
final class AgendamentoService
{
    public function __construct(
        private readonly AgendamentoRepository $agendamentos,
        private readonly ClienteRepository $clientes,
        private readonly OrdemServicoRepository $ordens,
    ) {
    }

    public function obter(int $id): Agendamento
    {
        return $this->agendamentos->buscarPorId($id) ?? throw new NaoEncontradoException('Compromisso');
    }

    /** @return array<string, mixed> */
    public function criar(AgendamentoDto $dto): array
    {
        $this->validarReferencias($dto);
        $id = $this->agendamentos->criar($dto);
        return $this->comAvisos($this->obter($id), null);
    }

    /** @return array<string, mixed> */
    public function atualizar(int $id, AgendamentoDto $dto): array
    {
        $this->obter($id);
        $this->validarReferencias($dto);
        $this->agendamentos->atualizar($id, $dto);
        return $this->comAvisos($this->obter($id), $id);
    }

    /** DELETE cancela o compromisso (mantém o histórico). */
    public function cancelar(int $id): void
    {
        $this->obter($id);
        $this->agendamentos->cancelar($id);
    }

    private function validarReferencias(AgendamentoDto $dto): void
    {
        $erros = [];
        if ($this->clientes->buscarPorId($dto->clienteId) === null) {
            $erros['cliente_id'] = 'cliente não encontrado';
        }
        if ($dto->osId !== null) {
            $os = $this->ordens->buscarPorId($dto->osId);
            if ($os === null) {
                $erros['os_id'] = 'ordem de serviço não encontrada';
            } elseif ($os->clienteId !== $dto->clienteId) {
                $erros['os_id'] = 'esta OS não pertence ao cliente escolhido';
            }
        }
        if ($erros !== []) {
            throw new ValidationException($erros);
        }
    }

    /** @return array<string, mixed> */
    private function comAvisos(Agendamento $a, ?int $excetoId): array
    {
        $dados = $a->paraArray();
        if ($a->status->value !== 'cancelado'
            && $this->agendamentos->contarNoMesmoHorario($a->dataHora, $excetoId ?? $a->id) > 0) {
            $dados['avisos'] = [[
                'codigo' => 'horario_ocupado',
                'mensagem' => 'Já existe outro compromisso neste mesmo horário.',
            ]];
        }
        return $dados;
    }
}
