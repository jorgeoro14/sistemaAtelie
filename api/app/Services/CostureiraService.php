<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\CostureiraDto;
use App\Dto\Pagina;
use App\Dto\Paginacao;
use App\Exceptions\NaoEncontradoException;
use App\Models\Costureira;
use App\Repositories\CostureiraRepository;

final class CostureiraService
{
    public function __construct(private readonly CostureiraRepository $costureiras)
    {
    }

    public function listar(Paginacao $p, ?bool $ativa): Pagina
    {
        [$lista, $total] = $this->costureiras->listar($p, $ativa);
        return new Pagina(array_map(static fn(Costureira $c) => $c->paraArray(), $lista), $total, $p);
    }

    public function obter(int $id): Costureira
    {
        return $this->costureiras->buscarPorId($id) ?? throw new NaoEncontradoException('Costureira');
    }

    public function criar(CostureiraDto $dto): Costureira
    {
        return $this->obter($this->costureiras->criar($dto->nome, $dto->telefone));
    }

    /** RN09/RN13: costureira nunca é apagada; "inativar" é ativa = false. */
    public function atualizar(int $id, CostureiraDto $dto): Costureira
    {
        $atual = $this->obter($id);
        $this->costureiras->atualizar($id, $dto->nome, $dto->telefone, $dto->ativa ?? $atual->ativa);
        return $this->obter($id);
    }
}
