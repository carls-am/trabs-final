<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

$file = $argv[1] ?? '';

if ($file === '') {
    fwrite(STDERR, 'Uso: php scripts/apply_migration.php migrations/arquivo.sql' . PHP_EOL);
    exit(1);
}

$path = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . $file);
$migrationsDir = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'migrations');

if ($path === false || $migrationsDir === false || !str_starts_with($path, $migrationsDir)) {
    fwrite(STDERR, 'Migration invalida.' . PHP_EOL);
    exit(1);
}

$sql = file_get_contents($path);

if ($sql === false || trim($sql) === '') {
    fwrite(STDERR, 'Migration vazia ou ilegivel.' . PHP_EOL);
    exit(1);
}

try {
    $pdo->exec($sql);
    echo 'Migration aplicada: ' . $file . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Falha ao aplicar migration.' . PHP_EOL);
    exit(1);
}
