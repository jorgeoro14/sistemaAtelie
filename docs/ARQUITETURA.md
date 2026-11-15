# Arquitetura

Visão geral das decisões técnicas do Bella Costura Gestão. Os diagramas detalhados estão em
[`CLASSES.md`](CLASSES.md) (classes) e [`ER.md`](ER.md) (banco); o contrato HTTP está em [`API.md`](API.md).

## Visão geral

```
Navegador (React, SPA)
      │  fetch /api/...  (JSON, cookie de sessão, X-CSRF-Token)
      ▼
Apache (.htaccess) ──► api/index.php  (ponto de entrada único)
                            │
                            ▼
                      Application  (monta as dependências e as rotas)
                            │
        Router ─► Middleware ─► Controller ─► Service ─► Repository ─► MySQL
                                   │             │
                                  DTO       Model / Enum / Exception
```

O front é uma aplicação de página única, compilada em arquivos estáticos. A API é independente e só fala JSON. Em
produção ambos ficam no mesmo domínio (`/atelie` e `/atelie/api`), o que permite usar cookie de sessão sem CORS.

## Ciclo de uma requisição

1. O Apache entrega `/…/api/*` para `api/index.php`; qualquer outro caminho cai no `index.html` do React.
2. `Request::capturar()` normaliza método, caminho (remove o prefixo até `/api`, então funciona na raiz ou em
   subpasta), consulta, cabeçalhos e corpo JSON.
3. `Router` encontra a rota. Rotas podem ser públicas ou exigir administrador.
4. Os **middlewares** rodam na ordem: `AuthMiddleware` (401), `AdminMiddleware` (403), `CsrfMiddleware` (403 em
   `POST/PUT/PATCH/DELETE`, exceto o login).
5. O **Controller** monta um **DTO** a partir da requisição. O DTO valida e normaliza; se houver erro, lança
   `ValidationException` (422 com `campos`).
6. O **Service** aplica as regras de negócio, abre transação quando preciso e usa os **Repositories**.
7. O **Repository** é o único lugar com SQL e devolve **Models**.
8. `Response` serializa JSON; exceções viram respostas padronizadas (`{erro, mensagem, campos}`) e qualquer erro
   inesperado vira um 500 genérico, com o detalhe gravado só no log.

## Camadas e responsabilidades

| Camada | Pasta | Regra de ouro |
|---|---|---|
| Controller | `api/app/Controllers` | Não tem regra de negócio nem SQL |
| DTO | `api/app/Dto` | Toda entrada externa passa por aqui |
| Service | `api/app/Services` | Concentra regras (RN01–RN18) e transações |
| Repository | `api/app/Repositories` | Só aqui existe SQL, sempre com *prepared statements* |
| Model / Enum | `api/app/Models`, `api/app/Enums` | Entidades e valores tipados; sem acesso ao banco |
| Middleware | `api/app/Middleware` | Preocupações transversais de segurança |
| Core | `api/app/Core` | Infraestrutura (roteador, sessão, banco, config, validação) |

As dependências são injetadas manualmente em `Application`, sem contêiner. O autoloader PSR-4 é escrito à mão
(`api/app/bootstrap.php`), o que dispensa Composer e simplifica a publicação em hospedagem compartilhada.

## Decisões de projeto

| Decisão | Motivo |
|---|---|
| Dinheiro em **centavos inteiros** | Evita erros de ponto flutuante nos totais |
| **Total da OS calculado no servidor** (RN03) | O cliente nunca define o valor total |
| **Valor do item congelado** na OS | Mudar o preço no catálogo não altera OS antigas |
| **Número da OS sequencial** e nunca reaproveitado (RN15) | Rastreabilidade; cancelar não libera o número |
| **Pagamento único** na própria OS | Decisão da proprietária: sem sinal nem parcelas, uma tabela a menos |
| **OS paga não é editada** | Para corrigir: estornar, editar e pagar de novo; evita divergência de caixa |
| **Entregue só depois de pronto** (RN05) | Impede pular etapas do fluxo |
| Voltar um status **limpa as datas seguintes** (RN04) | Mantém o histórico coerente |
| Exclusão x inativação de cliente | Com histórico de OS, o cliente é inativado em vez de apagado |
| `ON DELETE RESTRICT` (CASCADE só nos itens da OS) | O banco impede apagar dados com histórico |
| Prioridade em 3 níveis | normal, alta e urgente; um booleano antigo ainda é aceito |
| Sem framework e sem Composer | Menos superfície de ataque e implantação simples em hospedagem compartilhada |

### Estados da ordem de serviço

```
recebido ──► em_andamento ──► pronto ──► entregue
   ▲  │            │             │
   │  └────────────┴─────────────┴──► cancelado ──► (reabrir) recebido
   └──────────── voltar um status (limpa as datas à frente)
```

* `entregue` só a partir de `pronto`; não se cancela uma OS entregue ou paga (estornar antes).
* Pagamento: `POST /os/{id}/pagamento` (forma + data); "pago" significa `data_pagamento` preenchida.

## Segurança em camadas

| Ameaça | Mitigação |
|---|---|
| Acesso sem login | `AuthMiddleware` em todas as rotas, exceto o login (401) |
| Escalada de perfil | `AdminMiddleware` nas rotas de usuários e relatórios (403) |
| CSRF | Token por sessão no cabeçalho `X-CSRF-Token`; cookie `SameSite=Lax` |
| SQL injection | *Prepared statements* nativos, sem emulação, com tipos explícitos nos `LIMIT`/`OFFSET` |
| Força bruta | Bloqueio de 15 min após 5 tentativas; hash fictício para usuário inexistente (tempo constante) |
| Fixação/roubo de sessão | `session.use_strict_mode`, ID regenerado no login, cookie `HttpOnly` + `Secure`, expiração por inatividade |
| XSS | React escapa a saída; nenhum uso de HTML cru no front |
| Vazamento de segredos | `config.php` fora do Git e, em produção, fora do `public_html`; `.htaccess` nega acesso ao código |
| Vazamento de erros | `display_errors` desligado; erro 500 genérico; log em arquivo fora do site |
| Cabeçalhos | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Cache-Control: no-store` na API |

## Front-end

* **React Router** com `basename` derivado da base do Vite, então o mesmo código roda em `/` (desenvolvimento) e em
  `/atelie/` (produção).
* **React Hook Form** (`useFieldArray` na lista de serviços da OS; `Controller` no campo de dinheiro).
* Cliente HTTP único (`web/src/lib/api.js`): injeta o token CSRF, converte erros da API em `ApiError` e, ao receber 401,
  devolve o usuário para o login.
* **FullCalendar** (pt-BR) para a agenda e **Recharts** para os gráficos de desempenho.
* Interface responsiva: menu inferior e tabelas em formato de cartão no celular; CSS de impressão para "Exportar PDF".

## Ambientes

| | Desenvolvimento | Produção |
|---|---|---|
| Front | Vite em `:5173` com proxy de `/api` | Arquivos estáticos em `public_html/atelie` |
| API | `php -S` em `:8000` | PHP 8.3 da Hostinger |
| Banco | MySQL local (porta 3308), `database/seed.sql` | MySQL da Hostinger, SQL gerado pelo `empacotar.sh` |
| Credencial inicial | `admin` / `1234` (só dev) | `admin` com senha temporária aleatória |
| Configuração | `api/config/config.php` (ignorado pelo Git) | `config/atelie.php` fora do `public_html` |

## Evolução possível

Fora do escopo atual, mas compatível com a arquitetura: perfis de acesso além de administrador, comanda em PDF gerada
no servidor, lembretes por WhatsApp, divisão do pacote JavaScript em partes menores (o build avisa que passa de 500 kB)
e anexos/fotos nas OS.
