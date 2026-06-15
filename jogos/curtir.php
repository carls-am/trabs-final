<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/api.php';

require_method(['POST']);

$usuarioId = require_login();
$data = request_data();
$reviewId = int_value($data, 'review_id');
$acao = text_value($data, 'acao', 20, false) ?? 'toggle';

if (!in_array($acao, ['toggle', 'curtir', 'descurtir'], true)) {
    json_response(['erro' => 'Acao invalida.'], 400);
}

try {
    $pdo->beginTransaction();

    $existsStmt = $pdo->prepare('SELECT 1 FROM reviews WHERE id = :review_id LIMIT 1');
    $existsStmt->execute([':review_id' => $reviewId]);

    if (!$existsStmt->fetchColumn()) {
        $pdo->rollBack();
        json_response(['erro' => 'Review nao encontrada.'], 404);
    }

    if ($acao === 'descurtir') {
        $stmt = $pdo->prepare(
            'DELETE FROM review_likes WHERE usuario_id = :usuario_id AND review_id = :review_id'
        );
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':review_id' => $reviewId,
        ]);
        $curtido = false;
    } elseif ($acao === 'curtir') {
        $stmt = $pdo->prepare(
            '
            INSERT INTO review_likes (usuario_id, review_id)
            VALUES (:usuario_id, :review_id)
            ON CONFLICT DO NOTHING
            '
        );
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':review_id' => $reviewId,
        ]);
        $curtido = true;
    } else {
        $stmt = $pdo->prepare(
            'DELETE FROM review_likes WHERE usuario_id = :usuario_id AND review_id = :review_id'
        );
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':review_id' => $reviewId,
        ]);

        if ($stmt->rowCount() > 0) {
            $curtido = false;
        } else {
            $stmt = $pdo->prepare(
                '
                INSERT INTO review_likes (usuario_id, review_id)
                VALUES (:usuario_id, :review_id)
                '
            );
            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':review_id' => $reviewId,
            ]);
            $curtido = true;
        }
    }

    $countStmt = $pdo->prepare(
        'SELECT COUNT(1) AS total FROM review_likes WHERE review_id = :review_id'
    );
    $countStmt->execute([':review_id' => $reviewId]);
    $total = (int) $countStmt->fetch()['total'];

    $pdo->commit();

    json_response([
        'data' => [
            'review_id' => $reviewId,
            'curtido' => $curtido,
            'total_likes' => $total,
        ],
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getCode() === '23503') {
        json_response(['erro' => 'Review ou usuario invalido.'], 400);
    }

    error_log('Falha ao curtir review.');
    json_response(['erro' => 'Nao foi possivel atualizar a curtida.'], 500);
}
