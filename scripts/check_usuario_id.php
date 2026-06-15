<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

try {
    $columnStmt = $pdo->prepare(
        "
        select
            column_name,
            data_type,
            column_default,
            is_identity,
            identity_generation
        from information_schema.columns
        where table_schema = 'public'
            and table_name = 'usuarios'
            and column_name = 'id'
        limit 1
        "
    );
    $columnStmt->execute();

    $sequenceStmt = $pdo->query(
        "select pg_get_serial_sequence('public.usuarios', 'id') as serial_sequence"
    );

    echo json_encode(
        [
            'column' => $columnStmt->fetch(),
            'sequence' => $sequenceStmt->fetch(),
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Falha ao verificar usuarios.id.' . PHP_EOL);
    exit(1);
}
