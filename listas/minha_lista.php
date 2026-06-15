<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/api.php';

require_method(['GET']);

$usuarioId = require_login();

try {
    $stmt = $pdo->prepare(
        '
        SELECT
            l.id,
            l.nome,
            l.descricao,
            l.privada,
            l.criada_em,
            COUNT(lj.jogo_id) AS total_jogos
        FROM listas l
        LEFT JOIN lista_jogos lj ON lj.lista_id = l.id
        WHERE l.usuario_id = :usuario_id
        GROUP BY l.id, l.nome, l.descricao, l.privada, l.criada_em
        ORDER BY l.criada_em DESC
        '
    );
    $stmt->execute([':usuario_id' => $usuarioId]);

    json_response(['data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    error_log('Falha ao listar listas do usuario.');
    json_response(['erro' => 'Nao foi possivel listar suas listas.'], 500);
}
