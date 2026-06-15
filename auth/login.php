<?php

declare(strict_types=1);

session_start();

require_once '../config/database.php';

function responder_erro_login(string $mensagem, int $statusCode = 400): void
{
    http_response_code($statusCode);
    echo $mensagem;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_erro_login('Metodo nao permitido.', 405);
}

$email = trim((string) ($_POST['email'] ?? ''));
$senha = (string) ($_POST['senha'] ?? '');

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || $senha === '') {
    responder_erro_login('Email ou senha invalidos.', 400);
}

try {
    $stmt = $pdo->prepare(
        'SELECT id, senha_hash FROM usuarios WHERE email = ? LIMIT 1'
    );

    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        responder_erro_login('Email ou senha invalidos.', 401);
    }

    session_regenerate_id(true);
    $_SESSION['usuario'] = (int) $usuario['id'];

    header('Location: ../index.php');
    exit;
} catch (PDOException $e) {
    error_log('Falha ao autenticar usuario.');
    responder_erro_login('Nao foi possivel concluir o login.', 500);
}
