# Modelagem de dados

Banco MySQL/MariaDB, `utf8mb4`. Sete tabelas (a tabela `pagamentos` do desenho original foi eliminada
pela decisão fechada nº 1: pagamento único, gravado na própria OS). Dinheiro em **centavos inteiros**.

## Diagrama Entidade-Relacionamento

```mermaid
erDiagram
    usuarios      ||--o{ ordens_servico : "criou (criado_por)"
    clientes      ||--o{ ordens_servico : "tem"
    costureiras   |o--o{ ordens_servico : "executa (opcional)"
    ordens_servico ||--|{ os_itens : "contém (>= 1)"
    servicos      ||--o{ os_itens : "tipo"
    clientes      ||--o{ agendamentos : "tem"
    ordens_servico |o--o{ agendamentos : "vinculada (opcional)"

    usuarios {
        int id PK
        varchar nome
        varchar usuario UK
        varchar senha_hash
        enum perfil "admin"
        tinyint ativo
        tinyint trocar_senha
        tinyint tentativas_login
        datetime bloqueado_ate
    }
    clientes {
        int id PK
        varchar nome
        varchar telefone "so digitos com DDD"
        text observacoes
        tinyint ativo
    }
    costureiras {
        int id PK
        varchar nome
        varchar telefone
        tinyint ativa
    }
    servicos {
        int id PK
        varchar nome
        enum categoria "conserto|sob_medida|ajuste|outro"
        int valor_padrao_centavos
        tinyint ativo
    }
    ordens_servico {
        int id PK
        int numero UK "sequencial RN15"
        int cliente_id FK
        int costureira_id FK "nulo"
        enum prioridade "normal|alta|urgente"
        enum status "recebido|em_andamento|pronto|entregue|cancelado"
        date data_recebimento
        date data_prevista
        date data_pronto
        date data_entrega
        text observacoes
        int total_centavos
        enum forma_pagamento "nulo ate pagar"
        date data_pagamento "nulo ate pagar"
        int criado_por FK
    }
    os_itens {
        int id PK
        int os_id FK "ON DELETE CASCADE"
        int servico_id FK
        varchar descricao
        int valor_centavos "congelado"
        smallint ordem
    }
    agendamentos {
        int id PK
        int cliente_id FK
        int os_id FK "nulo"
        datetime data_hora
        enum tipo "prova|medidas|retirada|outro"
        varchar observacao
        enum status "marcado|realizado|cancelado"
    }
```

Todas as tabelas têm também `criado_em` e `atualizado_em`.

## Decisões de modelagem

| Tema | Decisão |
|---|---|
| Integridade | FKs com `ON DELETE RESTRICT` onde há histórico; `CASCADE` somente de `os_itens` para a OS. |
| Valores | `INT` em centavos (R$ 35,00 = 3500). Evita erro de arredondamento. |
| `status`, `prioridade`, `forma` | `ENUM` no banco **e** enums PHP (`app/Enums`), mantidos iguais. |
| Atrasada / situação do pagamento | **Derivadas**, nunca gravadas (RN07, RN11). |
| Pagamento | `forma_pagamento` + `data_pagamento` na OS; pago = `data_pagamento` preenchida. |
| Índices | `clientes.nome`, `clientes.telefone`, `ordens_servico.status`, `.data_prevista`, `.data_recebimento`, `.data_pagamento`, `.costureira_id`, `agendamentos.data_hora`. |
| Fuso | `America/Sao_Paulo` no PHP e `SET time_zone='-03:00'` no MySQL. |
