<?php
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Não funcionou'];

if (!isLoggedIn()) {
    $response['message'] = 'Você precisa estar logado.';
    echo json_encode($response);
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];

//Like e deslike
if ($action === 'vote' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $voteType = $_POST['vote_type'] ?? '';
    
    if (!in_array($voteType, ['like', 'dislike']) || $reviewId <= 0) {
        $response['message'] = 'Parâmetros inválidos.';
        echo json_encode($response);
        exit;
    }
    
    //ve se ja tem review
    $stmt = $db->prepare("SELECT id FROM reviews WHERE id = ?");
    $stmt->execute([$reviewId]);
    if (!$stmt->fetch()) {
        $response['message'] = 'Review não encontrada.';
        echo json_encode($response);
        exit;
    }
    
    //ve se ja tem voto
    $stmt = $db->prepare("SELECT * FROM review_likes WHERE user_id = ? AND review_id = ?");
    $stmt->execute([$userId, $reviewId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['vote_type'] === $voteType) {
            //DELETAR voto
            $stmt = $db->prepare("DELETE FROM review_likes WHERE id = ?");
            $stmt->execute([$existing['id']]);
        } else {
            //updatear voto
            $stmt = $db->prepare("UPDATE review_likes SET vote_type = ? WHERE id = ?");
            $stmt->execute([$voteType, $existing['id']]);
        }
    } else {
        //adicionar novo voto
        $stmt = $db->prepare("INSERT INTO review_likes (user_id, review_id, vote_type) VALUES (?,?,?)");
        $stmt->execute([$userId, $reviewId, $voteType]);
    }
    
    //contar votos
    $votes = getReviewVotes($reviewId);
    $userVote = getUserVote($userId, $reviewId);
    
    $response = [
        'success'   => true,
        'likes'     => (int)$votes['likes'],
        'dislikes'  => (int)$votes['dislikes'],
        'user_vote' => $userVote,
    ];
}

//adicionar a lista
if ($action === 'add_to_list' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $listId = (int)($_POST['list_id'] ?? 0);
    $gameId = (int)($_POST['game_id'] ?? 0);
    
    //lista - usuario
    $stmt = $db->prepare("SELECT id FROM user_lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$listId, $userId]);
    if (!$stmt->fetch()) {
        $response['message'] = 'Lista não encontrada ou sem permissão.';
        echo json_encode($response);
        exit;
    }
    
    //ve s e o jogo existe
    if (!getGame($gameId)) {
        $response['message'] = 'Jogo não encontrado.';
        echo json_encode($response);
        exit;
    }
    
    //inserir - sem duplicata
    try {
        $stmt = $db->prepare("INSERT IGNORE INTO list_entries (list_id, game_id) VALUES (?,?)");
        $stmt->execute([$listId, $gameId]);
        $response = ['success' => true, 'message' => 'Jogo adicionado à lista!'];
    } catch (Exception $e) {
        $response['message'] = 'Erro ao adicionar jogo.';
    }
}

//fofocas
echo json_encode($response);