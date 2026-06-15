<?php

declare(strict_types=1);

session_start();

require_once '../config/database.php';

function responder_erro_avaliacao(string $mensagem, int $statusCode = 400): void
{
    http_response_code($statusCode);
    echo $mensagem;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_erro_avaliacao('Metodo nao permitido.', 405);
}

if (empty($_SESSION['usuario'])) {
    responder_erro_avaliacao('Voce precisa estar logado para avaliar.', 401);
}

$jogoId = filter_var($_POST['jogo_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$nota = filter_var($_POST['nota'] ?? null, FILTER_VALIDATE_FLOAT);
$review = trim((string) ($_POST['review'] ?? ''));
$spoiler = isset($_POST['spoiler']) ? 'true' : 'false';
$erros = [];

if ($jogoId === false) {
    $erros[] = 'Jogo invalido.';
}

if ($nota === false || $nota < 0 || $nota > 5) {
    $erros[] = 'A nota deve estar entre 0 e 5.';
}

if ($review === '' || strlen($review) > 3000) {
    $erros[] = 'A review deve ter entre 1 e 3000 caracteres.';
}

if ($erros !== []) {
    responder_erro_avaliacao(implode(PHP_EOL, $erros));
}

try {
    $stmt = $pdo->prepare(
        '
        INSERT INTO reviews
            (usuario_id, jogo_id, nota, review, spoiler)
        VALUES
            (?, ?, ?, ?, ?)
        '
    );

    $stmt->execute([
        (int) $_SESSION['usuario'],
        $jogoId,
        $nota,
        $review,
        $spoiler,
    ]);

    echo 'Review cadastrada com sucesso.';
} catch (PDOException $e) {
    if ($e->getCode() === '23503') {
        responder_erro_avaliacao('Usuario ou jogo invalido.', 400);
    }

    if ($e->getCode() === '23505') {
        responder_erro_avaliacao('Voce ja avaliou este jogo.', 409);
    }

    error_log('Falha ao cadastrar review.');
    responder_erro_avaliacao('Nao foi possivel salvar a review.', 500);
}
