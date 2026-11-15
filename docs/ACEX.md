# ATIVIDADE CURRICULAR DE EXTENSÃO — ACEX
**Engenharia de Software / ADS — UNIPAR Cascavel — 2026**
**Documento para registro de atividade de extensão — Projeto: MUNDO TECH 2026**

> Campos marcados com **[PREENCHER]** dependem de informações suas. O restante já está redigido.

| Campo | Conteúdo |
|---|---|
| **Validar (Cód — disciplina)** | **[PREENCHER]** código e nome da disciplina |
| **Público atendido** | ( ) Interno  **( X ) Externo** — microempreendedora do ramo de costura |
| **Ação desenvolvida** | **J. Implementação de Software** (confirme a letra conforme a apresentação do projeto) |
| **Integrantes (RA — Nome — Período)** | **[PREENCHER]** |
| **Título da atividade** | Bella Costura Gestão: sistema web de gestão para ateliê de costura |
| **Local ou demandante** | Ateliê Bella Costura — demandante: **[PREENCHER nome da proprietária]** |
| **Quantidade de público atingido** | 1 instituição (o ateliê) e 1 usuária direta; clientes do ateliê atendidos indiretamente. **[confirmar números]** |

## Resumo da atividade (até 100 palavras)

Esperava-se substituir o caderno de anotações de um ateliê de costura por um sistema simples de controle. Foi desenvolvido o Bella Costura Gestão, aplicação web responsiva (PHP, MySQL e React) com login, cadastro de clientes, costureiras e serviços, ordens de serviço com prioridade, status e pagamento, agenda unificada de entregas e provas, painel do dia e dashboard de desempenho. O back-end usa orientação a objetos em camadas (Controller, Service, Repository, Model e DTO) e práticas de segurança (sessão segura, CSRF, consultas preparadas). A documentação inclui requisitos, diagramas e roteiro de testes.

*(≈ 87 palavras)*

## Objetivos (até 4)

1. Digitalizar o controle de clientes, ordens de serviço, agenda e pagamentos do ateliê, reduzindo esquecimentos e atrasos nas entregas.
2. Oferecer uma interface simples e responsiva, usável no celular, tablet ou computador por pessoa sem formação em tecnologia.
3. Aplicar boas práticas de engenharia de software: orientação a objetos, arquitetura em camadas, validação no servidor e segurança de dados (LGPD).
4. Fornecer indicadores (faturamento, a receber, desempenho por costureira, melhores clientes) para apoiar decisões da proprietária.

## Etapas e período de realização

**[AJUSTE as datas conforme o seu cronograma real]**

| Etapa | Descrição | Período |
|---|---|---|
| 1. Levantamento de requisitos | Entrevista com a proprietária, definição de escopo, telas, regras de negócio (RN01–RN18) e decisões fechadas | **[datas]** |
| 2. Modelagem | Diagrama Entidade-Relacionamento, diagrama de classes, contrato da API, paleta visual da marca | **[datas]** |
| 3. Back-end | API REST em PHP 8, PDO, sessão, CSRF, cadastros, ordens de serviço, agenda, dashboard e relatórios | **[datas]** |
| 4. Front-end | 14 telas em React: login, início, agenda, OS, cadastros, usuários e desempenho | **[datas]** |
| 5. Testes | Roteiro de aceitação e de segurança (`docs/TESTES.md`) | **[datas]** |
| 6. Publicação e treinamento | Hospedagem (Hostinger), backup, treinamento da usuária | **[datas]** |

## Resultados alcançados

* Sistema com **14 telas**, **API com ~35 rotas** e **7 tabelas**, cobrindo as regras RN01–RN18.
* Funcionalidades: login com bloqueio, troca obrigatória de senha, gestão de usuários, clientes (com WhatsApp), costureiras, catálogo de serviços, OS com vários serviços e total automático, status e pagamento único, agenda (mês/semana/dia), painel do dia, dashboard de desempenho e exportação da OS em PDF.
* Documentação técnica completa (requisitos, API, ER, classes, deploy e testes).
* Teste automatizado da API com 56 verificações aprovadas (`api/bin/teste_api.py`) e validação das telas no navegador.
* Link do software: **https://vulpesdigital.com.br/atelie**
* Link do GitHub com o fonte: **https://github.com/jorgeoro14/sistemaAtelie**
* **[Após o teste real: número de OS cadastradas, feedback da usuária]**

---

# APRESENTAÇÃO DAS EVIDÊNCIAS

*(Todas as imagens devem ter legenda embaixo. Insira os prints abaixo depois de rodar o sistema.)*

## J.a Protótipos

*[Print de cada tela. Legenda sugerida: "Figura 1 — Tela de login (T01)".]*

Telas a capturar: T01 Login, T02 Trocar senha, T03 Início, T04 Agenda, T05 Compromisso, T06 Lista de OS, T07 Nova OS, T08 Detalhe da OS, T09 Clientes, T10 Serviços, T11 Costureiras, T12 Usuários, T14 Desempenho, e uma versão no celular.

## J.b Levantamento de requisitos

Resumo em [`REQUISITOS.md`](REQUISITOS.md): escopo do MVP, 14 telas, regras de negócio RN01–RN18, decisões fechadas com a proprietária (pagamento único, prioridade em 3 níveis, relatórios) e requisitos não funcionais (responsividade, segurança, backup).

## J.c Diagrama de classes

Ver [`CLASSES.md`](CLASSES.md). *[Inserir imagem do diagrama com legenda: "Figura X — Diagrama de classes do back-end".]*

## J.d Diagrama Entidade-Relacionamento

Ver [`ER.md`](ER.md). *[Inserir imagem do diagrama com legenda: "Figura X — Diagrama Entidade-Relacionamento (7 tabelas)".]*

## J.e Outros itens de documentação

* Contrato da API e exemplos: [`API.md`](API.md), `api/requests.http`
* Roteiro de testes de aceitação e segurança: [`TESTES.md`](TESTES.md)
* Publicação e backup: [`DEPLOY.md`](DEPLOY.md)

## J.f Link do software e do GitHub

* Software: **https://vulpesdigital.com.br/atelie**
* GitHub: **https://github.com/jorgeoro14/sistemaAtelie**
