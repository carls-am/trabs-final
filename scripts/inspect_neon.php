<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function load_dotenv_file(string $path): void
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
    }
}

function env_or_null(string $name): ?string
{
    $value = getenv($name);

    if ($value === false || trim($value) === '') {
        return null;
    }

    return trim($value);
}

function database_config(): array
{
    $databaseUrl = env_or_null('DATABASE_URL') ?? env_or_null('POSTGRES_URL');

    if ($databaseUrl !== null) {
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
            'user' => isset($parts['user']) ? urldecode($parts['user']) : '',
            'password' => isset($parts['pass']) ? urldecode($parts['pass']) : '',
            'sslmode' => $query['sslmode'] ?? 'require',
            'options' => $query['options'] ?? null,
        ];
    }

    return [
        'host' => env_or_null('PGHOST') ?? env_or_null('DB_HOST'),
        'port' => env_or_null('PGPORT') ?? env_or_null('DB_PORT') ?? '5432',
        'dbname' => env_or_null('PGDATABASE') ?? env_or_null('DB_NAME'),
        'user' => env_or_null('PGUSER') ?? env_or_null('DB_USER'),
        'password' => env_or_null('PGPASSWORD') ?? env_or_null('DB_PASSWORD'),
        'sslmode' => env_or_null('PGSSLMODE') ?? env_or_null('DB_SSLMODE') ?? 'require',
        'options' => env_or_null('PGOPTIONS') ?? env_or_null('DB_OPTIONS'),
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

function require_config_keys(array $config): void
{
    foreach (['host', 'port', 'dbname', 'user', 'password'] as $key) {
        if (empty($config[$key])) {
            throw new RuntimeException('Configuracao incompleta: ' . $key);
        }
    }
}

function pdo_from_config(array $config): PDO
{
    require_config_keys($config);

    if (empty($config['options'])) {
        $config['options'] = neon_endpoint_option($config['host']);
    }

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        $config['host'],
        $config['port'],
        $config['dbname'],
        $config['sslmode'] ?? 'require'
    );

    if (!empty($config['options'])) {
        $dsn .= ';options=' . $config['options'];
    }

    return new PDO(
        $dsn,
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function fetch_all(PDO $pdo, string $sql): array
{
    return $pdo->query($sql)->fetchAll();
}

load_dotenv_file(dirname(__DIR__) . '/.env');

try {
    $config = database_config();
    $pdo = pdo_from_config($config);

    $result = [
        'connection' => $pdo->query(
            "select current_database() as database_name, current_user as user_name, current_schema() as schema_name"
        )->fetch(),
        'tables' => fetch_all(
            $pdo,
            "
            select table_schema, table_name, table_type
            from information_schema.tables
            where table_schema not in ('pg_catalog', 'information_schema')
            order by table_schema, table_name
            "
        ),
        'columns' => fetch_all(
            $pdo,
            "
            select
                table_schema,
                table_name,
                column_name,
                data_type,
                is_nullable,
                column_default
            from information_schema.columns
            where table_schema not in ('pg_catalog', 'information_schema')
            order by table_schema, table_name, ordinal_position
            "
        ),
        'constraints' => fetch_all(
            $pdo,
            "
            select
                tc.table_schema,
                tc.table_name,
                tc.constraint_name,
                tc.constraint_type,
                kcu.column_name
            from information_schema.table_constraints tc
            left join information_schema.key_column_usage kcu
                on tc.constraint_schema = kcu.constraint_schema
                and tc.constraint_name = kcu.constraint_name
                and tc.table_name = kcu.table_name
            where tc.table_schema not in ('pg_catalog', 'information_schema')
            order by tc.table_schema, tc.table_name, tc.constraint_name, kcu.ordinal_position
            "
        ),
    ];

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Falha na inspecao do Neon: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
