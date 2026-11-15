<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;

/** Requisição HTTP já normalizada (método, caminho sem /api, query, corpo JSON, cabeçalhos). */
final class Request
{
    /** @var array<string, mixed> */
    private array $atributos = [];

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $corpo
     * @param array<string, string> $cabecalhos chaves em minúsculas
     */
    public function __construct(
        public readonly string $metodo,
        public readonly string $caminho,
        private readonly array $query,
        private readonly array $corpo,
        private readonly array $cabecalhos,
    ) {
    }

    public static function capturar(): self
    {
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $caminho = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $caminho = rawurldecode($caminho);
        // Remove tudo até o primeiro segmento /api (funciona na raiz e em subcaminhos, ex.: /atelie/api/...)
        $caminho = (string) preg_replace('#^.*?/api(?=/|$)#', '', $caminho, 1);
        $caminho = '/' . trim($caminho, '/');

        $cabecalhos = [];
        foreach ($_SERVER as $chave => $valor) {
            if (str_starts_with($chave, 'HTTP_')) {
                $cabecalhos[strtolower(str_replace('_', '-', substr($chave, 5)))] = (string) $valor;
            }
        }

        $corpo = [];
        if (in_array($metodo, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $bruto = file_get_contents('php://input');
            if ($bruto !== false && trim($bruto) !== '') {
                $decodificado = json_decode($bruto, true);
                if (!is_array($decodificado) || json_last_error() !== JSON_ERROR_NONE) {
                    throw new HttpException(400, 'json_invalido', 'O corpo da requisição não é um JSON válido.');
                }
                $corpo = $decodificado;
            }
        }

        return new self($metodo, $caminho, $_GET, $corpo, $cabecalhos);
    }

    /** @return array<string, mixed> */
    public function corpo(): array
    {
        return $this->corpo;
    }

    /** @return array<string, mixed> */
    public function query(): array
    {
        return $this->query;
    }

    public function consulta(string $chave, ?string $padrao = null): ?string
    {
        $v = $this->query[$chave] ?? null;
        return is_string($v) && $v !== '' ? $v : $padrao;
    }

    /** ?ativo=1 / ?ativo=0 → true/false; ausente → null. */
    public function consultaBool(string $chave): ?bool
    {
        $v = $this->consulta($chave);
        return match ($v) {
            '1', 'true' => true,
            '0', 'false' => false,
            default => null,
        };
    }

    public function cabecalho(string $nome): ?string
    {
        return $this->cabecalhos[strtolower($nome)] ?? null;
    }

    public function definir(string $chave, mixed $valor): void
    {
        $this->atributos[$chave] = $valor;
    }

    public function atributo(string $chave): mixed
    {
        return $this->atributos[$chave] ?? null;
    }
}
