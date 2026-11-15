<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\ClienteDto;
use App\Dto\Pagina;
use App\Dto\Paginacao;
use App\Exceptions\NaoEncontradoException;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Repositories\ClienteRepository;
use App\Repositories\OrdemServicoRepository;

final class ClienteService
{
    public function __construct(
        private readonly ClienteRepository $clientes,
        private readonly OrdemServicoRepository $ordens,
    ) {
    }

    public function listar(Paginacao $p, ?bool $ativo): Pagina
    {
        [$lista, $total] = $this->clientes->listar($p, $ativo);
        return new Pagina(array_map(static fn(Cliente $c) => $c->paraArray(), $lista), $total, $p);
    }

    /** Detalhe com histórico de OS e total já gasto. @return array<string, mixed> */
    public function detalhe(int $id): array
    {
        $c = $this->obter($id);
        $ordens = $this->ordens->listarPorCliente($id);
        return $c->paraArray() + [
            'total_os' => count($ordens),
            'total_gasto_centavos' => $this->clientes->totalGastoCentavos($id),
            'ordens' => array_map(static fn(OrdemServico $o) => $o->paraArray(false), $ordens),
        ];
    }

    public function obter(int $id): Cliente
    {
        return $this->clientes->buscarPorId($id) ?? throw new NaoEncontradoException('Cliente');
    }

    /** RN14: telefone repetido gera AVISO, não bloqueio. @return array<string, mixed> */
    public function criar(ClienteDto $dto): array
    {
        $id = $this->clientes->criar($dto->nome, $dto->telefone, $dto->observacoes);
        return $this->comAvisos($this->obter($id), $dto->telefone, $id);
    }

    /** @return array<string, mixed> */
    public function atualizar(int $id, ClienteDto $dto): array
    {
        $atual = $this->obter($id);
        $this->clientes->atualizar($id, $dto->nome, $dto->telefone, $dto->observacoes, $dto->ativo ?? $atual->ativo);
        return $this->comAvisos($this->obter($id), $dto->telefone, $id);
    }

    /** RN13: com histórico só inativa; sem histórico exclui. @return 'excluido'|'inativado' */
    public function excluir(int $id): string
    {
        $this->obter($id);
        if ($this->clientes->possuiHistorico($id)) {
            $this->clientes->inativar($id);
            return 'inativado';
        }
        $this->clientes->excluir($id);
        return 'excluido';
    }

    /** @return array<string, mixed> */
    private function comAvisos(Cliente $c, string $telefone, int $id): array
    {
        $dados = $c->paraArray();
        if ($this->clientes->existeTelefone($telefone, $id)) {
            $dados['avisos'] = [[
                'codigo' => 'telefone_duplicado',
                'mensagem' => 'Já existe outro cliente cadastrado com este telefone. Confira se não é duplicidade.',
            ]];
        }
        return $dados;
    }
}
