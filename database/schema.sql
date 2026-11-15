-- =====================================================================
-- Bella Costura Gestão — schema.sql
-- MySQL 8 / MariaDB 10.4+  ·  charset utf8mb4
-- Sete tabelas (a tabela "pagamentos" foi eliminada pela decisão da
-- seção 10 dos requisitos: pagamento único, gravado na própria OS).
-- Valores em dinheiro: inteiros em centavos.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '-03:00'; -- America/Sao_Paulo (sem horário de verão desde 2019)

CREATE TABLE IF NOT EXISTS usuarios (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome              VARCHAR(120) NOT NULL,
    usuario           VARCHAR(60)  NOT NULL,
    senha_hash        VARCHAR(255) NOT NULL,
    perfil            ENUM('admin') NOT NULL DEFAULT 'admin',
    ativo             TINYINT(1)   NOT NULL DEFAULT 1,
    trocar_senha      TINYINT(1)   NOT NULL DEFAULT 1,
    tentativas_login  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_ate     DATETIME NULL,
    criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    -- collation _ci: "Maria" e "maria" são o mesmo usuário (RN17)
    UNIQUE KEY uq_usuarios_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clientes (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome           VARCHAR(120) NOT NULL,
    telefone       VARCHAR(11)  NOT NULL,           -- só dígitos com DDD
    observacoes    TEXT NULL,
    ativo          TINYINT(1) NOT NULL DEFAULT 1,
    criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_clientes_nome (nome),
    KEY idx_clientes_telefone (telefone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS costureiras (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome           VARCHAR(120) NOT NULL,
    telefone       VARCHAR(11) NULL,
    ativa          TINYINT(1) NOT NULL DEFAULT 1,
    criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicos (
    id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome                   VARCHAR(120) NOT NULL,
    categoria              ENUM('conserto','sob_medida','ajuste','outro') NOT NULL DEFAULT 'outro',
    valor_padrao_centavos  INT UNSIGNED NULL,
    ativo                  TINYINT(1) NOT NULL DEFAULT 1,
    criado_em              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ordens_servico (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero            INT UNSIGNED NOT NULL,                  -- RN15: sequencial, nunca reaproveitado
    cliente_id        INT UNSIGNED NOT NULL,
    costureira_id     INT UNSIGNED NULL,
    prioridade        ENUM('normal','alta','urgente') NOT NULL DEFAULT 'normal',
    status            ENUM('recebido','em_andamento','pronto','entregue','cancelado') NOT NULL DEFAULT 'recebido',
    data_recebimento  DATE NOT NULL,
    data_prevista     DATE NOT NULL,
    data_pronto       DATE NULL,
    data_entrega      DATE NULL,
    observacoes       TEXT NULL,
    total_centavos    INT UNSIGNED NOT NULL DEFAULT 0,        -- RN03: soma dos itens, calculada no back
    forma_pagamento   ENUM('dinheiro','pix','cartao_debito','cartao_credito','outro') NULL,
    data_pagamento    DATE NULL,                              -- pago = data_pagamento preenchida
    criado_por        INT UNSIGNED NOT NULL,                  -- RN18
    criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_os_numero (numero),
    KEY idx_os_status (status),
    KEY idx_os_data_prevista (data_prevista),
    KEY idx_os_data_recebimento (data_recebimento),
    KEY idx_os_data_pagamento (data_pagamento),
    KEY idx_os_costureira (costureira_id),
    KEY idx_os_cliente (cliente_id),
    CONSTRAINT fk_os_cliente    FOREIGN KEY (cliente_id)    REFERENCES clientes (id)    ON DELETE RESTRICT,
    CONSTRAINT fk_os_costureira FOREIGN KEY (costureira_id) REFERENCES costureiras (id) ON DELETE RESTRICT,
    CONSTRAINT fk_os_criado_por FOREIGN KEY (criado_por)    REFERENCES usuarios (id)    ON DELETE RESTRICT,
    CONSTRAINT ck_os_datas CHECK (data_prevista >= data_recebimento)  -- RN06
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_itens (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    os_id           INT UNSIGNED NOT NULL,
    servico_id      INT UNSIGNED NOT NULL,
    descricao       VARCHAR(255) NOT NULL,
    valor_centavos  INT UNSIGNED NOT NULL,                    -- congelado ao salvar (decisão 7)
    ordem           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_itens_os (os_id),
    CONSTRAINT fk_itens_os      FOREIGN KEY (os_id)      REFERENCES ordens_servico (id) ON DELETE CASCADE,
    CONSTRAINT fk_itens_servico FOREIGN KEY (servico_id) REFERENCES servicos (id)       ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agendamentos (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cliente_id     INT UNSIGNED NOT NULL,
    os_id          INT UNSIGNED NULL,
    data_hora      DATETIME NOT NULL,
    tipo           ENUM('prova','medidas','retirada','outro') NOT NULL DEFAULT 'prova',
    observacao     VARCHAR(500) NULL,
    status         ENUM('marcado','realizado','cancelado') NOT NULL DEFAULT 'marcado',
    criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_agendamentos_data_hora (data_hora),
    CONSTRAINT fk_agend_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id)       ON DELETE RESTRICT,
    CONSTRAINT fk_agend_os      FOREIGN KEY (os_id)      REFERENCES ordens_servico (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
