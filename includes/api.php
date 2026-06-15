<?php

declare(strict_types=1);

function json_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_method(array $methods): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if (!in_array($method, $methods, true)) {
        json_response(['erro' => 'Metodo nao permitido.'], 405);
    }
}

function request_data(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw !== false ? $raw : '', true);

        if (!is_array($data)) {
            json_response(['erro' => 'JSON invalido.'], 400);
        }

        return $data;
    }

    return $_POST;
}

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function current_user_id(): ?int
{
    ensure_session_started();

    if (empty($_SESSION['usuario'])) {
        return null;
    }

    return (int) $_SESSION['usuario'];
}

function require_login(): int
{
    $usuarioId = current_user_id();

    if ($usuarioId === null) {
        json_response(['erro' => 'Voce precisa estar logado.'], 401);
    }

    return $usuarioId;
}

function admin_user_ids(): array
{
    $raw = getenv('ADMIN_USER_IDS');

    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $ids = [];

    foreach (explode(',', $raw) as $value) {
        $id = filter_var(trim($value), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($id !== false) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function require_admin(): int
{
    $usuarioId = require_login();

    if (!in_array($usuarioId, admin_user_ids(), true)) {
        json_response(['erro' => 'Acesso negado.'], 403);
    }

    return $usuarioId;
}

function text_value(array $data, string $name, int $maxLength, bool $required = true): ?string
{
    $value = trim((string) ($data[$name] ?? ''));

    if ($value === '') {
        if ($required) {
            json_response(['erro' => sprintf('Campo %s e obrigatorio.', $name)], 400);
        }

        return null;
    }

    if (strlen($value) > $maxLength) {
        json_response(['erro' => sprintf('Campo %s muito longo.', $name)], 400);
    }

    return $value;
}

function int_value(array $data, string $name, int $min = 1, bool $required = true): ?int
{
    if (!array_key_exists($name, $data) || $data[$name] === '') {
        if ($required) {
            json_response(['erro' => sprintf('Campo %s e obrigatorio.', $name)], 400);
        }

        return null;
    }

    $value = filter_var($data[$name], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min],
    ]);

    if ($value === false) {
        json_response(['erro' => sprintf('Campo %s invalido.', $name)], 400);
    }

    return $value;
}

function bool_value(array $data, string $name, bool $default = false): bool
{
    if (!array_key_exists($name, $data)) {
        return $default;
    }

    $value = $data[$name];

    if (is_bool($value)) {
        return $value;
    }

    return in_array(strtolower((string) $value), ['1', 'true', 'sim', 'on', 'yes'], true);
}

function date_value(array $data, string $name, bool $required = false): ?string
{
    $value = text_value($data, $name, 10, $required);

    if ($value === null) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();

    if (
        $date === false ||
        ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) ||
        $date->format('Y-m-d') !== $value
    ) {
        json_response(['erro' => sprintf('Campo %s deve usar o formato YYYY-MM-DD.', $name)], 400);
    }

    return $value;
}

function id_list_value(array $data, string $name): array
{
    if (!array_key_exists($name, $data) || $data[$name] === '') {
        return [];
    }

    $rawItems = is_array($data[$name])
        ? $data[$name]
        : explode(',', (string) $data[$name]);

    $ids = [];

    foreach ($rawItems as $rawId) {
        $id = filter_var(trim((string) $rawId), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($id === false) {
            json_response(['erro' => sprintf('Campo %s contem um ID invalido.', $name)], 400);
        }

        $ids[] = $id;
    }

    return array_values(array_unique($ids));
}
