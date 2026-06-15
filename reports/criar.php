<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/api.php';

require_method(['POST']);

function report_content_exists(PDO $pdo, string $tipoConteudo, int $conteudoId, int $usuarioId): bool
{
    $queries = [
        'jogo' => 'SELECT 1 FROM jogos WHERE id = :id LIMIT 1',
        'review' => 'SELECT 1 FROM reviews WHERE id = :id LIMIT 1',
        'comentario' => 'SELECT 1 FROM comentarios WHERE id = :id LIMIT 1',
        'usuario' => 'SELECT 1 FROM usuarios WHERE id = :id LIMIT 1',
        'lista' => '
            SELECT 1
            FROM listas
            WHERE id = :id
                AND (privada = false OR usuario_id = :usuario_id)
            LIMIT 1
        ',
    ];

    if (!isset($queries[$tipoConteudo])) {
        return false;
    }

    $stmt = $pdo->prepare($queries[$tipoConteudo]);
    $stmt->bindValue(':id', $conteudoId, PDO::PARAM_INT);

    if ($tipoConteudo === 'lista') {
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    }

    $stmt->execute();

    return (bool) $stmt->fetchColumn();
}

$usuarioId = require_login();
$data = request_data();
$tipoConteudo = text_value($data, 'tipo_conteudo', 30);
$conteudoId = int_value($data, 'conteudo_id');
$motivo = text_value($data, 'motivo', 1000);
$tiposPermitidos = ['jogo', 'review', 'comentario', 'lista', 'usuario'];

if (!in_array($tipoConteudo, $tiposPermitidos, true)) {
    json_response(['erro' => 'Tipo de conteudo invalido.'], 400);
}

try {
    if (!report_content_exists($pdo, $tipoConteudo, $conteudoId, $usuarioId)) {
        json_response(['erro' => 'Conteudo nao encontrado.'], 404);
    }

    $stmt = $pdo->prepare(
        '
        INSERT INTO reports
            (reporter_id, tipo_conteudo, conteudo_id, motivo)
        VALUES
            (:reporter_id, :tipo_conteudo, :conteudo_id, :motivo)
        RETURNING id, reporter_id, tipo_conteudo, conteudo_id, motivo, status, criado_em
        '
    );
    $stmt->execute([
        ':reporter_id' => $usuarioId,
        ':tipo_conteudo' => $tipoConteudo,
        ':conteudo_id' => $conteudoId,
        ':motivo' => $motivo,
    ]);

    json_response(['data' => $stmt->fetch()], 201);
} catch (PDOException $e) {
    error_log('Falha ao criar report.');
    json_response(['erro' => 'Nao foi possivel reportar o conteudo.'], 500);
}
