<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sessão PHP segura: cookie HttpOnly + Secure + SameSite=Lax,
 * expiração por inatividade (8h) e destruição no servidor no logout.
 */
final class Session
{
    private bool $iniciada = false;

    public function iniciar(): void
    {
        if ($this->iniciada) {
            return;
        }
        $duracao = (int) Config::obter('sessao.duracao_segundos', 8 * 3600);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) $duracao);

        session_name((string) Config::obter('sessao.nome', 'ATELIESESSID'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => (string) Config::obter('sessao.cookie_path', '/'),
            'secure' => $this->cookieSeguro(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        $this->iniciada = true;

        $agora = Clock::timestamp();
        $ultimo = $_SESSION['_ultimo_acesso'] ?? $agora;
        if ($agora - (int) $ultimo > $duracao) {
            $this->destruir();
            session_start();
        }
        $_SESSION['_ultimo_acesso'] = $agora;
    }

    private function cookieSeguro(): bool
    {
        $cfg = Config::obter('sessao.cookie_secure', 'auto');
        if ($cfg === 'auto') {
            return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        }
        return (bool) $cfg;
    }

    public function usuarioId(): ?int
    {
        return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    }

    public function entrar(int $usuarioId): void
    {
        session_regenerate_id(true); // evita fixação de sessão
        $_SESSION['usuario_id'] = $usuarioId;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    public function sair(): void
    {
        $this->destruir();
    }

    public function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf'];
    }

    public function csrfValido(?string $token): bool
    {
        return $token !== null && $token !== '' && hash_equals($this->tokenCsrf(), $token);
    }

    private function destruir(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $p['path'],
                'secure' => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?: 'Lax',
            ]);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $this->iniciada = false;
    }
}
