<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\ValidationException;
use BackedEnum;

/**
 * Validação de entrada. Cada método lê um campo, registra o erro (se houver)
 * e devolve o valor já normalizado. No fim, lancarSeInvalido() gera HTTP 422
 * no formato { erro, mensagem, campos }.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $erros = [];

    /** @param array<string, mixed> $dados */
    public function __construct(private readonly array $dados, private readonly string $prefixo = '')
    {
    }

    public function tem(string $campo): bool
    {
        return array_key_exists($campo, $this->dados);
    }

    public function erro(string $campo, string $mensagem): void
    {
        $this->erros[$this->prefixo . $campo] ??= $mensagem;
    }

    /** @param array<string, string> $erros */
    public function mesclar(array $erros): void
    {
        foreach ($erros as $c => $m) {
            $this->erros[$c] ??= $m;
        }
    }

    public function temErros(): bool
    {
        return $this->erros !== [];
    }

    /** @return array<string, string> */
    public function erros(): array
    {
        return $this->erros;
    }

    public function texto(string $campo, bool $obrigatorio = true, int $min = 0, int $max = 255, string $rotulo = 'Campo'): ?string
    {
        $v = $this->dados[$campo] ?? null;
        if ($v === null || (is_string($v) && trim($v) === '')) {
            if ($obrigatorio) {
                $this->erro($campo, 'obrigatório');
            }
            return null;
        }
        if (!is_string($v)) {
            $this->erro($campo, 'texto inválido');
            return null;
        }
        $v = trim($v);
        $tam = mb_strlen($v);
        if ($tam < $min) {
            $this->erro($campo, "mínimo de {$min} caracteres");
        } elseif ($tam > $max) {
            $this->erro($campo, "máximo de {$max} caracteres");
        }
        return $v;
    }

    /** Senhas não passam por trim(). */
    public function senha(string $campo, int $min): ?string
    {
        $v = $this->dados[$campo] ?? null;
        if (!is_string($v) || $v === '') {
            $this->erro($campo, 'obrigatório');
            return null;
        }
        if (mb_strlen($v) < $min) {
            $this->erro($campo, "mínimo de {$min} caracteres");
        } elseif (strlen($v) > 72) { // limite do bcrypt
            $this->erro($campo, 'máximo de 72 caracteres');
        }
        return $v;
    }

    public function inteiro(string $campo, bool $obrigatorio = true, ?int $min = null, ?int $max = null): ?int
    {
        $v = $this->dados[$campo] ?? null;
        if ($v === null || $v === '') {
            if ($obrigatorio) {
                $this->erro($campo, 'obrigatório');
            }
            return null;
        }
        if (is_string($v) && preg_match('/^-?\d{1,15}$/', $v)) {
            $v = (int) $v;
        }
        if (!is_int($v)) {
            $this->erro($campo, 'deve ser um número inteiro');
            return null;
        }
        if ($min !== null && $v < $min) {
            $this->erro($campo, "deve ser maior ou igual a {$min}");
        } elseif ($max !== null && $v > $max) {
            $this->erro($campo, "deve ser menor ou igual a {$max}");
        }
        return $v;
    }

    public function booleano(string $campo, ?bool $padrao = null): ?bool
    {
        if (!array_key_exists($campo, $this->dados) || $this->dados[$campo] === null) {
            return $padrao;
        }
        $v = $this->dados[$campo];
        if (is_bool($v)) {
            return $v;
        }
        if ($v === 1 || $v === '1' || $v === 'true') {
            return true;
        }
        if ($v === 0 || $v === '0' || $v === 'false') {
            return false;
        }
        $this->erro($campo, 'deve ser verdadeiro ou falso');
        return $padrao;
    }

    public function data(string $campo, bool $obrigatorio = true): ?string
    {
        $v = $this->dados[$campo] ?? null;
        if ($v === null || $v === '') {
            if ($obrigatorio) {
                $this->erro($campo, 'obrigatória');
            }
            return null;
        }
        if (!is_string($v) || !self::dataValida($v)) {
            $this->erro($campo, 'data inválida (use AAAA-MM-DD)');
            return null;
        }
        return $v;
    }

    /** Aceita "YYYY-MM-DD HH:MM" e devolve "YYYY-MM-DD HH:MM:00". */
    public function dataHora(string $campo, bool $obrigatorio = true): ?string
    {
        $v = $this->dados[$campo] ?? null;
        if ($v === null || $v === '') {
            if ($obrigatorio) {
                $this->erro($campo, 'obrigatória');
            }
            return null;
        }
        if (is_string($v) && preg_match('/^(\d{4}-\d{2}-\d{2})[ T]([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', $v, $m)
            && self::dataValida($m[1])) {
            return "{$m[1]} {$m[2]}:{$m[3]}:00";
        }
        $this->erro($campo, 'data e hora inválidas (use AAAA-MM-DD HH:MM)');
        return null;
    }

    /**
     * @template T of BackedEnum
     * @param class-string<T> $enum
     * @return T|null
     */
    public function opcao(string $campo, string $enum, bool $obrigatorio = true, ?BackedEnum $padrao = null): ?BackedEnum
    {
        $v = $this->dados[$campo] ?? null;
        if ($v === null || $v === '') {
            if ($obrigatorio && $padrao === null) {
                $this->erro($campo, 'obrigatório');
            }
            return $padrao;
        }
        $e = is_string($v) ? $enum::tryFrom($v) : null;
        if ($e === null) {
            $validos = implode(', ', array_map(static fn($c) => $c->value, $enum::cases()));
            $this->erro($campo, "valor inválido (use: {$validos})");
            return $padrao;
        }
        return $e;
    }

    /** Telefone só com dígitos (RN14): 10 ou 11 dígitos com DDD. */
    public function telefone(string $campo, bool $obrigatorio = true): ?string
    {
        $v = $this->dados[$campo] ?? null;
        if ($v === null || (is_string($v) && trim($v) === '')) {
            if ($obrigatorio) {
                $this->erro($campo, 'obrigatório');
            }
            return null;
        }
        if (!is_string($v) && !is_int($v)) {
            $this->erro($campo, 'inválido');
            return null;
        }
        $digitos = preg_replace('/\D+/', '', (string) $v);
        if (!preg_match('/^[1-9]{2}9?\d{8}$/', $digitos)) {
            $this->erro($campo, 'inválido (informe DDD + número, 10 ou 11 dígitos)');
            return null;
        }
        return $digitos;
    }

    public function lancarSeInvalido(): void
    {
        if ($this->erros !== []) {
            throw new ValidationException($this->erros);
        }
    }

    public static function dataValida(string $data): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data, $m)) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}
