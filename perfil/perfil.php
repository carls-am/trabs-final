<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/api.php';

require_method(['GET']);

$usuarioLogadoId = current_user_id();
$id = isset($_GET['id']) && $_GET['id'] !== ''
    ? int_value($_GET, 'id')
    : null;
$username = text_value($_GET, 'username', 30, false);

if ($id === null && $username === null) {
    $id = require_login();
}

try {
    $where = $id !== null ? 'u.id = :id' : 'u.username = :username';
    $params = $id !== null ? [':id' => $id] : [':username' => $username];

    $stmt = $pdo->prepare(
        "
        SELECT
            u.id,
            u.username,
            u.nome,
            u.avatar,
            u.bio,
            u.criado_em,
            (
                SELECT COUNT(1)
                FROM reviews r
                WHERE r.usuario_id = u.id
            ) AS total_reviews,
            (
                SELECT COUNT(1)
                FROM listas l
                WHERE l.usuario_id = u.id
                    AND (l.privada = false OR u.id = :usuario_logado_id)
            ) AS total_listas_visiveis,
            (
                SELECT COUNT(1)
                FROM seguidores s
                WHERE s.seguido_id = u.id
            ) AS total_seguidores,
            (
                SELECT COUNT(1)
                FROM seguidores s
                WHERE s.seguidor_id = u.id
            ) AS total_seguindo
        FROM usuarios u
        WHERE $where
        LIMIT 1
        "
    );

    foreach ($params as $name => $value) {
        $stmt->bindValue($name, $value);
    }

    $stmt->bindValue(':usuario_logado_id', $usuarioLogadoId ?? 0, PDO::PARAM_INT);
    $stmt->execute();
    $perfil = $stmt->fetch();

    if (!$perfil) {
        json_response(['erro' => 'Usuario nao encontrado.'], 404);
    }

    json_response([
        'data' => [
            'id' => (int) $perfil['id'],
            'username' => $perfil['username'],
            'nome' => $perfil['nome'],
            'avatar' => $perfil['avatar'],
            'bio' => $perfil['bio'],
            'criado_em' => $perfil['criado_em'],
            'total_reviews' => (int) $perfil['total_reviews'],
            'total_listas_visiveis' => (int) $perfil['total_listas_visiveis'],
            'total_seguidores' => (int) $perfil['total_seguidores'],
            'total_seguindo' => (int) $perfil['total_seguindo'],
            'proprio_perfil' => $usuarioLogadoId !== null && $usuarioLogadoId === (int) $perfil['id'],
        ],
    ]);
} catch (PDOException $e) {
    error_log('Falha ao carregar perfil.');
    json_response(['erro' => 'Nao foi possivel carregar o perfil.'], 500);
}
