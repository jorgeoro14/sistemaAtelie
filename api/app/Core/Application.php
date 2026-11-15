<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AgendaController;
use App\Controllers\AuthController;
use App\Controllers\ClienteController;
use App\Controllers\CostureiraController;
use App\Controllers\DashboardController;
use App\Controllers\OrdemServicoController;
use App\Controllers\RelatorioController;
use App\Controllers\ServicoController;
use App\Controllers\UsuarioController;
use App\Exceptions\HttpException;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Repositories\AgendamentoRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\CostureiraRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\OrdemServicoRepository;
use App\Repositories\RelatorioRepository;
use App\Repositories\ServicoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AgendaService;
use App\Services\AgendamentoService;
use App\Services\AuthService;
use App\Services\ClienteService;
use App\Services\CostureiraService;
use App\Services\DashboardService;
use App\Services\OrdemServicoService;
use App\Services\RelatorioService;
use App\Services\ServicoService;
use App\Services\UsuarioService;
use Throwable;

/**
 * Compõe as camadas (injeção de dependência manual) e executa a requisição:
 * Controller → Service → Repository → Model, com DTOs na fronteira.
 */
final class Application
{
    private function __construct(private readonly Router $router)
    {
    }

    public static function criar(): self
    {
        Config::carregar();
        date_default_timezone_set((string) Config::obter('app.fuso', 'America/Sao_Paulo'));
        ini_set('display_errors', Config::ehProducao() ? '0' : '1'); // nunca expor erros em produção
        ini_set('log_errors', '1');

        $db = new Database();
        $sessao = new Session();

        // Repositórios
        $usuarios = new UsuarioRepository($db);
        $clientes = new ClienteRepository($db);
        $costureiras = new CostureiraRepository($db);
        $servicos = new ServicoRepository($db);
        $ordens = new OrdemServicoRepository($db);
        $agendamentos = new AgendamentoRepository($db);
        $dashboardRepo = new DashboardRepository($db);
        $relatorioRepo = new RelatorioRepository($db);

        // Serviços
        $agendaSvc = new AgendaService($ordens, $agendamentos);
        $authSvc = new AuthService($usuarios, $sessao);

        // Controllers
        $auth = new AuthController($authSvc);
        $usuariosCtl = new UsuarioController(new UsuarioService($usuarios));
        $clientesCtl = new ClienteController(new ClienteService($clientes, $ordens));
        $costureirasCtl = new CostureiraController(new CostureiraService($costureiras));
        $servicosCtl = new ServicoController(new ServicoService($servicos));
        $osCtl = new OrdemServicoController(new OrdemServicoService($db, $ordens, $clientes, $costureiras, $servicos));
        $agendaCtl = new AgendaController($agendaSvc, new AgendamentoService($agendamentos, $clientes, $ordens));
        $dashCtl = new DashboardController(new DashboardService($dashboardRepo, $ordens, $agendaSvc));
        $relCtl = new RelatorioController(new RelatorioService($relatorioRepo));

        $router = new Router(
            new AuthMiddleware($sessao, $usuarios),
            new CsrfMiddleware($sessao),
            new AdminMiddleware(),
        );

        $app = new self($router);
        $app->sessao = $sessao;
        $app->rotas(compact(
            'auth', 'usuariosCtl', 'clientesCtl', 'costureirasCtl', 'servicosCtl',
            'osCtl', 'agendaCtl', 'dashCtl', 'relCtl'
        ));
        return $app;
    }

    private ?Session $sessao = null;

    /** @param array<string, object> $c */
    private function rotas(array $c): void
    {
        $r = $this->router;
        $pub = ['publica' => true];
        $adm = ['admin' => true];

        // Autenticação
        $r->adicionar('POST', '/login', $c['auth']->login(...), $pub);
        $r->adicionar('POST', '/logout', $c['auth']->logout(...));
        $r->adicionar('GET', '/me', $c['auth']->me(...));
        $r->adicionar('POST', '/me/senha', $c['auth']->trocarSenha(...));

        // Dashboard
        $r->adicionar('GET', '/dashboard', $c['dashCtl']->resumo(...));

        // Clientes
        $r->adicionar('GET', '/clientes', $c['clientesCtl']->listar(...));
        $r->adicionar('POST', '/clientes', $c['clientesCtl']->criar(...));
        $r->adicionar('GET', '/clientes/{id}', $c['clientesCtl']->detalhe(...));
        $r->adicionar('PUT', '/clientes/{id}', $c['clientesCtl']->atualizar(...));
        $r->adicionar('DELETE', '/clientes/{id}', $c['clientesCtl']->excluir(...));

        // Costureiras
        $r->adicionar('GET', '/costureiras', $c['costureirasCtl']->listar(...));
        $r->adicionar('POST', '/costureiras', $c['costureirasCtl']->criar(...));
        $r->adicionar('PUT', '/costureiras/{id}', $c['costureirasCtl']->atualizar(...));

        // Catálogo de serviços
        $r->adicionar('GET', '/servicos', $c['servicosCtl']->listar(...));
        $r->adicionar('POST', '/servicos', $c['servicosCtl']->criar(...));
        $r->adicionar('PUT', '/servicos/{id}', $c['servicosCtl']->atualizar(...));

        // Ordens de serviço
        $r->adicionar('GET', '/os', $c['osCtl']->listar(...));
        $r->adicionar('POST', '/os', $c['osCtl']->criar(...));
        $r->adicionar('GET', '/os/{id}', $c['osCtl']->detalhe(...));
        $r->adicionar('PUT', '/os/{id}', $c['osCtl']->atualizar(...));
        $r->adicionar('PATCH', '/os/{id}/status', $c['osCtl']->mudarStatus(...));
        $r->adicionar('POST', '/os/{id}/pagamento', $c['osCtl']->registrarPagamento(...));
        $r->adicionar('DELETE', '/os/{id}/pagamento', $c['osCtl']->estornarPagamento(...));

        // Agenda e compromissos
        $r->adicionar('GET', '/agenda', $c['agendaCtl']->agenda(...));
        $r->adicionar('POST', '/agendamentos', $c['agendaCtl']->criar(...));
        $r->adicionar('GET', '/agendamentos/{id}', $c['agendaCtl']->detalhe(...));
        $r->adicionar('PUT', '/agendamentos/{id}', $c['agendaCtl']->atualizar(...));
        $r->adicionar('DELETE', '/agendamentos/{id}', $c['agendaCtl']->cancelar(...));

        // Usuários (só admin)
        $r->adicionar('GET', '/usuarios', $c['usuariosCtl']->listar(...), $adm);
        $r->adicionar('POST', '/usuarios', $c['usuariosCtl']->criar(...), $adm);
        $r->adicionar('PUT', '/usuarios/{id}', $c['usuariosCtl']->atualizar(...), $adm);
        $r->adicionar('POST', '/usuarios/{id}/redefinir-senha', $c['usuariosCtl']->redefinirSenha(...), $adm);

        // Relatórios (T14, admin)
        $r->adicionar('GET', '/relatorios/resumo', $c['relCtl']->resumo(...), $adm);
        $r->adicionar('GET', '/relatorios/costureiras', $c['relCtl']->costureiras(...), $adm);
        $r->adicionar('GET', '/relatorios/clientes', $c['relCtl']->clientes(...), $adm);
        $r->adicionar('GET', '/relatorios/faturamento-mensal', $c['relCtl']->faturamentoMensal(...), $adm);
    }

    public function executar(): void
    {
        try {
            $this->sessao?->iniciar();
            $resposta = $this->router->despachar(Request::capturar());
        } catch (HttpException $e) {
            $resposta = Response::json($e->paraArray(), $e->status);
        } catch (Throwable $e) {
            // Detalhes só no log; a resposta nunca expõe SQL, caminhos ou stack trace
            Logger::erro(get_class($e) . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
            $resposta = Response::json([
                'erro' => 'erro_interno',
                'mensagem' => 'Algo deu errado do nosso lado. Tente novamente em instantes.',
            ], 500);
        }
        $resposta->enviar();
    }
}
