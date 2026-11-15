<?php

declare(strict_types=1);

namespace App\Core;

/** Registra erros em arquivo (nunca na resposta HTTP). */
final class Logger
{
    public static function erro(string $mensagem): void
    {
        $arquivo = Config::obter('app.log');
        $linha = '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . PHP_EOL;
        if (is_string($arquivo) && $arquivo !== '') {
            $dir = dirname($arquivo);
            if (!is_dir($dir)) {
                @mkdir($dir, 0750, true);
            }
            if (@error_log($linha, 3, $arquivo)) {
                return;
            }
        }
        error_log($mensagem);
    }
}
