<?php

declare(strict_types=1);

/**
 * Script de instalação (linha de comando):  php api/bin/instalar.php [usuario] [senha]
 * Cria o administrador inicial se ainda não existir nenhum usuário.
 * Sem argumentos, cria admin/1234 (SÓ desenvolvimento) com troca de senha obrigatória.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;

Config::carregar();
$db = new Database();

$existentes = (int) $db->escalar('SELECT COUNT(*) FROM usuarios');
if ($existentes > 0) {
    fwrite(STDERR, "Já existem usuários cadastrados. Nada foi feito.\n");
    exit(1);
}

$usuario = $argv[1] ?? 'admin';
$senha = $argv[2] ?? '1234';
if (Config::ehProducao() && strlen($senha) < 8) {
    fwrite(STDERR, "Em produção a senha precisa ter pelo menos 8 caracteres.\n");
    exit(1);
}

$db->executar(
    "INSERT INTO usuarios (nome, usuario, senha_hash, perfil, ativo, trocar_senha)
     VALUES ('Administrador', :u, :h, 'admin', 1, 1)",
    ['u' => $usuario, 'h' => password_hash($senha, PASSWORD_DEFAULT)]
);
echo "Administrador '{$usuario}' criado. Será obrigatório trocar a senha no primeiro login.\n";
