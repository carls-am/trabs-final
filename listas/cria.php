<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/api.php';

require_method(['POST']);

$usuarioId = require_login();
$data = request_data();
$nome = text_value($data, 'nome', 120);
$descricao = text_value($data, 'descricao', 1000, false);
$privada = bool_value($data, 'privada', false);
$jogosIds = id_list_value($data, 'jogos_ids');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        '
        INSERT INTO listas
            (usuario_id, nome, descricao, privada)
        VALUES
            (:usuario_id, :nome, :descricao, :privada)
        RETURNING id, usuario_id, nome, descricao, privada, criada_em
        '
    );
    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':nome' => $nome,
        ':descricao' => $descricao,
        ':privada' => $privada ? 'true' : 'false',
    ]);
    $lista = $stmt->fetch();

    if ($jogosIds !== []) {
        $itemStmt = $pdo->prepare(
            '
            INSERT INTO lista_jogos (lista_id, jogo_id)
            VALUES (:lista_id, :jogo_id)
            ON CONFLICT DO NOTHING
            '
        );

        foreach ($jogosIds as $jogoId) {
            $itemStmt->execute([
                ':lista_id' => $lista['id'],
                ':jogo_id' => $jogoId,
            ]);
        }
    }

    $pdo->commit();

    json_response(['data' => $lista], 201);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getCode() === '23503') {
        json_response(['erro' => 'Usuario ou jogo invalido.'], 400);
    }

    error_log('Falha ao criar lista.');
    json_response(['erro' => 'Nao foi possivel criar a lista.'], 500);
}
