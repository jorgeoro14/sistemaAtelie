<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Clock;
use App\Enums\FormaPagamento;
use App\Enums\Prioridade;
use App\Enums\StatusOs;

/**
 * Ordem de Serviço (a "comanda" do cliente).
 * Atrasada (RN07) e situação do pagamento (RN11) são derivadas, nunca gravadas.
 */
final class OrdemServico
{
    /** @param list<ItemOs> $itens */
    public function __construct(
        public readonly int $id,
        public readonly int $numero,
        public readonly int $clienteId,
        public readonly ?int $costureiraId,
        public readonly Prioridade $prioridade,
        public readonly StatusOs $status,
        public readonly string $dataRecebimento,
        public readonly string $dataPrevista,
        public readonly ?string $dataPronto,
        public readonly ?string $dataEntrega,
        public readonly ?string $observacoes,
        public readonly int $totalCentavos,
        public readonly ?FormaPagamento $formaPagamento,
        public readonly ?string $dataPagamento,
        public readonly int $criadoPor,
        public readonly string $criadoEm,
        public readonly string $atualizadoEm,
        public readonly ?string $clienteNome = null,
        public readonly ?string $clienteTelefone = null,
        public readonly ?string $costureiraNome = null,
        public readonly ?string $resumoServicos = null,
        public readonly array $itens = [],
        public readonly ?string $criadoPorNome = null,
    ) {
    }

    /**
     * @param array<string, mixed> $r
     * @param list<ItemOs> $itens
     */
    public static function deLinha(array $r, array $itens = []): self
    {
        return new self(
            (int) $r['id'],
            (int) $r['numero'],
            (int) $r['cliente_id'],
            $r['costureira_id'] !== null ? (int) $r['costureira_id'] : null,
            Prioridade::from((string) $r['prioridade']),
            StatusOs::from((string) $r['status']),
            (string) $r['data_recebimento'],
            (string) $r['data_prevista'],
            $r['data_pronto'] !== null ? (string) $r['data_pronto'] : null,
            $r['data_entrega'] !== null ? (string) $r['data_entrega'] : null,
            $r['observacoes'] !== null ? (string) $r['observacoes'] : null,
            (int) $r['total_centavos'],
            $r['forma_pagamento'] !== null ? FormaPagamento::from((string) $r['forma_pagamento']) : null,
            $r['data_pagamento'] !== null ? (string) $r['data_pagamento'] : null,
            (int) $r['criado_por'],
            (string) $r['criado_em'],
            (string) $r['atualizado_em'],
            isset($r['cliente_nome']) ? (string) $r['cliente_nome'] : null,
            isset($r['cliente_telefone']) ? (string) $r['cliente_telefone'] : null,
            isset($r['costureira_nome']) ? (string) $r['costureira_nome'] : null,
            isset($r['resumo_servicos']) ? (string) $r['resumo_servicos'] : null,
            $itens,
            isset($r['criado_por_nome']) ? (string) $r['criado_por_nome'] : null,
        );
    }

    /** RN07: não entregue/cancelada e com data prevista anterior a hoje. */
    public function atrasada(): bool
    {
        return $this->status->aberta() && $this->dataPrevista < Clock::hoje();
    }

    public function paga(): bool
    {
        return $this->dataPagamento !== null;
    }

    /** RN11 (decisão 1): só "pendente" ou "pago". */
    public function situacaoPagamento(): string
    {
        return $this->paga() ? 'pago' : 'pendente';
    }

    /** @return array<string, mixed> */
    public function paraArray(bool $comItens = true): array
    {
        $dados = [
            'id' => $this->id,
            'numero' => $this->numero,
            'cliente_id' => $this->clienteId,
            'cliente' => $this->clienteNome === null ? null : [
                'id' => $this->clienteId,
                'nome' => $this->clienteNome,
                'telefone' => $this->clienteTelefone,
            ],
            'costureira_id' => $this->costureiraId,
            'costureira' => $this->costureiraId === null ? null : [
                'id' => $this->costureiraId,
                'nome' => $this->costureiraNome,
            ],
            'prioridade' => $this->prioridade->value,
            'status' => $this->status->value,
            'data_recebimento' => $this->dataRecebimento,
            'data_prevista' => $this->dataPrevista,
            'data_pronto' => $this->dataPronto,
            'data_entrega' => $this->dataEntrega,
            'observacoes' => $this->observacoes,
            'total_centavos' => $this->totalCentavos,
            'forma_pagamento' => $this->formaPagamento?->value,
            'data_pagamento' => $this->dataPagamento,
            'situacao_pagamento' => $this->situacaoPagamento(),
            'paga' => $this->paga(),
            'atrasada' => $this->atrasada(),
            'resumo_servicos' => $this->resumoServicos,
            'criado_por' => $this->criadoPor,
            'criado_por_nome' => $this->criadoPorNome,
            'criado_em' => $this->criadoEm,
            'atualizado_em' => $this->atualizadoEm,
        ];
        if ($comItens) {
            $dados['itens'] = array_map(static fn(ItemOs $i) => $i->paraArray(), $this->itens);
        }
        return $dados;
    }
}
