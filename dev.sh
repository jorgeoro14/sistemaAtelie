#!/usr/bin/env bash
# Sobe o ambiente de DESENVOLVIMENTO completo: MySQL (porta 3308), API PHP (8000) e front (5173).
# Uso:  ./dev.sh        (Ctrl+C encerra tudo)
# Primeira vez: ./dev.sh --instalar  (cria o banco, o usuário e importa schema + seed)
set -euo pipefail
RAIZ="$(cd "$(dirname "$0")" && pwd)"
SOCKET=/tmp/bella-mysql.sock
DADOS="$RAIZ/.mysql/data"
SENHA_DB="dev-atelie-123"   # só desenvolvimento; igual ao api/config/config.php local

mysql_root() { mysql -u root -S "$SOCKET" "$@"; }

if [[ "${1:-}" == "--instalar" ]]; then
  mkdir -p "$RAIZ/.mysql" "$RAIZ/storage/logs"
  [[ -d "$DADOS" ]] || mysqld --no-defaults --initialize-insecure --datadir="$DADOS"
fi

if ! mysql_root -e "select 1" >/dev/null 2>&1; then
  echo ">> iniciando MySQL na porta 3308"
  mysqld --no-defaults --datadir="$DADOS" --port=3308 --bind-address=127.0.0.1 \
    --socket="$SOCKET" --mysqlx=OFF --character-set-server=utf8mb4 >"$RAIZ/.mysql/server.log" 2>&1 &
  for _ in $(seq 1 30); do mysql_root -e "select 1" >/dev/null 2>&1 && break; sleep 1; done
fi

if [[ "${1:-}" == "--instalar" ]]; then
  mysql_root -e "CREATE DATABASE IF NOT EXISTS atelie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER IF NOT EXISTS 'atelie'@'127.0.0.1' IDENTIFIED BY '$SENHA_DB';
    CREATE USER IF NOT EXISTS 'atelie'@'localhost' IDENTIFIED BY '$SENHA_DB';
    GRANT ALL ON atelie.* TO 'atelie'@'127.0.0.1'; GRANT ALL ON atelie.* TO 'atelie'@'localhost';"
  mysql_root atelie < "$RAIZ/database/schema.sql"
  mysql_root atelie < "$RAIZ/database/seed.sql"
  if [[ ! -f "$RAIZ/api/config/config.php" ]]; then
    sed -e "s/'porta'   => 3306/'porta'   => 3308/" -e "s/troque-esta-senha/$SENHA_DB/" \
      "$RAIZ/api/config/config.example.php" > "$RAIZ/api/config/config.php"
  fi
  echo ">> banco criado. Login: admin / 1234 (troca obrigatória no primeiro acesso)"
fi

cleanup() { kill "${PID_PHP:-}" "${PID_VITE:-}" 2>/dev/null || true; }
trap cleanup EXIT INT TERM

echo ">> API em http://localhost:8000"
php -S localhost:8000 -t "$RAIZ/api" "$RAIZ/api/index.php" >"$RAIZ/.mysql/php.log" 2>&1 &
PID_PHP=$!

echo ">> Front em http://localhost:5173"
cd "$RAIZ/web" && { [[ -d node_modules ]] || npm install; } && npm run dev &
PID_VITE=$!
wait
