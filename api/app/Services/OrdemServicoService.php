<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Clock;
use App\Core\Database;
use App\Dto\AlterarStatusDto;
use App\Dto\FiltroOsDto;
use App\Dto\OrdemServicoDto;
use App\Dto\Pagina;
use App\Dto\Paginacao;
use App\Dto\PagamentoDto;
use App\Enums\StatusOs;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\RegraNegocioException;
use App\Exceptions\ValidationException;
use App\Models\OrdemServico;
use App\Models\Usuario;
use App\Repositories\ClienteRepository;
use App\Repositories\CostureiraRepository;
use App\Repositories\OrdemServicoRepository;
use App\Repositories\ServicoRepository;

/**
 * Núcleo do sistema: ordens de serviço, status (RN04/RN05) e pagamento único.
 * Todas as regras RN01–RN18 relacionadas à OS são conferidas aqui, no back-end.
 */
final class OrdemServicoService
{
    public function __construct(
        private readonly Database $db,
        private readonly OrdemServicoRepository $ordens,
        private readonly ClienteRepository $clientes,
        private readonly CostureiraRepository $costureiras,
        private readonly ServicoRepository $servicos,
    ) {
    }

    public function listar(FiltroOsDto $filtro, Paginacao $p): Pagina
    {
        [$lista, $total] = $this->ordens->listar($filtro, $p);
        return new Pagina(array_map(static fn(OrdemServico $o) => $o->paraArray(false), $lista), $total, $p);
    }

    public function obter(int $id): OrdemServico
    {
        return $this->ordens->buscarPorId($id) ?? throw new NaoEncontradoException('Ordem de serviço');
    }

    /** RN01/RN02/RN03/RN06/RN15/RN18: grava OS e itens numa transação (tudo ou nada). */
    public function criar(OrdemServicoDto $dto, Usuario $usuario): OrdemServico
    {
        $this->validarReferencias($dto, null);

        $id = $this->db->transacao(function () use ($dto, $usuario): int {
            $numero = $this->ordens->proximoNumero();
            $id = $this->ordens->criar($dto, $numero, $dto->total(), $usuario->id);
            $this->ordens->substituirItens($id, $dto->itens);
            return $id;
        });

        return $this->obter($id);
    }

    /** RN12 (decisão): OS paga não pode ser editada — estorna, edita e paga de novo. */
    public function atualizar(int $id, OrdemServicoDto $dto): OrdemServico
    {
        $os = $this->obter($id);
        if ($os->paga()) {
            throw new RegraNegocioException(
                'os_paga',
                'OS paga não pode ser editada. Estorne o pagamento, edite a OS e registre o pagamento de novo.'
            );
        }
        if ($os->status === StatusOs::Cancelado) {
            throw new RegraNegocioException('os_cancelada', 'OS cancelada não pode ser editada. Reabra a OS primeiro.');
        }
        if (($os->dataPronto !== null && $os->dataPronto < $dto->dataRecebimento)
            || ($os->dataEntrega !== null && $os->dataEntrega < $dto->dataRecebimento)) {
            throw new ValidationException(['data_recebimento' => 'não pode ser posterior às datas de pronto/entrega já registradas']);
        }
        $this->validarReferencias($dto, $os);

        $this->db->transacao(function () use ($id, $dto): void {
            $this->ordens->atualizar($id, $dto, $dto->total());
            $this->ordens->substituirItens($id, $dto->itens);
        });

        return $this->obter($id);
    }

    /** RN04/RN05: transições de status e gravação/limpeza das datas. */
    public function mudarStatus(int $id, AlterarStatusDto $dto): OrdemServico
    {
        $os = $this->obter($id);
        $atual = $os->status;
        $alvo = $dto->status;
        $data = $dto->data ?? Clock::hoje();

        if ($alvo === $atual) {
            throw new RegraNegocioException('status_invalido', 'A OS já está com este status.');
        }

        $dataPronto = $os->dataPronto;
        $dataEntrega = $os->dataEntrega;

        if ($atual === StatusOs::Cancelado) {
            // única saída de uma OS cancelada: reabrir como "recebido"
            if ($alvo !== StatusOs::Recebido) {
                throw new RegraNegocioException('status_invalido', 'Uma OS cancelada só pode ser reaberta (voltar para "recebido").');
            }
        } elseif ($alvo === StatusOs::Cancelado) {
            if ($atual === StatusOs::Entregue) {
                throw new RegraNegocioException('nao_pode_cancelar', 'Uma OS já entregue não pode ser cancelada.');
            }
            if ($os->paga()) {
                throw new RegraNegocioException('os_paga', 'Estorne o pagamento antes de cancelar a OS.');
            }
        } elseif ($alvo->ordem() > $atual->ordem()) {
            // avançando
            if ($alvo === StatusOs::Entregue && $atual !== StatusOs::Pronto) {
                throw new RegraNegocioException('precisa_estar_pronta', 'Só é possível marcar como entregue uma OS que já está pronta.');
            }
            if ($data < $os->dataRecebimento) {
                throw new ValidationException(['data' => 'não pode ser anterior à data de recebimento']);
            }
            if ($alvo === StatusOs::Pronto) {
                $dataPronto = $data;
            } elseif ($alvo === StatusOs::Entregue) {
                if ($dataPronto !== null && $data < $dataPronto) {
                    throw new ValidationException(['data' => 'não pode ser anterior à data em que ficou pronta']);
                }
                $dataEntrega = $data;
            }
        } else {
            // voltando um status: limpa as datas "à frente" (RN04)
            if ($alvo->ordem() < StatusOs::Entregue->ordem()) {
                $dataEntrega = null;
            }
            if ($alvo->ordem() < StatusOs::Pronto->ordem()) {
                $dataPronto = null;
            }
        }

        $this->ordens->atualizarStatus($id, $alvo->value, $dataPronto, $dataEntrega);
        return $this->obter($id);
    }

    /** Decisão 1: pagamento único que quita a OS inteira (valor = total). */
    public function registrarPagamento(int $id, PagamentoDto $dto): OrdemServico
    {
        $os = $this->obter($id);
        if ($os->status === StatusOs::Cancelado) {
            throw new RegraNegocioException('os_cancelada', 'OS cancelada não recebe pagamento.');
        }
        if ($os->paga()) {
            throw new RegraNegocioException('ja_paga', 'Esta OS já está paga. Estorne o pagamento para lançar outro.');
        }
        $this->ordens->registrarPagamento($id, $dto->forma->value, $dto->dataPagamento);
        return $this->obter($id);
    }

    public function estornarPagamento(int $id): OrdemServico
    {
        $os = $this->obter($id);
        if (!$os->paga()) {
            throw new RegraNegocioException('nao_paga', 'Esta OS não tem pagamento para estornar.');
        }
        $this->ordens->estornarPagamento($id);
        return $this->obter($id);
    }

    /**
     * Confere cliente, costureira (RN09) e serviços (RN02) no banco.
     * Cadastros inativos só valem se já faziam parte da própria OS.
     */
    private function validarReferencias(OrdemServicoDto $dto, ?OrdemServico $atual): void
    {
        $erros = [];

        $cliente = $this->clientes->buscarPorId($dto->clienteId);
        if ($cliente === null) {
            $erros['cliente_id'] = 'cliente não encontrado';
        } elseif (!$cliente->ativo && $atual?->clienteId !== $dto->clienteId) {
            $erros['cliente_id'] = 'cliente inativo';
        }

        if ($dto->costureiraId !== null) {
            $costureira = $this->costureiras->buscarPorId($dto->costureiraId);
            if ($costureira === null) {
                $erros['costureira_id'] = 'costureira não encontrada';
            } elseif (!$costureira->ativa && $atual?->costureiraId !== $dto->costureiraId) {
                $erros['costureira_id'] = 'costureira inativa'; // RN09
            }
        }

        $jaUsados = $atual !== null ? $this->ordens->servicosUsados($atual->id) : [];
        foreach ($dto->itens as $i => $item) {
            $servico = $this->servicos->buscarPorId($item->servicoId);
            if ($servico === null) {
                $erros["itens.{$i}.servico_id"] = 'tipo de serviço não encontrado';
            } elseif (!$servico->ativo && !in_array($servico->id, $jaUsados, true)) {
                $erros["itens.{$i}.servico_id"] = 'tipo de serviço inativo';
            }
        }

        if ($erros !== []) {
            throw new ValidationException($erros);
        }
    }
}
