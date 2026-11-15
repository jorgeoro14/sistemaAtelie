<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

/**
 * Roteador simples. Cada rota define opções:
 *  - publica: não exige sessão (apenas POST /login)
 *  - admin:   exige perfil administrador
 * Mutações (POST/PUT/PATCH/DELETE) exigem CSRF, salvo rota publica.
 */
final class Router
{
    /** @var list<array{metodo:string, regex:string, handler:callable, opcoes:array<string,bool>}> */
    private array $rotas = [];

    public function __construct(
        private readonly AuthMiddleware $auth,
        private readonly CsrfMiddleware $csrf,
        private readonly AdminMiddleware $admin,
    ) {
    }

    /** @param array<string, bool> $opcoes */
    public function adicionar(string $metodo, string $padrao, callable $handler, array $opcoes = []): void
    {
        // {id}/{pid} aceitam somente dígitos
        $regex = preg_replace('#\{[a-z_]+\}#', '([0-9]+)', $padrao);
        $this->rotas[] = [
            'metodo' => $metodo,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'opcoes' => $opcoes,
        ];
    }

    public function despachar(Request $req): Response
    {
        $caminhoCasou = false;
        foreach ($this->rotas as $rota) {
            if (!preg_match($rota['regex'], $req->caminho, $m)) {
                continue;
            }
            $caminhoCasou = true;
            if ($rota['metodo'] !== $req->metodo) {
                continue;
            }
            array_shift($m);
            $params = array_map('intval', $m);

            if (empty($rota['opcoes']['publica'])) {
                $this->auth->tratar($req);
                if (!empty($rota['opcoes']['admin'])) {
                    $this->admin->tratar($req);
                }
                if ($req->metodo !== 'GET') {
                    $this->csrf->tratar($req);
                }
            }
            return ($rota['handler'])($req, ...$params);
        }

        if ($caminhoCasou) {
            throw new HttpException(405, 'metodo_nao_permitido', 'Método não permitido para esta rota.');
        }
        throw new HttpException(404, 'rota_nao_encontrada', 'Recurso não encontrado.');
    }
}
