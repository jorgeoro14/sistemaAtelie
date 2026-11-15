<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Clock;
use App\Core\Config;
use App\Core\Session;
use App\Dto\LoginDto;
use App\Dto\TrocarSenhaDto;
use App\Exceptions\NaoAutenticadoException;
use App\Exceptions\ProibidoException;
use App\Exceptions\ValidationException;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;

/** Login com bloqueio por tentativas, logout e troca da própria senha. */
final class AuthService
{
    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly Session $sessao,
    ) {
    }

    public function entrar(LoginDto $dto): Usuario
    {
        $maxTentativas = (int) Config::obter('seguranca.max_tentativas_login', 5);
        $bloqueioMin = (int) Config::obter('seguranca.bloqueio_minutos', 15);
        $agora = Clock::agora();

        $usuario = $this->usuarios->buscarPorUsuario($dto->usuario);

        if ($usuario === null || !$usuario->ativo) {
            // gasta o mesmo tempo de uma verificação real (dificulta descobrir quais usuários existem)
            password_verify($dto->senha, password_hash('tempo-constante', PASSWORD_DEFAULT));
            throw new NaoAutenticadoException('Usuário ou senha incorretos.', 'credenciais_invalidas');
        }

        if ($usuario->estaBloqueado($agora)) {
            throw new ProibidoException(
                "Usuário bloqueado por excesso de tentativas. Tente novamente em {$bloqueioMin} minutos.",
                'usuario_bloqueado'
            );
        }

        if (!password_verify($dto->senha, $usuario->senhaHash)) {
            $tentativas = $usuario->tentativasLogin + 1;
            if ($tentativas >= $maxTentativas) {
                $this->usuarios->registrarFalhaLogin($usuario->id, 0, Clock::agoraMais($bloqueioMin));
                throw new ProibidoException(
                    "Muitas tentativas erradas. Usuário bloqueado por {$bloqueioMin} minutos.",
                    'usuario_bloqueado'
                );
            }
            $this->usuarios->registrarFalhaLogin($usuario->id, $tentativas, null);
            throw new NaoAutenticadoException('Usuário ou senha incorretos.', 'credenciais_invalidas');
        }

        if (password_needs_rehash($usuario->senhaHash, PASSWORD_DEFAULT)) {
            $this->usuarios->atualizarSenha($usuario->id, password_hash($dto->senha, PASSWORD_DEFAULT), $usuario->trocarSenha);
        }
        $this->usuarios->zerarTentativas($usuario->id);
        $this->sessao->entrar($usuario->id);
        return $usuario;
    }

    public function sair(): void
    {
        $this->sessao->sair();
    }

    /** @return array<string, mixed> payload de /api/me */
    public function perfilDaSessao(Usuario $usuario): array
    {
        return [
            'usuario' => [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
                'usuario' => $usuario->usuario,
                'perfil' => $usuario->perfil->value,
            ],
            'trocar_senha' => $usuario->trocarSenha,
            'csrf_token' => $this->sessao->tokenCsrf(),
        ];
    }

    public function trocarSenha(Usuario $usuario, TrocarSenhaDto $dto): void
    {
        if (!password_verify($dto->senhaAtual, $usuario->senhaHash)) {
            throw new ValidationException(['senha_atual' => 'senha atual incorreta']);
        }
        $this->usuarios->atualizarSenha($usuario->id, password_hash($dto->novaSenha, PASSWORD_DEFAULT), false);
    }
}
