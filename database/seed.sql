-- =====================================================================
-- seed.sql — dados iniciais
-- ATENÇÃO: o usuário admin/1234 serve SÓ para desenvolvimento.
-- O primeiro login obriga trocar a senha (trocar_senha = 1).
-- Em produção: crie o usuário real e desative/troque o admin (tarefa B7.3).
-- =====================================================================
SET NAMES utf8mb4;

INSERT INTO usuarios (nome, usuario, senha_hash, perfil, ativo, trocar_senha)
VALUES ('Administrador', 'admin', '$2y$10$WZ.KogRytNZ29bfIv09neOBStnv7tw868umgZufx9s5QlUMNDggfO', 'admin', 1, 1);

INSERT INTO servicos (nome, categoria, valor_padrao_centavos) VALUES
 ('Barra de calça',        'conserto',   3500),
 ('Ajuste de cintura',     'ajuste',     6000),
 ('Vestido sob medida',    'sob_medida', 25000),
 ('Troca de zíper',        'conserto',   4500),
 ('Barra de vestido',      'conserto',   4000),
 ('Ajuste de manga',       'ajuste',     3000);
