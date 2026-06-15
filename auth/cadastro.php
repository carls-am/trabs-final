<?php

declare(strict_types=1);

require_once '../config/database.php';

function responder_erros(array $erros, int $statusCode = 400): void
{
    http_response_code($statusCode);
    echo implode(PHP_EOL, $erros);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_erros(['Metodo nao permitido.'], 405);
}

$username = trim((string) ($_POST['username'] ?? ''));
$nome = trim((string) ($_POST['nome'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$senha = (string) ($_POST['senha'] ?? '');
$erros = [];

if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
    $erros[] = 'Informe um username com 3 a 30 caracteres usando letras, numeros, ponto, hifen ou underline.';
}

if ($nome === '' || strlen($nome) > 120) {
    $erros[] = 'Informe um nome com ate 120 caracteres.';
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 180) {
    $erros[] = 'Informe um email valido.';
}

if (strlen($senha) < 6) {
    $erros[] = 'Informe uma senha com pelo menos 6 caracteres.';
}

if ($erros !== []) {
    responder_erros($erros);
}

try {
    $stmt = $pdo->prepare(
        'SELECT id FROM usuarios WHERE email = ? OR username = ? LIMIT 1'
    );
    $stmt->execute([$email, $username]);

    if ($stmt->fetch()) {
        responder_erros(['Email ou username ja cadastrado.'], 409);
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        '
        INSERT INTO usuarios
            (username, nome, email, senha_hash)
        VALUES
            (?, ?, ?, ?)
        '
    );

    $stmt->execute([
        $username,
        $nome,
        $email,
        $senhaHash,
    ]);

    header('Location: login.php');
    exit;
} catch (PDOException $e) {
    if ($e->getCode() === '23505') {
        responder_erros(['Email ou username ja cadastrado.'], 409);
    }

    error_log('Falha ao cadastrar usuario.');
    responder_erros(['Nao foi possivel concluir o cadastro.'], 500);
}
