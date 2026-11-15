<?php

declare(strict_types=1);

/**
 * Ponto de entrada único da API (.htaccess redireciona /api/* para cá).
 */
require __DIR__ . '/app/bootstrap.php';

\App\Core\Application::criar()->executar();
