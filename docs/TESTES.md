# Testes de aceitação e segurança

Execute com dados reais de teste antes da entrega. Marque ✅/❌ e anote a data.

## Fluxos de aceitação (seção 8 dos requisitos + decisões da seção 10)

| # | Fluxo | Resultado esperado | OK |
|---|---|---|---|
| 1 | Entrar com `admin`/`1234` | É forçado a trocar a senha; depois entra normalmente | ☐ |
| 2 | Cadastrar cliente novo dentro da Nova OS | Cliente é selecionado e o resto do formulário não se perde | ☐ |
| 3 | Criar OS com 3 serviços, remover 2 | Total recalcula ao vivo; botão de remover fica desabilitado no último | ☐ |
| 4 | API: enviar OS sem itens | HTTP 422 com `campos.itens` | ☐ |
| 5 | Marcar prioridade urgente, pronta e entregue | Datas gravadas; "entregue" só após "pronto" | ☐ |
| 6 | Voltar um status | Data correspondente é apagada | ☐ |
| 7 | Registrar pagamento (pix) | Situação passa de Pendente para Pago | ☐ |
| 8 | Tentar editar OS paga | Mensagem "OS paga não pode ser editada"; estornar libera a edição | ☐ |
| 9 | Criar compromisso de prova | Aparece na agenda junto da entrega prevista | ☐ |
| 10 | Dois compromissos no mesmo horário | Aviso, sem bloqueio (RN16) | ☐ |
| 11 | Criar segundo usuário e entrar com ele | Consegue entrar; não consegue desativar a si mesmo nem o último admin | ☐ |
| 12 | Cartões do Início | Cada número é igual ao total mostrado na lista filtrada | ☐ |
| 13 | Dashboard de desempenho | 15 OS de valores redondos conferem na calculadora | ☐ |

## Segurança

| # | Teste | Esperado | OK |
|---|---|---|---|
| S1 | `GET /api/clientes` sem login | 401 e nenhum dado | ☐ |
| S2 | `POST /api/clientes` logado, sem `X-CSRF-Token` | 403 `csrf_invalido` | ☐ |
| S3 | 5 senhas erradas seguidas | Usuário bloqueado por 15 min | ☐ |
| S4 | Nome de cliente `'; DROP TABLE clientes;--` | Gravado como texto; tabela intacta | ☐ |
| S5 | Observação `<script>alert(1)</script>` | Aparece como texto, sem executar | ☐ |
| S6 | Cookie de sessão | Marcado `HttpOnly`, `Secure` (produção), `SameSite=Lax` | ☐ |
| S7 | Acessar `/api/app/...` e `/api/config/...` pelo navegador | 403 | ☐ |
| S8 | Provocar erro interno | Resposta genérica, sem SQL/caminhos; detalhe só no log | ☐ |

## Execução automatizada da API (registro)

Executado em 14/11/2026 em ambiente local (PHP 8.5, MySQL 8.4) com `python3 api/bin/teste_api.py`:
**56/56 verificações aprovadas**, sem avisos ou erros no log do PHP.

| Cobertura | Resultado |
|---|---|
| Autenticação: login inválido (401), troca obrigatória de senha, logout, bloqueio após 5 tentativas (403) | ✅ |
| CSRF: mutação sem token (403) | ✅ (S2) |
| Rota protegida sem login (401) | ✅ (S1) |
| Clientes: validação 422, telefone só dígitos, aviso de telefone repetido, excluir x inativar | ✅ |
| Serviços e costureiras: criar e editar | ✅ |
| OS: itens obrigatórios, prevista ≥ recebimento (RN06), total no servidor (RN03), número sequencial (RN15), prioridade booleana | ✅ |
| Status: "entregue" só após "pronto" (RN05), pagamento único, OS paga não edita nem cancela, estorno libera edição, reabrir cancelada | ✅ |
| Agenda, dashboard, 4 relatórios, usuários (duplicado sem diferenciar maiúsculas, redefinir senha) | ✅ |
| Nome de cliente `'; DROP TABLE clientes;--` gravado como texto, tabela intacta | ✅ (S4) |
| `<script>` no nome é guardado como texto; o React escapa na tela (nenhum `dangerouslySetInnerHTML` no front) | ✅ |

Também validado manualmente no navegador (Vite + API): login → troca de senha → início → agenda → lista de OS →
nova OS (busca de cliente, valor padrão do serviço, total ao vivo, salvar) → detalhe da OS → desempenho.
Os fluxos de aceitação 1–13 da tabela acima ainda devem ser conferidos pela proprietária antes da entrega.

Pendente de teste real: HTTPS em produção (cookie `Secure`), `.htaccess` na Hostinger e restauração de backup.
