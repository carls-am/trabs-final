<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/api.php';

require_admin();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$camposTexto = [
    'nome' => 160,
    'descricao' => 5000,
    'capa' => 1000,
    'banner' => 1000,
    'desenvolvedora' => 160,
    'publisher' => 160,
    'genero' => 120,
    'plataforma' => 160,
];

function jogo_field_value(array $data, string $campo, int $maxLength): ?string
{
    if ($campo === 'data_lancamento') {
        return date_value($data, 'data_lancamento', false);
    }

    return text_value($data, $campo, $maxLength, $campo === 'nome');
}

try {
    if ($method === 'GET') {
        $stmt = $pdo->query(
            '
            SELECT id, nome, descricao, capa, banner, data_lancamento, desenvolvedora, publisher, genero, plataforma, criado_em
            FROM jogos
            ORDER BY criado_em DESC, id DESC
            LIMIT 100
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
        $nome = text_value($data, 'nome', 160);

        $stmt = $pdo->prepare(
            '
            INSERT INTO jogos
                (nome, descricao, capa, banner, data_lancamento, desenvolvedora, publisher, genero, plataforma)
            VALUES
                (:nome, :descricao, :capa, :banner, :data_lancamento, :desenvolvedora, :publisher, :genero, :plataforma)
            RETURNING id, nome, descricao, capa, banner, data_lancamento, desenvolvedora, publisher, genero, plataforma, criado_em
            '
        );
        $stmt->execute([
            ':nome' => $nome,
            ':descricao' => text_value($data, 'descricao', 5000, false),
            ':capa' => text_value($data, 'capa', 1000, false),
            ':banner' => text_value($data, 'banner', 1000, false),
            ':data_lancamento' => date_value($data, 'data_lancamento', false),
            ':desenvolvedora' => text_value($data, 'desenvolvedora', 160, false),
            ':publisher' => text_value($data, 'publisher', 160, false),
            ':genero' => text_value($data, 'genero', 120, false),
            ':plataforma' => text_value($data, 'plataforma', 160, false),
        ]);

        json_response(['data' => $stmt->fetch()], 201);
    }

    if ($acao === 'editar') {
        $jogoId = int_value($data, 'jogo_id');
        $sets = [];
        $params = [':id' => $jogoId];
        $camposEditaveis = array_merge($camposTexto, ['data_lancamento' => 10]);

        foreach ($camposEditaveis as $campo => $maxLength) {
            if (!array_key_exists($campo, $data)) {
                continue;
            }

            $sets[] = $campo . ' = :' . $campo;
            $params[':' . $campo] = jogo_field_value($data, $campo, $maxLength);
        }

        if ($sets === []) {
            json_response(['erro' => 'Nenhum campo enviado para editar.'], 400);
        }

        $stmt = $pdo->prepare(
            '
            UPDATE jogos
            SET ' . implode(', ', $sets) . '
            WHERE id = :id
            RETURNING id, nome, descricao, capa, banner, data_lancamento, desenvolvedora, publisher, genero, plataforma, criado_em
            '
        );
        $stmt->execute($params);
        $jogo = $stmt->fetch();

        if (!$jogo) {
            json_response(['erro' => 'Jogo nao encontrado.'], 404);
        }

        json_response(['data' => $jogo]);
    }

    if ($acao === 'excluir') {
        json_response([
            'erro' => 'Exclusao fisica de jogo nao foi implementada por seguranca. Use edicao ou confirme uma regra de arquivamento.',
        ], 405);
    }

    json_response(['erro' => 'Acao invalida.'], 400);
} catch (PDOException $e) {
    error_log('Falha ao gerenciar jogos.');
    json_response(['erro' => 'Nao foi possivel gerenciar jogos.'], 500);
}
