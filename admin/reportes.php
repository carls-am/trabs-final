<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/api.php';

$adminId = require_admin();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $status = trim((string) ($_GET['status'] ?? ''));
        $params = [];
        $where = '';

        if ($status !== '') {
            if (!in_array($status, ['aberto', 'em_analise', 'resolvido', 'rejeitado'], true)) {
                json_response(['erro' => 'Status invalido.'], 400);
            }

            $where = 'WHERE r.status = :status';
            $params[':status'] = $status;
        }

        $stmt = $pdo->prepare(
            "
            SELECT
                r.id,
                r.tipo_conteudo,
                r.conteudo_id,
                r.motivo,
                r.status,
                r.observacao_admin,
                r.moderado_por,
                r.moderado_em,
                r.criado_em,
                u.username AS reporter_username
            FROM reports r
            INNER JOIN usuarios u ON u.id = r.reporter_id
            $where
            ORDER BY r.criado_em DESC
            LIMIT 100
            "
        );
        $stmt->execute($params);

        json_response(['data' => $stmt->fetchAll()]);
    }

    if ($method !== 'POST') {
        json_response(['erro' => 'Metodo nao permitido.'], 405);
    }

    $data = request_data();
    $reportId = int_value($data, 'report_id');
    $status = text_value($data, 'status', 20);
    $observacao = text_value($data, 'observacao_admin', 1000, false);

    if (!in_array($status, ['aberto', 'em_analise', 'resolvido', 'rejeitado'], true)) {
        json_response(['erro' => 'Status invalido.'], 400);
    }

    $stmt = $pdo->prepare(
        '
        UPDATE reports
        SET
            status = :status,
            observacao_admin = :observacao_admin,
            moderado_por = :moderado_por,
            moderado_em = CURRENT_TIMESTAMP
        WHERE id = :id
        RETURNING id, tipo_conteudo, conteudo_id, motivo, status, observacao_admin, moderado_por, moderado_em, criado_em
        '
    );
    $stmt->execute([
        ':status' => $status,
        ':observacao_admin' => $observacao,
        ':moderado_por' => $adminId,
        ':id' => $reportId,
    ]);
    $report = $stmt->fetch();

    if (!$report) {
        json_response(['erro' => 'Report nao encontrado.'], 404);
    }

    json_response(['data' => $report]);
} catch (PDOException $e) {
    error_log('Falha ao gerenciar reportes.');
    json_response(['erro' => 'Nao foi possivel gerenciar reportes.'], 500);
}
