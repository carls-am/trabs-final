<?php
// functions.php - Funções utilitárias do StarPad
require_once __DIR__ . '/config.php';

// Busca jogo por ID
function getGame(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// Busca usuário por ID
function getUser(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// Busca usuário por username
function getUserByUsername(string $username): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

// Média de avaliações de um jogo
function getGameAverageRating(int $gameId): ?float {
    $db = getDB();
    $stmt = $db->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE game_id = ?");
    $stmt->execute([$gameId]);
    $row = $stmt->fetch();
    return $row['total'] > 0 ? round($row['avg_rating'], 1) : null;
}

// Total de reviews de um jogo
function getGameReviewCount(int $gameId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE game_id = ?");
    $stmt->execute([$gameId]);
    return (int) $stmt->fetchColumn();
}

// Review do usuário para um jogo específico
function getUserReview(int $userId, int $gameId): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM reviews WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$userId, $gameId]);
    return $stmt->fetch() ?: null;
}

// Todas as reviews de um jogo (mais recentes primeiro)
function getGameReviews(int $gameId, int $limit = 20): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT r.*, u.username, u.avatar_url 
         FROM reviews r 
         JOIN users u ON r.user_id = u.id 
         WHERE r.game_id = ? 
         ORDER BY r.created_at DESC 
         LIMIT ?"
    );
    $stmt->execute([$gameId, $limit]);
    return $stmt->fetchAll();
}

// Likes e dislikes de uma review
function getReviewVotes(int $reviewId): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT 
            SUM(CASE WHEN vote_type = 'like' THEN 1 ELSE 0 END) AS likes,
            SUM(CASE WHEN vote_type = 'dislike' THEN 1 ELSE 0 END) AS dislikes
         FROM review_likes WHERE review_id = ?"
    );
    $stmt->execute([$reviewId]);
    return $stmt->fetch();
}

// Voto do usuário em uma review
function getUserVote(int $userId, int $reviewId): ?string {
    $db = getDB();
    $stmt = $db->prepare("SELECT vote_type FROM review_likes WHERE user_id = ? AND review_id = ?");
    $stmt->execute([$userId, $reviewId]);
    $row = $stmt->fetch();
    return $row['vote_type'] ?? null;
}

// Busca jogos com filtros
function searchGames(?string $search = null, ?string $genre = null, ?string $platform = null, ?string $tag = null, int $limit = 50): array {
    $db = getDB();
    $sql = "SELECT DISTINCT g.* FROM games g LEFT JOIN game_tags gt ON g.id = gt.game_id LEFT JOIN tags t ON gt.tag_id = t.id WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (g.title LIKE ? OR g.developer LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($genre) {
        $sql .= " AND g.genre = ?";
        $params[] = $genre;
    }
    if ($platform) {
        $sql .= " AND g.platform = ?";
        $params[] = $platform;
    }
    if ($tag) {
        $sql .= " AND t.name = ?";
        $params[] = $tag;
    }
    
    $sql .= " ORDER BY g.title LIMIT ?";
    $params[] = $limit;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Tags de um jogo
function getGameTags(int $gameId): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT t.* FROM tags t JOIN game_tags gt ON t.id = gt.tag_id WHERE gt.game_id = ? ORDER BY t.name"
    );
    $stmt->execute([$gameId]);
    return $stmt->fetchAll();
}

// Todas as tags (para nuvem de tags)
function getAllTags(): array {
    $db = getDB();
    $stmt = $db->query("SELECT t.*, COUNT(gt.game_id) AS game_count FROM tags t LEFT JOIN game_tags gt ON t.id = gt.tag_id GROUP BY t.id ORDER BY game_count DESC");
    return $stmt->fetchAll();
}

// Gêneros disponíveis
function getAllGenres(): array {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT genre FROM games ORDER BY genre");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Plataformas disponíveis
function getAllPlatforms(): array {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT platform FROM games ORDER BY platform");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Listas do usuário
function getUserLists(int $userId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM user_lists WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Reviews do usuário (para perfil)
function getUserReviews(int $userId, int $limit = 30): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT r.*, g.title, g.cover_image_url 
         FROM reviews r 
         JOIN games g ON r.game_id = g.id 
         WHERE r.user_id = ? 
         ORDER BY r.created_at DESC 
         LIMIT ?"
    );
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// Avatar URL (usa serviço ui-avatars.com como fallback)
function getAvatarUrl(?string $url, string $username): string {
    if ($url && file_exists(__DIR__ . '/' . ltrim($url, '/'))) {
        return SITE_URL . '/' . ltrim($url, '/');
    }
    // Fallback: ui-avatars.com (serviço gratuito de placeholders)
    return 'https://ui-avatars.com/api/?name=' . urlencode($username) . '&background=e94560&color=fff&size=150&bold=true';
}

// Jogos com mais atividade recente (para home)
function getRecentActiveGames(int $limit = 6): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT g.*, 
            (SELECT COUNT(*) FROM reviews WHERE game_id = g.id) AS review_count,
            (SELECT AVG(rating) FROM reviews WHERE game_id = g.id) AS avg_rating
         FROM games g 
         ORDER BY (SELECT MAX(created_at) FROM reviews WHERE game_id = g.id) DESC 
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Melhores avaliados
function getTopRatedGames(int $limit = 6): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT g.*, AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
         FROM games g 
         JOIN reviews r ON g.id = r.game_id 
         GROUP BY g.id 
         HAVING COUNT(r.id) >= 1 
         ORDER BY avg_rating DESC 
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Piores avaliados
function getLowestRatedGames(int $limit = 6): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT g.*, AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
         FROM games g 
         JOIN reviews r ON g.id = r.game_id 
         GROUP BY g.id 
         HAVING COUNT(r.id) >= 1 
         ORDER BY avg_rating ASC 
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Estrelas HTML (1-10)
function renderStars(int $rating): string {
    $html = '<span class="stars-display" title="' . $rating . '/10">';
    for ($i = 1; $i <= 10; $i++) {
        $html .= $i <= $rating ? '★' : '☆';
    }
    $html .= ' <small>' . $rating . '/10</small></span>';
    return $html;
}