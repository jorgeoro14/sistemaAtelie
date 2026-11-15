<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\Pagina;
use App\Dto\Paginacao;
use App\Dto\RedefinirSenhaDto;
use App\Dto\UsuarioDto;
use App\Exceptions\NaoEncontradoException;
use App\Exceptions\RegraNegocioException;
use App\Exceptions\ValidationException;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;

/** RN17: só o administrador gerencia usuários; nunca inativa a si mesmo nem o último admin. */
final class UsuarioService
{
    public function __construct(private readonly UsuarioRepository $usuarios)
    {
    }

    public function listar(Paginacao $p): Pagina
    {
        [$lista, $total] = $this->usuarios->listar($p);
        return new Pagina(array_map(static fn(Usuario $u) => $u->paraArray(), $lista), $total, $p);
    }

    public function obter(int $id): Usuario
    {
        return $this->usuarios->buscarPorId($id) ?? throw new NaoEncontradoException('Usuário');
    }

    public function criar(UsuarioDto $dto): Usuario
    {
        if ($this->usuarios->existeUsuario($dto->usuario)) {
            throw new ValidationException(['usuario' => 'já está em uso']);
        }
        $id = $this->usuarios->criar(
            $dto->nome,
            $dto->usuario,
            password_hash((string) $dto->senha, PASSWORD_DEFAULT),
            $dto->perfil->value,
            $dto->ativo ?? true
        );
        return $this->obter($id);
    }

    public function atualizar(Usuario $executor, int $id, UsuarioDto $dto): Usuario
    {
        $alvo = $this->obter($id);
        if ($this->usuarios->existeUsuario($dto->usuario, $id)) {
            throw new ValidationException(['usuario' => 'já está em uso']);
        }
        $ativo = $dto->ativo ?? $alvo->ativo;

        if ($alvo->ativo && !$ativo) {
            if ($alvo->id === $executor->id) {
                throw new RegraNegocioException('nao_pode_inativar_a_si', 'Você não pode desativar o seu próprio usuário.');
            }
            if ($this->usuarios->contarAdminsAtivos() <= 1) {
                throw new RegraNegocioException('ultimo_admin', 'Não é possível desativar o último administrador ativo.');
            }
        }

        $this->usuarios->atualizar($id, $dto->nome, $dto->usuario, $dto->perfil->value, $ativo);
        return $this->obter($id);
    }

    /** O usuário redefinido é obrigado a trocar a senha no próximo login. */
    public function redefinirSenha(int $id, RedefinirSenhaDto $dto): void
    {
        $this->obter($id);
        $this->usuarios->atualizarSenha($id, password_hash($dto->novaSenha, PASSWORD_DEFAULT), true);
    }
}
