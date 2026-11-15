#!/usr/bin/env bash
# Gera o pacote de PRODUÇÃO para hospedagem compartilhada (cPanel/Hostinger) em uma subpasta.
# Uso:  ./empacotar.sh [subpasta]      (padrão: atelie  →  https://seudominio/atelie)
# Saída (pasta deploy/):
#   atelie-hostinger.zip     → conteúdo para public_html/<subpasta>/
#   atelie-banco.sql         → importar no phpMyAdmin (tabelas + usuário admin com senha temporária + serviços)
#   config-atelie.php        → modelo do arquivo de configuração (vai FORA do public_html)
#   SENHA-TEMPORARIA.txt     → senha do primeiro login (troca obrigatória)
set -euo pipefail
RAIZ="$(cd "$(dirname "$0")" && pwd)"
SUB="${1:-atelie}"
SAIDA="$RAIZ/deploy"
PKG="$SAIDA/$SUB"

command -v php >/dev/null || { echo "php não encontrado"; exit 1; }
command -v openssl >/dev/null || { echo "openssl não encontrado"; exit 1; }

echo ">> build do front (base /$SUB/)"
( cd "$RAIZ/web" && { [[ -d node_modules ]] || npm install; } && VITE_BASE="/$SUB/" npm run build >/dev/null )

rm -rf "$SAIDA"
mkdir -p "$PKG"
cp -R "$RAIZ/web/dist/." "$PKG/"

echo ">> copiando a API"
mkdir -p "$PKG/api"
rsync -a \
  --exclude 'config/config.php' --exclude 'bin/teste_api.py' --exclude 'requests.http' \
  --exclude '.DS_Store' "$RAIZ/api/" "$PKG/api/"

cat > "$PKG/.htaccess" <<EOF
# Bella Costura Gestão — roda em /$SUB/ (HTTPS, API em /$SUB/api, front React como SPA)
Options -Indexes
RewriteEngine On
RewriteBase /$SUB/

# Força HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# API (o próprio index.php já está pronto; o resto de /api/* vai para ele)
RewriteRule ^api/index\.php\$ - [L]
RewriteRule ^api(/.*)?\$ api/index.php [L,QSA]

# Front (SPA): qualquer rota que não seja arquivo/pasta real cai no index.html
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.html [L]

<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "same-origin"
    <FilesMatch "index\.html\$">
        Header set Cache-Control "no-cache"
    </FilesMatch>
</IfModule>
EOF

echo ">> gerando senha temporária e banco de produção"
SENHA="$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 14)"
HASH="$(SENHA="$SENHA" php -r 'echo password_hash(getenv("SENHA"), PASSWORD_BCRYPT);')"
{
  cat "$RAIZ/database/schema.sql"
  echo
  echo "-- ===== dados iniciais de PRODUÇÃO ====="
  echo "SET NAMES utf8mb4;"
  echo "INSERT INTO usuarios (nome, usuario, senha_hash, perfil, ativo, trocar_senha)"
  echo "VALUES ('Administrador', 'admin', '$HASH', 'admin', 1, 1);"
  echo
  sed -n '/^INSERT INTO servicos/,$p' "$RAIZ/database/seed.sql"
} > "$SAIDA/atelie-banco.sql"

cat > "$SAIDA/SENHA-TEMPORARIA.txt" <<EOF
Primeiro acesso ao sistema (https://SEU-DOMINIO/$SUB)

Usuário: admin
Senha temporária: $SENHA

O sistema obriga a trocar a senha no primeiro login. Depois disso, apague este arquivo.
EOF
chmod 600 "$SAIDA/SENHA-TEMPORARIA.txt"

cat > "$SAIDA/config-atelie.php" <<EOF
<?php
// Salve este arquivo como  /home/SEU_USUARIO/config/atelie.php  (FORA de public_html).
// Preencha os campos marcados com TROQUE.
return [
    'app' => [
        'ambiente' => 'producao',
        'fuso'     => 'America/Sao_Paulo',
        'log'      => __DIR__ . '/../logs/atelie-api.log',   // /home/SEU_USUARIO/logs/ (fora do site)
    ],
    'db' => [
        'host'    => 'localhost',
        'porta'   => 3306,
        'nome'    => 'TROQUE_usuario_atelie',     // nome completo do banco criado no cPanel (com prefixo)
        'usuario' => 'TROQUE_usuario_atelie',     // usuário do banco (com prefixo)
        'senha'   => 'TROQUE-PELA-SENHA-DO-BANCO',
        'charset' => 'utf8mb4',
    ],
    'sessao' => [
        'nome'             => 'ATELIESESSID',
        'duracao_segundos' => 8 * 3600,
        'cookie_secure'    => true,
        'cookie_path'      => '/$SUB',
    ],
    'seguranca' => [
        'max_tentativas_login' => 5,
        'bloqueio_minutos'     => 15,
        'senha_minima'         => 8,
    ],
];
EOF

echo ">> compactando"
( cd "$PKG" && zip -qr "$SAIDA/atelie-hostinger.zip" . -x '*.DS_Store' )
echo
echo "Pronto. Arquivos em $SAIDA:"
ls -1 "$SAIDA"
