<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/api.php';

require_method(['POST']);

function username_value(array $data): ?string
{
    if (!array_key_exists('username', $data)) {
        return null;
    }

    $username = text_value($data, 'username', 30);

    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
        json_response([
            'erro' => 'Informe um username com 3 a 30 caracteres usando letras, numeros, ponto, hifen ou underline.',
        ], 400);
    }

    return $username;
}

$usuarioId = require_login();
$data = request_data();
$sets = [];
$params = [':id' => $usuarioId];

if (array_key_exists('username', $data)) {
    $sets[] = 'username = :username';
    $params[':username'] = username_value($data);
}

if (array_key_exists('nome', $data)) {
    $sets[] = 'nome = :nome';
    $params[':nome'] = text_value($data, 'nome', 120);
}

if (array_key_exists('bio', $data)) {
    $sets[] = 'bio = :bio';
    $params[':bio'] = text_value($data, 'bio', 1000, false);
}

if (array_key_exists('avatar', $data)) {
    $sets[] = 'avatar = :avatar';
    $params[':avatar'] = text_value($data, 'avatar', 1000, false);
}

$senhaNova = text_value($data, 'senha_nova', 200, false);

try {
    if ($senhaNova !== null) {
        if (strlen($senhaNova) < 6) {
            json_response(['erro' => 'A nova senha deve ter pelo menos 6 caracteres.'], 400);
        }

        $senhaAtual = text_value($data, 'senha_atual', 200);
        $senhaStmt = $pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = :id LIMIT 1');
        $senhaStmt->execute([':id' => $usuarioId]);
        $usuario = $senhaStmt->fetch();

        if (!$usuario || !password_verify($senhaAtual, $usuario['senha_hash'])) {
            json_response(['erro' => 'Senha atual invalida.'], 401);
        }

        $sets[] = 'senha_hash = :senha_hash';
        $params[':senha_hash'] = password_hash($senhaNova, PASSWORD_DEFAULT);
    }

    if ($sets === []) {
        json_response(['erro' => 'Nenhum campo enviado para editar.'], 400);
    }

    $stmt = $pdo->prepare(
        '
        UPDATE usuarios
        SET ' . implode(', ', $sets) . '
        WHERE id = :id
        RETURNING id, username, nome, avatar, bio, criado_em
        '
    );
    $stmt->execute($params);

    json_response(['data' => $stmt->fetch()]);
} catch (PDOException $e) {
    if ($e->getCode() === '23505') {
        json_response(['erro' => 'Username ja esta em uso.'], 409);
    }

    error_log('Falha ao editar perfil.');
    json_response(['erro' => 'Nao foi possivel editar o perfil.'], 500);
}
