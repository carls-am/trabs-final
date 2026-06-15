<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/api.php';

require_admin();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $stmt = $pdo->query(
            '
            SELECT
                t.id,
                t.nome,
                t.tipo,
                t.descricao,
                t.criado_em,
                COUNT(jt.jogo_id) AS total_jogos
            FROM tags t
            LEFT JOIN jogo_tags jt ON jt.tag_id = t.id
            GROUP BY t.id, t.nome, t.tipo, t.descricao, t.criado_em
            ORDER BY t.nome ASC
            '
        );

        json_response(['data' => $stmt->fetchAll()]);
    }

    if ($method !== 'POST') {
        json_response(['erro' => 'Metodo nao permitido.'], 405);
    }

    $data = request_data();
    $acao = text_value($data, 'acao', 20);

    if ($acao === 'criar') {
        $nome = text_value($data, 'nome', 80);
        $tipo = text_value($data, 'tipo', 50, false);
        $descricao = text_value($data, 'descricao', 1000, false);

        $stmt = $pdo->prepare(
            '
            INSERT INTO tags (nome, tipo, descricao)
            VALUES (:nome, :tipo, :descricao)
            RETURNING id, nome, tipo, descricao, criado_em
            '
        );
        $stmt->execute([
            ':nome' => $nome,
            ':tipo' => $tipo,
            ':descricao' => $descricao,
        ]);

        json_response(['data' => $stmt->fetch()], 201);
    }

    if ($acao === 'editar') {
        $tagId = int_value($data, 'tag_id');
        $nome = text_value($data, 'nome', 80);
        $tipo = text_value($data, 'tipo', 50, false);
        $descricao = text_value($data, 'descricao', 1000, false);

        $stmt = $pdo->prepare(
            '
            UPDATE tags
            SET nome = :nome, tipo = :tipo, descricao = :descricao
            WHERE id = :id
            RETURNING id, nome, tipo, descricao, criado_em
            '
        );
        $stmt->execute([
            ':nome' => $nome,
            ':tipo' => $tipo,
            ':descricao' => $descricao,
            ':id' => $tagId,
        ]);
        $tag = $stmt->fetch();

        if (!$tag) {
            json_response(['erro' => 'Tag nao encontrada.'], 404);
        }

        json_response(['data' => $tag]);
    }

    if ($acao === 'vincular' || $acao === 'desvincular') {
        $tagId = int_value($data, 'tag_id');
        $jogoId = int_value($data, 'jogo_id');

        if ($acao === 'vincular') {
            $stmt = $pdo->prepare(
                '
                INSERT INTO jogo_tags (jogo_id, tag_id)
                VALUES (:jogo_id, :tag_id)
                ON CONFLICT DO NOTHING
                '
            );
        } else {
            $stmt = $pdo->prepare(
                'DELETE FROM jogo_tags WHERE jogo_id = :jogo_id AND tag_id = :tag_id'
            );
        }

        $stmt->execute([
            ':jogo_id' => $jogoId,
            ':tag_id' => $tagId,
        ]);

        json_response([
            'data' => [
                'jogo_id' => $jogoId,
                'tag_id' => $tagId,
                'vinculado' => $acao === 'vincular',
            ],
        ]);
    }

    if ($acao === 'excluir') {
        json_response([
            'erro' => 'Exclusao fisica de tag nao foi implementada por seguranca. Use edicao ou desvincule a tag dos jogos.',
        ], 405);
    }

    json_response(['erro' => 'Acao invalida.'], 400);
} catch (PDOException $e) {
    if ($e->getCode() === '23505') {
        json_response(['erro' => 'Tag ja existe.'], 409);
    }

    if ($e->getCode() === '23503') {
        json_response(['erro' => 'Jogo ou tag invalida.'], 400);
    }

    error_log('Falha ao gerenciar tags.');
    json_response(['erro' => 'Nao foi possivel gerenciar tags.'], 500);
}
