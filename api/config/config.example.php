<?php

/**
 * Copie este arquivo para config.php e ajuste os valores.
 * config.php NUNCA vai para o Git (está no .gitignore).
 *
 * Em produção (Hostinger), o ideal é guardar o config.php FORA de public_html
 * (ex.: /home/usuario/config/atelie/config.php) e apontar a variável de ambiente
 * ATELIE_CONFIG para ele, ou simplesmente colocá-lo em ../config/config.php,
 * um nível acima de public_html. A busca segue esta ordem:
 *   1) variável de ambiente ATELIE_CONFIG
 *   2) <raiz do site>/../config/atelie.php   (fora de public_html)
 *   3) api/config/config.php                 (protegido por .htaccess)
 */
return [
    'app' => [
        'ambiente' => 'desenvolvimento',      // 'desenvolvimento' | 'producao'
        'fuso'     => 'America/Sao_Paulo',
        'log'      => __DIR__ . '/../../storage/logs/api.log', // fora da pasta pública em produção
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'porta'   => 3306,
        'nome'    => 'atelie',
        'usuario' => 'atelie',
        'senha'   => 'troque-esta-senha',
        'charset' => 'utf8mb4',
    ],
    'sessao' => [
        'nome'            => 'ATELIESESSID',
        'duracao_segundos' => 8 * 3600,       // expira após 8h sem uso
        'cookie_secure'   => 'auto',          // 'auto' | true | false  (produção: true)
        'cookie_path'     => '/',             // em subpasta, ex.: '/atelie' (isola o cookie de outros sites do domínio)
    ],
    'seguranca' => [
        'max_tentativas_login'   => 5,
        'bloqueio_minutos'       => 15,
        'senha_minima'           => 8,
    ],
];
