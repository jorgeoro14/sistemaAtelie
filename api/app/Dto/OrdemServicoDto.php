<?php

declare(strict_types=1);

namespace App\Dto;

use App\Core\Validator;
use App\Enums\Prioridade;

/** Corpo de POST/PUT /api/os. O total NUNCA vem do cliente (RN03). */
final class OrdemServicoDto
{
    /** @param list<ItemOsDto> $itens */
    private function __construct(
        public readonly int $clienteId,
        public readonly ?int $costureiraId,
        public readonly Prioridade $prioridade,
        public readonly string $dataRecebimento,
        public readonly string $dataPrevista,
        public readonly ?string $observacoes,
        public readonly array $itens,
    ) {
    }

    /** @param array<string, mixed> $d */
    public static function deArray(array $d, string $hoje): self
    {
        $v = new Validator($d);
        $cliente = $v->inteiro('cliente_id', true, 1);
        $costureira = $v->inteiro('costureira_id', false, 1);

        // aceita boolean (contrato original) ou enum de 3 níveis (decisão 2)
        if (isset($d['prioridade']) && is_bool($d['prioridade'])) {
            $d['prioridade'] = $d['prioridade'] ? 'alta' : 'normal';
        }
        $v2 = new Validator($d);
        $prioridade = $v2->opcao('prioridade', Prioridade::class, false, Prioridade::Normal);
        $v->mesclar($v2->erros());

        $recebimento = $v->tem('data_recebimento') && $d['data_recebimento'] !== null && $d['data_recebimento'] !== ''
            ? $v->data('data_recebimento')
            : $hoje; // RN06: padrão hoje
        $prevista = $v->data('data_prevista');
        if ($recebimento !== null && $prevista !== null && $prevista < $recebimento) {
            $v->erro('data_prevista', 'não pode ser anterior à data de recebimento');
        }
        $obs = $v->texto('observacoes', false, 0, 2000);

        // RN01: pelo menos um serviço
        $itens = [];
        $brutos = $d['itens'] ?? null;
        if (!is_array($brutos) || $brutos === [] || !array_is_list($brutos)) {
            $v->erro('itens', 'informe pelo menos um serviço');
        } elseif (count($brutos) > 50) {
            $v->erro('itens', 'máximo de 50 serviços por OS');
        } else {
            foreach ($brutos as $i => $bruto) {
                if (!is_array($bruto)) {
                    $v->erro("itens.{$i}", 'item inválido');
                    continue;
                }
                [$item, $erros] = ItemOsDto::validar($bruto, $i);
                if ($item !== null) {
                    $itens[] = $item;
                }
                $v->mesclar($erros);
            }
        }
        $v->lancarSeInvalido();

        return new self((int) $cliente, $costureira, $prioridade, (string) $recebimento, (string) $prevista, $obs, $itens);
    }

    /** RN03: total = soma dos valores dos serviços. */
    public function total(): int
    {
        return array_sum(array_map(static fn(ItemOsDto $i) => $i->valorCentavos, $this->itens));
    }
}
