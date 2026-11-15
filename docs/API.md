# Contrato da API

Base: `/api` (mesmo domínio do front). JSON em UTF-8.

## Convenções

* **Dinheiro** em centavos inteiros (R$ 35,00 = `3500`). **Datas** `YYYY-MM-DD`; data+hora `YYYY-MM-DD HH:MM`. **Telefone** só dígitos com DDD.
* **Autenticação**: sessão PHP (cookie `HttpOnly`). Toda rota, exceto `POST /login`, exige sessão (**401**).
* **CSRF**: mutações (`POST/PUT/PATCH/DELETE`) exigem o header `X-CSRF-Token` entregue por `GET /api/me` / `POST /api/login` (**403** se faltar).
* **Listas**: `?pagina=1&por_pagina=20&q=` → `{ "dados": [...], "paginacao": { pagina, por_pagina, total, total_paginas } }`.
* **Avisos** (não bloqueiam): respostas de criar/editar cliente e compromisso podem trazer `"avisos": [{ "codigo", "mensagem" }]`.
* **Erros**: `{ "erro": "codigo", "mensagem": "texto", "campos": { "telefone": "inválido" } }` com HTTP 400/401/403/404/405/422/500.

## Endpoints

| Método | Rota | Função |
|---|---|---|
| POST | `/login` | Entrar (`usuario`, `senha`) → mesmo corpo de `/me` |
| POST | `/logout` | Sair |
| GET | `/me` | Usuário, `trocar_senha`, `csrf_token` |
| POST | `/me/senha` | Trocar a própria senha (`senha_atual`, `nova_senha`, `confirmacao`) |
| GET | `/dashboard` | Contadores e listas do dia |
| GET, POST | `/clientes` | Listar (`q`, `ativo`) / criar |
| GET, PUT, DELETE | `/clientes/{id}` | Detalhe com histórico / editar / excluir **ou** inativar (`{"acao":"excluido"\|"inativado"}`) |
| GET, POST | `/costureiras` | Listar (`ativa`) / criar |
| PUT | `/costureiras/{id}` | Editar e inativar (`ativa`) |
| GET, POST | `/servicos` | Catálogo (`ativo`) / criar |
| PUT | `/servicos/{id}` | Editar e inativar |
| GET, POST | `/os` | Listar com filtros / criar OS com itens |
| GET, PUT | `/os/{id}` | Detalhe / editar OS e itens (bloqueia se paga) |
| PATCH | `/os/{id}/status` | `status` (`recebido`, `em_andamento`, `pronto`, `entregue`, `cancelado`) + `data` opcional |
| POST | `/os/{id}/pagamento` | Pagamento único: `forma` + `data_pagamento` (valor = total) |
| DELETE | `/os/{id}/pagamento` | Estornar |
| GET | `/agenda?de=&ate=&costureira_id=` | Entregas previstas + compromissos, unificados |
| POST | `/agendamentos` | Criar compromisso |
| GET, PUT, DELETE | `/agendamentos/{id}` | Detalhe / editar / cancelar |
| GET, POST | `/usuarios` | Listar / criar (admin) |
| PUT | `/usuarios/{id}` | Editar, ativar/inativar (admin) |
| POST | `/usuarios/{id}/redefinir-senha` | Admin define nova senha (admin) |
| GET | `/relatorios/resumo?de=&ate=` | Faturamento, a receber, contagens, ticket médio (admin) |
| GET | `/relatorios/costureiras?de=&ate=` | Desempenho por costureira, com linha "sem costureira" (admin) |
| GET | `/relatorios/clientes?de=&ate=` | Totais e 10 maiores clientes (admin) |
| GET | `/relatorios/faturamento-mensal?ano=` | 12 meses, por forma de pagamento (admin) |

### Diferenças em relação ao texto original (seção 10 prevalece)

* `prioridade` é `normal | alta | urgente` (o booleano `true/false` ainda é aceito: `true` = `alta`).
* Pagamento é **único**: rotas `POST/DELETE /os/{id}/pagamento` (singular); não existe `/pagamentos/{pid}`.
* `situacao_pagamento` é apenas `pendente` ou `pago`.

### Filtros de `GET /os`

`status`, `prioridade`, `costureira_id`, `cliente_id`, `de`/`ate` (sobre `data_prevista`), `pagamento` (`pendente|pago`),
`atrasadas=1`, `abertas=1` (não entregues/canceladas), `q` (cliente, telefone ou nº).
Ordem padrão (RN08): abertas primeiro → urgente, alta, normal → atrasadas → data prevista.

### Exemplo — criar OS

```http
POST /api/os
X-CSRF-Token: <token>
Content-Type: application/json

{
  "cliente_id": 12, "costureira_id": null, "prioridade": "alta",
  "data_recebimento": "2026-11-12", "data_prevista": "2026-11-19",
  "observacoes": "Cliente tem pressa, evento no sábado",
  "itens": [
    { "servico_id": 3, "descricao": "Barra da calça jeans", "valor_centavos": 3500 },
    { "servico_id": 5, "descricao": "Apertar cintura do vestido", "valor_centavos": 6000 }
  ]
}
```

Resposta **201**: OS completa com `numero`, `total_centavos` (9500), `status` (`recebido`), `situacao_pagamento` (`pendente`), `atrasada` (false) e `itens`.

### Transições de status (`PATCH /os/{id}/status`)

| De → Para | Regra |
|---|---|
| recebido/em_andamento → pronto | grava `data_pronto` (`data` ou hoje) |
| pronto → entregue | grava `data_entrega`; **só** se estiver pronta (RN05) |
| qualquer retorno | volta um status e limpa as datas à frente (RN04) |
| não entregue → cancelado | bloqueado se a OS estiver paga (estornar antes) |
| cancelado → recebido | reabrir |

### Códigos de erro de negócio (HTTP 422)

`dados_invalidos`, `os_paga`, `os_cancelada`, `ja_paga`, `nao_paga`, `status_invalido`, `precisa_estar_pronta`,
`nao_pode_cancelar`, `ultimo_admin`, `nao_pode_inativar_a_si`, `periodo_grande`.
Autenticação: `nao_autenticado`/`credenciais_invalidas` (401), `usuario_bloqueado`/`csrf_invalido`/`proibido` (403).
