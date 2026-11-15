# Levantamento de requisitos — rastreabilidade

Fonte: *Sistema de Gestão do Ateliê — Requisitos* (24 páginas), incluindo a seção 10 (decisões fechadas),
que **prevalece** sobre as seções 4 a 7.

## Decisões fechadas aplicadas (seção 10)

| # | Decisão | Onde está no código |
|---|---|---|
| 1 | Sem sinal: pagamento único que quita a OS | `OrdemServicoService::registrarPagamento`, colunas `forma_pagamento`/`data_pagamento` |
| 2 | Prioridade em 3 níveis (normal, alta, urgente) | `Enums/Prioridade`, `ENUM` no banco, ordenação em `OrdemServicoRepository::listar` |
| 3 | Comanda impressa: não; PDF opcional | Botão "Exportar PDF" (impressão do navegador com CSS `@media print`) |
| 4 | Um computador só | Sem tratamento de edição simultânea; login continua obrigatório |
| 5 | Valor do catálogo é sugestão, congelado na OS | `os_itens.valor_centavos` |
| 6 | Dashboard de desempenho (T14) | `RelatorioService`, página `Desempenho.jsx` |

Suposições confirmadas no texto: **OS paga não pode ser editada** (estorna → edita → paga de novo) e
**faturamento = soma do total das OS pagas por `data_pagamento`**.

## Requisitos funcionais — telas

| Tela | Implementação |
|---|---|
| T01 Login | `pages/Login.jsx` · `POST /api/login` |
| T02 Trocar senha | `pages/TrocarSenha.jsx` · `POST /api/me/senha` (obrigatória no 1º login) |
| T03 Início | `pages/Inicio.jsx` · `GET /api/dashboard` |
| T04 Agenda | `pages/Agenda.jsx` (FullCalendar mês/semana/dia) · `GET /api/agenda` |
| T05 Compromisso | modal em `Agenda.jsx` · `/api/agendamentos` |
| T06 Lista de OS | `pages/ListaOs.jsx` · `GET /api/os` |
| T07 Nova/editar OS | `pages/FormOs.jsx` (`useFieldArray`, total ao vivo, cliente novo sem sair da tela) |
| T08 Detalhe da OS | `pages/DetalheOs.jsx` |
| T09 Clientes | `pages/Clientes.jsx`, `DetalheCliente.jsx` |
| T10 Serviços | `pages/Servicos.jsx` |
| T11 Costureiras | `pages/Costureiras.jsx` |
| T12 Usuários | `pages/Usuarios.jsx` (admin) |
| T13 Erro/sem permissão | `pages/Erro.jsx` |
| T14 Desempenho | `pages/Desempenho.jsx` (Recharts) |

## Regras de negócio

| Cód. | Regra | Onde é garantida |
|---|---|---|
| RN01 | OS com cliente e ≥ 1 serviço | `OrdemServicoDto` (422 em `itens`); front impede remover o último |
| RN02 | Item: tipo do catálogo, descrição, valor ≥ 0 | `ItemOsDto`, `validarReferencias` |
| RN03 | Total = soma dos itens, calculado no back | `OrdemServicoDto::total()` |
| RN04 | Fluxo de status e voltar status limpando datas | `OrdemServicoService::mudarStatus` |
| RN05 | Pronto grava `data_pronto`; entregue só se pronta | idem |
| RN06 | Recebimento (padrão hoje) e previsão ≥ recebimento | `OrdemServicoDto` + `CHECK` no banco |
| RN07 | Atrasada derivada | `OrdemServico::atrasada()` + filtro SQL |
| RN08 | Ordem: urgente, alta, normal, atrasada, data prevista | `OrdemServicoRepository::listar` |
| RN09 | Costureira opcional; inativa só nas OS antigas | `validarReferencias` |
| RN10–12 | Pagamento único, situação derivada, OS paga não edita | `registrarPagamento`, `atualizar` |
| RN13 | Nunca apagar com histórico; cliente sem OS pode excluir | `ClienteService::excluir` |
| RN14 | Telefone 10/11 dígitos; aviso de duplicidade | `Validator::telefone`, `ClienteService` |
| RN15 | Número sequencial sem reaproveitar | `proximoNumero()` + `UNIQUE` |
| RN16 | Compromisso exige cliente/data/hora; choque só avisa | `AgendamentoService` |
| RN17 | Só admin gerencia usuários; usuário único (sem diferenciar maiúscula) | `AdminMiddleware`, `UsuarioService`, collation `_ci` |
| RN18 | Auditoria `criado_por`, `criado_em`, `atualizado_em` | schema + `OrdemServicoRepository::criar` |

## Requisitos não funcionais e segurança

| Requisito | Como é atendido |
|---|---|
| Consultas preparadas (PDO) | `Database` usa `EMULATE_PREPARES = false` e `bindValue` tipado; nenhum SQL concatena entrada do usuário |
| XSS | React escapa toda saída; não há `dangerouslySetInnerHTML` |
| CSRF | Token por sessão, header `X-CSRF-Token` em toda mutação (`CsrfMiddleware`) |
| Sessão | Cookie `HttpOnly`, `Secure`, `SameSite=Lax`; expira em 8 h sem uso; logout destrói no servidor; `session_regenerate_id` no login |
| Senhas | `password_hash`/`password_verify`, mínimo de 8; bloqueio 5 tentativas → 15 min |
| Enumeração de usuários | Mesma resposta/tempo para usuário inexistente e senha errada |
| Config fora do público | `Config` procura `ATELIE_CONFIG` ou `../config/atelie.php`; `config.php` no `.gitignore` |
| Erros | `display_errors=0` em produção; log em arquivo; resposta 500 genérica |
| LGPD | Só nome e telefone; nenhuma listagem sem login |
| Backup | `api/bin/backup.php` + backup automático da Hostinger |
| Responsivo | Menu inferior e tabelas em cartões no celular; botões ≥ 46 px |
