# Diagrama de classes (back-end PHP)

Orientação a objetos em camadas: **Controller → Service → Repository → Model**, com **DTO** na fronteira
de entrada (validação) e **Enums** tipados para os domínios fechados.

```mermaid
classDiagram
    direction LR

    class Application { +criar() +executar() }
    class Router { +adicionar() +despachar(Request) Response }
    class Request
    class Response
    class Session { +iniciar() +entrar() +sair() +tokenCsrf() }
    class Database { +todos() +um() +executar() +transacao() }
    class Validator

    class AuthMiddleware { +tratar(Request) }
    class CsrfMiddleware { +tratar(Request) }
    class AdminMiddleware { +tratar(Request) }

    class OrdemServicoController
    class OrdemServicoService {
        +criar(dto, Usuario) OrdemServico
        +atualizar(id, dto) OrdemServico
        +mudarStatus(id, dto) OrdemServico
        +registrarPagamento(id, dto) OrdemServico
        +estornarPagamento(id) OrdemServico
    }
    class OrdemServicoRepository
    class OrdemServicoDto { +deArray() +total() int }
    class ItemOsDto
    class AlterarStatusDto
    class PagamentoDto

    class OrdemServico {
        +atrasada() bool
        +situacaoPagamento() string
        +paraArray()
    }
    class ItemOs
    class Cliente
    class Costureira
    class Servico
    class Agendamento
    class Usuario { +estaBloqueado() bool }

    class StatusOs { <<enum>> Recebido EmAndamento Pronto Entregue Cancelado }
    class Prioridade { <<enum>> Normal Alta Urgente }
    class FormaPagamento { <<enum>> Dinheiro Pix CartaoDebito CartaoCredito Outro }

    Application --> Router
    Router --> AuthMiddleware
    Router --> CsrfMiddleware
    Router --> AdminMiddleware
    AuthMiddleware --> Session
    OrdemServicoController --> OrdemServicoService
    OrdemServicoController ..> OrdemServicoDto : cria/valida
    OrdemServicoService --> OrdemServicoRepository
    OrdemServicoService --> Database : transação
    OrdemServicoRepository --> Database
    OrdemServicoRepository ..> OrdemServico : devolve
    OrdemServicoDto *-- ItemOsDto
    OrdemServicoDto ..> Validator
    OrdemServico *-- ItemOs
    OrdemServico --> StatusOs
    OrdemServico --> Prioridade
    OrdemServico --> FormaPagamento
    OrdemServico "n" --> "1" Cliente
    OrdemServico "n" --> "0..1" Costureira
    ItemOs "n" --> "1" Servico
    Agendamento "n" --> "1" Cliente
    Agendamento "n" --> "0..1" OrdemServico
    OrdemServico "n" --> "1" Usuario : criado_por
```

## Responsabilidade de cada camada

| Camada | Pasta | Faz | Não faz |
|---|---|---|---|
| Core | `app/Core` | Roteador, Request/Response, sessão, CSRF, PDO, validador | Regra de negócio |
| Middleware | `app/Middleware` | Exige sessão (401), perfil admin (403), CSRF (403) | Acessar banco além do usuário logado |
| Controller | `app/Controllers` | Recebe a requisição, monta o DTO, chama o service, devolve JSON | Regra de negócio, SQL |
| DTO | `app/Dto` | Valida e normaliza a entrada (422 por campo) | Acessar banco |
| Service | `app/Services` | Regras RN01–RN18, transações, orquestração | SQL, HTTP |
| Repository | `app/Repositories` | SQL com PDO e *prepared statements* | Regra de negócio |
| Model | `app/Models` | Entidade + comportamento derivado (atrasada, situação de pagamento) | Persistência |
| Enums | `app/Enums` | Domínios fechados e tipados | — |
