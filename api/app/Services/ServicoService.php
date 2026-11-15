<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\Pagina;
use App\Dto\Paginacao;
use App\Dto\ServicoDto;
use App\Exceptions\NaoEncontradoException;
use App\Models\Servico;
use App\Repositories\ServicoRepository;

/** Catálogo de tipos de serviço. O valor padrão é só sugestão (decisão 7). */
final class ServicoService
{
    public function __construct(private readonly ServicoRepository $servicos)
    {
    }

    public function listar(Paginacao $p, ?bool $ativo): Pagina
    {
        [$lista, $total] = $this->servicos->listar($p, $ativo);
        return new Pagina(array_map(static fn(Servico $s) => $s->paraArray(), $lista), $total, $p);
    }

    public function obter(int $id): Servico
    {
        return $this->servicos->buscarPorId($id) ?? throw new NaoEncontradoException('Serviço');
    }

    public function criar(ServicoDto $dto): Servico
    {
        return $this->obter($this->servicos->criar($dto->nome, $dto->categoria->value, $dto->valorPadraoCentavos));
    }

    public function atualizar(int $id, ServicoDto $dto): Servico
    {
        $atual = $this->obter($id);
        $this->servicos->atualizar($id, $dto->nome, $dto->categoria->value, $dto->valorPadraoCentavos, $dto->ativo ?? $atual->ativo);
        return $this->obter($id);
    }
}
