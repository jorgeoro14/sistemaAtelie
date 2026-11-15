<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * Conexão PDO. Sempre usada com prepared statements nativos
 * (EMULATE_PREPARES desligado): texto do usuário nunca é concatenado em SQL.
 */
final class Database
{
    private ?PDO $pdo = null;
    private int $nivelTransacao = 0;

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                Config::obter('db.host'),
                (int) Config::obter('db.porta', 3306),
                Config::obter('db.nome'),
                Config::obter('db.charset', 'utf8mb4')
            );
            $this->pdo = new PDO($dsn, (string) Config::obter('db.usuario'), (string) Config::obter('db.senha'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
            // Fuso do MySQL alinhado com o do PHP (RN: evita OS com data errada à noite)
            $this->pdo->exec("SET time_zone = '-03:00'");
        }
        return $this->pdo;
    }

    /** Prepara e executa, ligando cada parâmetro com o tipo certo (LIMIT/OFFSET exigem inteiro). */
    private function rodar(string $sql, array $params): \PDOStatement
    {
        $st = $this->pdo()->prepare($sql);
        foreach ($params as $nome => $valor) {
            $tipo = match (true) {
                is_int($valor) => PDO::PARAM_INT,
                is_bool($valor) => PDO::PARAM_BOOL,
                $valor === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $st->bindValue(':' . ltrim((string) $nome, ':'), $valor, $tipo);
        }
        $st->execute();
        return $st;
    }

    /** @return array<int, array<string, mixed>> */
    public function todos(string $sql, array $params = []): array
    {
        return $this->rodar($sql, $params)->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function um(string $sql, array $params = []): ?array
    {
        $linha = $this->rodar($sql, $params)->fetch();
        return $linha === false ? null : $linha;
    }

    public function escalar(string $sql, array $params = []): mixed
    {
        $v = $this->rodar($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** Executa INSERT/UPDATE/DELETE e devolve as linhas afetadas. */
    public function executar(string $sql, array $params = []): int
    {
        return $this->rodar($sql, $params)->rowCount();
    }

    public function ultimoId(): int
    {
        return (int) $this->pdo()->lastInsertId();
    }

    /**
     * Executa o callback dentro de uma transação: grava tudo ou nada.
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public function transacao(callable $fn): mixed
    {
        $externa = $this->nivelTransacao === 0;
        $this->nivelTransacao++;
        if ($externa) {
            $this->pdo()->beginTransaction();
        }
        try {
            $resultado = $fn();
            if ($externa) {
                $this->pdo()->commit();
            }
            return $resultado;
        } catch (Throwable $e) {
            if ($externa && $this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }
            throw $e;
        } finally {
            $this->nivelTransacao--;
        }
    }
}
