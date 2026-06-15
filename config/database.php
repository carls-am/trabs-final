<?php

declare(strict_types=1);

function env_value(array $names): ?string
{
    foreach ($names as $name) {
        $value = getenv($name);

        if ($value !== false && trim($value) !== '') {
            return trim($value);
        }
    }

    return null;
}

function load_dotenv(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if ($name === '' || getenv($name) !== false) {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function load_database_config(): array
{
    load_dotenv(dirname(__DIR__) . '/.env');

    $config = [
        'database_url' => env_value(['DATABASE_URL', 'POSTGRES_URL']),
        'host' => env_value(['PGHOST', 'DB_HOST']),
        'port' => env_value(['PGPORT', 'DB_PORT']) ?? '5432',
        'dbname' => env_value(['PGDATABASE', 'DB_NAME']),
        'user' => env_value(['PGUSER', 'DB_USER']),
        'password' => env_value(['PGPASSWORD', 'DB_PASSWORD']),
        'sslmode' => env_value(['PGSSLMODE', 'DB_SSLMODE']) ?? 'require',
    ];

    $localConfigPath = __DIR__ . '/local.php';

    if (is_file($localConfigPath)) {
        $localConfig = require $localConfigPath;

        if (is_array($localConfig)) {
            $config = array_merge($config, $localConfig);
        }
    }

    return $config;
}

function database_config_from_url(string $databaseUrl): array
{
    $parts = parse_url($databaseUrl);

    if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
        throw new RuntimeException('DATABASE_URL invalida.');
    }

    $query = [];

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    return [
        'host' => $parts['host'],
        'port' => isset($parts['port']) ? (string) $parts['port'] : '5432',
        'dbname' => ltrim(urldecode($parts['path']), '/'),
        'user' => isset($parts['user']) ? urldecode($parts['user']) : null,
        'password' => isset($parts['pass']) ? urldecode($parts['pass']) : null,
        'sslmode' => $query['sslmode'] ?? 'require',
        'options' => $query['options'] ?? null,
    ];
}

function neon_endpoint_option(?string $host): ?string
{
    if ($host === null || $host === '') {
        return null;
    }

    $endpointId = explode('.', $host)[0] ?? '';

    if (!str_starts_with($endpointId, 'ep-')) {
        return null;
    }

    return 'endpoint=' . $endpointId;
}

function normalize_database_config(array $config): array
{
    if (!empty($config['database_url'])) {
        $config = array_merge($config, database_config_from_url($config['database_url']));
    }

    foreach (['host', 'port', 'dbname', 'user', 'password'] as $requiredKey) {
        if (empty($config[$requiredKey])) {
            throw new RuntimeException('Configuracao do banco incompleta.');
        }
    }

    if (empty($config['options'])) {
        $config['options'] = neon_endpoint_option($config['host']);
    }

    return $config;
}

function build_pgsql_dsn(array $config): string
{
    $sslmode = $config['sslmode'] ?? 'require';

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        $config['host'],
        $config['port'],
        $config['dbname'],
        $sslmode
    );

    if (!empty($config['options'])) {
        $dsn .= ';options=' . $config['options'];
    }

    return $dsn;
}

$databaseConfig = normalize_database_config(load_database_config());

try {
    $pdo = new PDO(
        build_pgsql_dsn($databaseConfig),
        $databaseConfig['user'],
        $databaseConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Throwable $e) {
    error_log('Falha ao conectar ao banco de dados.');
    http_response_code(500);
    exit('Erro ao conectar ao banco de dados.');
}
