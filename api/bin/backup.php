<?php

declare(strict_types=1);

/**
 * Backup do banco em arquivo .sql (B6.4).  Uso:  php api/bin/backup.php [pasta_destino]
 * Funciona sem mysqldump (hospedagem compartilhada). Agende no cron da Hostinger (semanal).
 * Restaurar:  mysql -u USUARIO -p NOME_DO_BANCO < backup.sql   (ou "Importar" no phpMyAdmin)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;

Config::carregar();
$pdo = (new Database())->pdo();

$destino = rtrim($argv[1] ?? (__DIR__ . '/../../../backups'), '/');
if (!is_dir($destino) && !mkdir($destino, 0750, true)) {
    fwrite(STDERR, "Não foi possível criar a pasta {$destino}\n");
    exit(1);
}
$arquivo = $destino . '/atelie_' . date('Y-m-d_His') . '.sql';
$f = fopen($arquivo, 'w');
fwrite($f, "-- Backup Bella Costura Gestão em " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

$tabelas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tabelas as $t) {
    $create = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_NUM)[1];
    fwrite($f, "DROP TABLE IF EXISTS `{$t}`;\n{$create};\n\n");
    $st = $pdo->query("SELECT * FROM `{$t}`");
    while ($linha = $st->fetch(PDO::FETCH_ASSOC)) {
        $vals = array_map(static fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $linha);
        fwrite($f, "INSERT INTO `{$t}` VALUES (" . implode(',', $vals) . ");\n");
    }
    fwrite($f, "\n");
}
fwrite($f, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($f);
chmod($arquivo, 0640);
echo "Backup gravado em {$arquivo}\n";
