# Guia do usuário — Bella Costura Gestão

Manual para o dia a dia do ateliê. Não precisa saber nada de tecnologia: tudo é feito com cliques e preenchendo campos.

**Endereço do sistema:** https://vulpesdigital.com.br/atelie
Funciona no computador, no tablet e no celular (no celular o menu fica embaixo da tela).

---

## 1. Primeiro acesso

1. Abra o endereço acima e entre com o **usuário** e a **senha temporária** que você recebeu.
2. O sistema vai pedir para **criar uma senha nova**. Use uma senha que só você saiba (mínimo de 8 caracteres).
3. Depois disso, o sistema abre na tela **Início**.

> **Dica:** se o navegador oferecer para salvar a senha, aceite só em um computador ou celular seu.
> **Esqueci a senha:** peça a outra pessoa administradora para redefinir em *Usuários*.

Se errar a senha **5 vezes seguidas**, o usuário fica bloqueado por 15 minutos. É uma proteção, é só aguardar.

---

## 2. Tela Início — o resumo do dia

Mostra o que precisa de atenção hoje. Cada quadrado é clicável e leva à lista filtrada:

| Quadrado | Significa |
|---|---|
| OS atrasadas | A data de entrega já passou e a OS ainda não foi entregue |
| Urgentes / Prioridade alta abertas | Trabalhos que precisam ser feitos primeiro |
| Entregas de hoje | Peças com entrega prevista para hoje |
| Compromissos de hoje | Provas, medidas e retiradas marcadas para hoje |
| Prontas aguardando retirada | Já estão prontas, falta o cliente buscar |
| Entregues sem pagamento / Pagamentos pendentes | Dinheiro a receber |

Abaixo ficam as listas **Atrasadas** e **Agenda de hoje**.

---

## 3. Clientes

**Menu → Clientes.**

* **Novo cliente:** botão para cadastrar nome e telefone (com DDD). Observações são opcionais.
* **Buscar:** digite parte do nome ou do telefone.
* **WhatsApp:** abre a conversa com o cliente já com o número preenchido.
* **Detalhe do cliente:** mostra todas as ordens de serviço dele.
* **Excluir ou inativar:** se o cliente nunca teve OS, ele é excluído. Se já teve, ele é **inativado** (some das buscas,
  mas o histórico é preservado).

O sistema avisa, sem bloquear, quando o telefone já está cadastrado para outro cliente.

---

## 4. Ordens de serviço (OS)

**Menu → Ordens.** Cada trabalho recebido vira uma OS, com número sequencial (#1, #2, #3…).
**O número nunca é reaproveitado**, mesmo que a OS seja cancelada.

### 4.1 Criar uma OS

1. Clique em **+ Nova OS**.
2. **Cliente:** digite o nome ou telefone e escolha na lista. Se for um cliente novo, clique em **+ Novo**; você cadastra
   sem sair da tela e o resto do formulário não se perde.
3. **Costureira** (opcional), **Prioridade** (Normal, Alta ou Urgente) e as datas de **recebimento** e **entrega prevista**.
   A entrega não pode ser antes do recebimento.
4. **Serviços:** escolha o tipo de serviço. O valor sugerido aparece sozinho, mas **você pode mudar**. Descreva o que será
   feito (ex.: "Barra da calça jeans"). Use **+ Adicionar serviço** para colocar mais de um.
5. O **Total** é somado automaticamente.
6. Clique em **Salvar OS**.

### 4.2 Acompanhar o andamento

Na tela da OS, os botões seguem o caminho do trabalho:

```
Recebido  →  Em andamento  →  Pronto  →  Entregue
```

* **Iniciar:** passa para *Em andamento*.
* **Marcar como pronto:** registra a data em que ficou pronto.
* **Marcar como entregue:** só aparece depois de *Pronto*. Não dá para entregar o que não está pronto.
* **Voltar status:** se clicou errado, volte o status. As datas dos passos seguintes são apagadas.
* **Cancelar OS:** não é possível cancelar uma OS já **entregue** ou **paga**. Uma OS cancelada pode ser **reaberta** (botão *Reabrir*).

### 4.3 Receber o pagamento

1. Na OS, clique em **Registrar pagamento** (para desfazer, **Estornar pagamento**).
2. Escolha a **forma** (dinheiro, Pix, cartão de débito, cartão de crédito ou outro) e a **data**.
3. O valor pago é sempre o **total da OS** (não há sinal nem parcelas).

Depois de paga, a OS **não pode mais ser editada**. Se precisar corrigir algo:
**estornar o pagamento → editar a OS → registrar o pagamento de novo.**

### 4.4 Outros botões da OS

* **Editar:** altera cliente, datas, prioridade e serviços (enquanto não estiver paga).
* **WhatsApp:** fala com o cliente.
* **Exportar PDF:** abre a impressão do navegador; escolha "Salvar como PDF" para guardar ou envie para a impressora.

### 4.5 Encontrar uma OS

Na lista, use a busca (nome, telefone ou número) e os filtros de **status**, **prioridade**, **costureira**,
**período de entrega** e **pago/pendente**. A lista já vem na melhor ordem de trabalho:
**abertas primeiro → urgentes → altas → atrasadas → data de entrega mais próxima.**

---

## 5. Agenda

**Menu → Agenda.** Um calendário que junta tudo em um lugar:

* **Entregas previstas** das OS (roxo);
* **Compromissos** como prova, medidas e retirada (rosa);
* **Entregas atrasadas** (vermelho).

Alterne entre **Mês**, **Semana** e **Dia**, e filtre por costureira.

Para marcar um horário, clique em **+ Novo compromisso**, escolha o cliente (e, se quiser, a OS), o tipo, a data e a hora.
Se já houver outro compromisso no mesmo horário, o sistema **avisa, mas deixa marcar**.

---

## 6. Serviços e costureiras

* **Menu → Serviços:** catálogo com nome, categoria (conserto, sob medida, ajuste, outro) e valor sugerido.
  Mudar o valor aqui **não altera OS antigas**, porque o valor fica congelado em cada OS.
* **Menu → Costureiras:** cadastro da equipe. Quem saiu pode ser **inativada**, sem perder o histórico.

---

## 7. Desempenho

**Menu → Desempenho** (somente administrador). Escolha o período e veja:

| Indicador | Como é calculado |
|---|---|
| **Faturamento** | Soma do total das OS **pagas** no período, pela **data do pagamento** |
| **A receber** | Todas as OS ainda **não pagas** (exceto canceladas), de qualquer data |
| **Ticket médio** | Faturamento ÷ número de OS pagas |
| **Por costureira** | Quantidade de OS, concluídas, valor total e tempo médio até ficar pronto |
| **Melhores clientes** | Os 10 clientes que mais gastaram |
| **Faturamento por mês / forma de pagamento** | Gráficos do ano |

---

## 8. Usuários

**Menu → Usuários** (somente administrador). Cria novos usuários, ativa ou desativa e redefine senhas.
Por segurança, **não é possível desativar a si mesmo** nem o **último administrador**.

---

## 9. Dúvidas comuns

| Situação | O que fazer |
|---|---|
| Errei o valor de um serviço numa OS **paga** | Estornar o pagamento, editar a OS e pagar de novo |
| Cliente trocou de telefone | Clientes → abrir o cliente → editar |
| A OS não deixa marcar como entregue | Ela precisa estar **Pronta** antes |
| O sistema voltou para a tela de login sozinho | Você ficou 8 horas sem usar (a sessão expira por segurança). Entre de novo |
| Quero guardar uma cópia dos dados | Peça o backup ao responsável técnico (veja `DEPLOY.md`) |
| Algo deu erro e não sei o motivo | Anote a tela e a hora e avise o responsável técnico |

---

## 10. Boas práticas

* Cadastre a OS **no momento em que receber a peça**, com a data de entrega combinada.
* Marque **Pronto** assim que terminar: o cliente aparece em "Prontas aguardando retirada".
* Registre o **pagamento** no mesmo dia, para o "A receber" ficar certo.
* Não compartilhe sua senha; crie um usuário para cada pessoa.
* Saia do sistema (**Sair**) quando usar um computador de outras pessoas.
