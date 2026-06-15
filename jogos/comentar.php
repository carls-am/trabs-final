<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/api.php';

require_method(['POST']);

$usuarioId = require_login();
$data = request_data();
$reviewId = int_value($data, 'review_id');
$comentario = text_value($data, 'comentario', 1000);

try {
    $stmt = $pdo->prepare(
        '
        INSERT INTO comentarios
            (review_id, usuario_id, comentario)
        VALUES
            (:review_id, :usuario_id, :comentario)
        RETURNING id, review_id, usuario_id, comentario, criado_em
        '
    );
    $stmt->execute([
        ':review_id' => $reviewId,
        ':usuario_id' => $usuarioId,
        ':comentario' => $comentario,
    ]);

    json_response(['data' => $stmt->fetch()], 201);
} catch (PDOException $e) {
    if ($e->getCode() === '23503') {
        json_response(['erro' => 'Review ou usuario invalido.'], 400);
    }

    error_log('Falha ao comentar review.');
    json_response(['erro' => 'Nao foi possivel comentar.'], 500);
}
