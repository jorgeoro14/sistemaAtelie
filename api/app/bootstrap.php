<?php

declare(strict_types=1);

/**
 * Autoload PSR-4 simples (namespace App\ => pasta app/).
 * Não depende do Composer, pois a hospedagem compartilhada pode não ter terminal.
 */
spl_autoload_register(static function (string $classe): void {
    $prefixo = 'App\\';
    if (!str_starts_with($classe, $prefixo)) {
        return;
    }
    $arquivo = __DIR__ . '/' . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';
    if (is_file($arquivo)) {
        require $arquivo;
    }
});
