<?php

declare(strict_types=1);

final class ApiError extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 400)
    {
        parent::__construct($message);
    }
}

function respond(string $status = 'ok', string $message = '', mixed $data = [], int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['status' => $status, 'mensagem' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function method(string $expected): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $expected) {
        header('Allow: ' . $expected);
        throw new ApiError('Método não permitido.', 405);
    }
}

function request_data(): array
{
    if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        try {
            $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ApiError('JSON inválido.');
        }
        if (!is_array($data) || array_is_list($data) && $data !== []) {
            throw new ApiError('Envie um objeto JSON.');
        }
        return $data;
    }
    return $_POST;
}

function text_field(array $data, string $key, int $max, bool $required = true): string
{
    $value = $data[$key] ?? '';
    if (!is_string($value)) {
        throw new ApiError('Campo inválido: ' . $key);
    }
    $value = trim($value);
    if (($required && $value === '') || preg_match_all('/./us', $value) > $max || !preg_match('//u', $value)) {
        throw new ApiError('Campo inválido: ' . $key);
    }
    return $value;
}

function number_field(array $data, string $key, int $scale = 2, bool $positive = false): float
{
    $value = $data[$key] ?? null;
    if (!is_scalar($value) || is_bool($value) || !is_numeric($value)) {
        throw new ApiError('Número inválido: ' . $key);
    }
    $number = (float) $value;
    $max = 10 ** (12 - $scale) - 10 ** (-$scale);
    if (!is_finite($number) || $number < 0 || ($positive && $number <= 0) || $number > $max || abs($number - round($number, $scale)) > 0.0000001) {
        throw new ApiError('Valor fora do limite: ' . $key);
    }
    return $number;
}

function record_id(mixed $value): int
{
    if (is_bool($value) || !is_scalar($value) || !preg_match('/^[1-9][0-9]*$/', (string) $value) || (float) $value > 4294967295) {
        throw new ApiError('ID inválido.');
    }
    return (int) $value;
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start(['cookie_httponly' => true, 'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
    }
}

function require_user(): array
{
    start_session();
    if (!isset($_SESSION['usuario'])) {
        throw new ApiError('Faça login para continuar.', 401);
    }
    $user = $_SESSION['usuario'];
    session_write_close();
    return $user;
}
