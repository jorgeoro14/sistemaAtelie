<p align="center">
  <img src="web/public/logo.jpg" alt="Bella Costura" width="120">
</p>

<h1 align="center">Bella Costura — Gestão do Ateliê</h1>

<p align="center">
  Sistema web para controlar clientes, ordens de serviço, agenda e pagamentos de um ateliê de costura.<br>
  Projeto de extensão (ACEX) — ADS · UNIPAR Cascavel · 2026
</p>

---

## Sobre o projeto

O Bella Costura Gestão substitui o caderno de anotações de um ateliê (consertos, ajustes e peças sob medida) por um
sistema simples, seguro e que funciona no celular, tablet ou computador. A usuária é uma microempreendedora sem formação
em tecnologia; por isso a interface é direta e as regras de negócio evitam erros comuns (entregar o que não está pronto,
editar uma OS já paga, perder a data de uma prova).

**Em produção:** https://vulpesdigital.com.br/atelie

## Funcionalidades

| Área | O que faz |
|---|---|
| **Acesso** | Login com bloqueio após 5 tentativas, troca obrigatória da senha no primeiro acesso, gestão de usuários |
| **Início** | Painel do dia: OS atrasadas, urgentes, entregas e compromissos de hoje, prontas aguardando retirada, pagamentos pendentes |
| **Clientes** | Cadastro com WhatsApp (link direto), histórico de OS, inativar ou excluir |
| **Ordens de serviço** | Vários serviços por OS, total calculado ao vivo e no servidor, prioridade (normal/alta/urgente), costureira responsável, status, pagamento único, exportação em PDF |
| **Agenda** | Calendário (mês/semana/dia) unindo entregas previstas e compromissos (provas, medidas, retiradas) |
| **Serviços e costureiras** | Catálogo com valor sugerido (congelado na OS) e equipe |
| **Desempenho** | Faturamento, a receber, ticket médio, desempenho por costureira, melhores clientes, faturamento por mês e por forma de pagamento |

Regras de negócio completas (RN01–RN18) e as decisões fechadas com a proprietária estão em
[`docs/REQUISITOS.md`](docs/REQUISITOS.md).

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Back-end | PHP 8.2+ (sem framework, sem Composer), PDO com *prepared statements* nativos |
| Banco | MySQL 8 / MariaDB 10.4+ (`utf8mb4`), 7 tabelas |
| Front-end | React 18, Vite, React Router, React Hook Form, FullCalendar, Recharts |
| Hospedagem | Compartilhada (Hostinger), Apache com `.htaccess` |

## Arquitetura

O back-end é orientado a objetos e organizado em camadas:

```
Requisição → Router → Middleware (auth, CSRF, admin) → Controller → Service → Repository → Banco
                                                          │            │
                                                         DTO        Model / Enum
                                                   (valida entrada)  (regras de domínio)
```

| Camada | Responsabilidade |
|---|---|
| `Controller` | Recebe a requisição, chama o serviço, devolve JSON |
| `Dto` | Valida e normaliza a entrada (422 com os campos inválidos) |
| `Service` | Regras de negócio, transações e transições de status |
| `Repository` | Único lugar com SQL |
| `Model` / `Enums` | Entidades e valores tipados (status, prioridade, forma de pagamento) |
| `Middleware` | Autenticação (401), perfil admin (403), proteção CSRF (403) |
| `Core` | Roteador, sessão, configuração, validador, banco, resposta |

Detalhes, fluxo completo de uma requisição e decisões de projeto em [`docs/ARQUITETURA.md`](docs/ARQUITETURA.md).

## Estrutura de pastas

```
.
├── api/                    Back-end PHP
│   ├── index.php           Ponto de entrada único
│   ├── app/                Core · Middleware · Controllers · Dto · Services · Repositories · Models · Enums · Exceptions
│   ├── config/             config.example.php (o config.php real nunca vai para o Git)
│   └── bin/                instalar.php, backup.php, teste_api.py
├── web/                    Front-end React (Vite)
│   └── src/                pages · components · lib
├── database/               schema.sql e seed.sql
├── docs/                   Documentação
├── dev.sh                  Sobe o ambiente de desenvolvimento
├── empacotar.sh            Gera o pacote de produção
└── .htaccess               Roteamento para publicação na raiz do domínio
```

## Como rodar localmente

Pré-requisitos: **PHP 8.2+** (extensão `pdo_mysql`), **MySQL 8 / MariaDB 10.4+**, **Node 18+**.
Testado com PHP 8.5, MySQL 8.4 e Node 18+ no macOS.

### Jeito rápido (macOS/Linux)

```bash
./dev.sh --instalar   # só na primeira vez: cria um MySQL local (porta 3308), o banco, o config.php e o seed
./dev.sh              # nas próximas: sobe MySQL, API (8000) e front (5173)
```

Abra http://localhost:5173 e entre com `admin` / `1234`. **Essa credencial existe só em desenvolvimento** e o primeiro
login obriga a trocar a senha.

### Passo a passo manual

```bash
# 1. Banco
mysql -u root -p -e "CREATE DATABASE atelie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p atelie < database/schema.sql
mysql -u root -p atelie < database/seed.sql

# 2. Configuração (ajuste host, porta, usuário e senha do banco)
cp api/config/config.example.php api/config/config.php

# 3. API (porta 8000)
php -S localhost:8000 -t api api/index.php

# 4. Front (porta 5173, com proxy de /api para o PHP)
cd web && npm install && npm run dev
```

## Testes

Com a API no ar e o banco recém-criado (seed):

```bash
python3 api/bin/teste_api.py
```

São **56 verificações** automáticas: autenticação, CSRF, cadastros, OS (total, numeração, validações), transições de
status, pagamento e estorno, agenda, relatórios, usuários e bloqueio de login. O script altera dados e troca a senha do
admin: use só em desenvolvimento. O roteiro de aceitação manual e de segurança está em [`docs/TESTES.md`](docs/TESTES.md).

## Segurança

* Sessão PHP com cookie `HttpOnly`, `Secure` (em HTTPS) e `SameSite=Lax`; expira após 8 h sem uso; ID regenerado no login.
* Proteção **CSRF** por cabeçalho `X-CSRF-Token` em toda mutação.
* **SQL injection:** somente *prepared statements* nativos com tipos explícitos.
* Senhas com `password_hash`; bloqueio de 15 min após 5 tentativas; tempo de resposta constante para usuário inexistente.
* Validação no servidor via DTOs; o front nunca usa HTML cru (React escapa o conteúdo).
* Configuração e logs ficam **fora** da pasta pública; código-fonte da API bloqueado por `.htaccess` (403).
* Erros 500 genéricos para o usuário; detalhes só no arquivo de log.

## Publicação

`./empacotar.sh` gera em `deploy/` o zip, o SQL de produção (com **senha temporária aleatória**) e o modelo de
configuração. O passo a passo da Hostinger, a conferência pós-publicação e o backup estão em
[`docs/DEPLOY.md`](docs/DEPLOY.md).

## Documentação

| Documento | Conteúdo |
|---|---|
| [`docs/GUIA-DO-USUARIO.md`](docs/GUIA-DO-USUARIO.md) | Manual para quem usa o sistema no dia a dia |
| [`docs/REQUISITOS.md`](docs/REQUISITOS.md) | Levantamento de requisitos, decisões e rastreabilidade RN → código |
| [`docs/ARQUITETURA.md`](docs/ARQUITETURA.md) | Camadas, fluxo da requisição, decisões de projeto |
| [`docs/API.md`](docs/API.md) · [`api/requests.http`](api/requests.http) | Contrato da API e exemplos de chamadas |
| [`docs/ER.md`](docs/ER.md) | Diagrama Entidade-Relacionamento |
| [`docs/CLASSES.md`](docs/CLASSES.md) | Diagrama de classes |
| [`docs/TESTES.md`](docs/TESTES.md) | Testes de aceitação, segurança e registro da execução |
| [`docs/DEPLOY.md`](docs/DEPLOY.md) | Publicação, conferência e backup |
| [`docs/ACEX.md`](docs/ACEX.md) | Registro da Atividade Curricular de Extensão |

## Convenções

* **Dinheiro** em centavos inteiros (R$ 35,00 = `3500`); **datas** `YYYY-MM-DD`; **telefone** só dígitos com DDD.
* Fuso `America/Sao_Paulo`.
* Mensagens e interface em português do Brasil.

## Autoria

Desenvolvido como atividade de extensão do curso de Análise e Desenvolvimento de Sistemas (ADS) da UNIPAR Cascavel,
para o Ateliê Bella Costura.
