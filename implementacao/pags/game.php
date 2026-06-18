<?php
require_once __DIR__ . '/functions.php';

$gameId = $_GET['id'] ?? 0;
$game = getGame((int)$gameId);

if (!$game) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/header.php';
    echo '<div class="text-center" style="padding:4rem;"><h2>Jogo não encontrado</h2><a href="' . SITE_URL . '/catalog.php" class="btn-primary mt-2">Ver Catálogo</a></div>';
    include __DIR__ . '/footer.php';
    exit;
}

$avgRating = getGameAverageRating($game['id']);
$reviewCount = getGameReviewCount($game['id']);
$reviews = getGameReviews($game['id']);
$tags = getGameTags($game['id']);

// Processar envio de review
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $rating = (int)($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? '');
    $hasSpoiler = isset($_POST['has_spoiler']) ? 1 : 0;
    
    if ($rating < 1 || $rating > 10) {
        $message = 'Por favor, selecione uma nota de 1 a 10.';
        $messageType = 'error';
    } elseif (mb_strlen($reviewText) > MAX_REVIEW_CHARS) {
        $message = 'A análise excede o limite de ' . MAX_REVIEW_CHARS . ' caracteres.';
        $messageType = 'error';
    } else {
        $existing = getUserReview($_SESSION['user_id'], $game['id']);
        $db = getDB();
        
        if ($existing) {
            // Atualizar review existente
            $stmt = $db->prepare("UPDATE reviews SET rating=?, review_text=?, has_spoiler=? WHERE id=? AND user_id=?");
            $stmt->execute([$rating, $reviewText, $hasSpoiler, $existing['id'], $_SESSION['user_id']]);
            $message = 'Sua avaliação foi atualizada!';
        } else {
            // Nova review
            $stmt = $db->prepare("INSERT INTO reviews (user_id, game_id, rating, review_text, has_spoiler) VALUES (?,?,?,?,?)");
            $stmt->execute([$_SESSION['user_id'], $game['id'], $rating, $reviewText, $hasSpoiler]);
            $message = 'Sua avaliação foi publicada!';
        }
        $messageType = 'success';
        // Recarregar reviews
        $reviews = getGameReviews($game['id']);
        $avgRating = getGameAverageRating($game['id']);
        $reviewCount = getGameReviewCount($game['id']);
    }
}

$userReview = isLoggedIn() ? getUserReview($_SESSION['user_id'], $game['id']) : null;

include __DIR__ . '/header.php';
?>

<?php if ($message): ?>
    <div class="message <?= $messageType ?>"><?= h($message) ?></div>
<?php endif; ?>

<div class="game-detail">
    <!-- Capa -->
    <div class="game-detail-cover">
        <img src="<?= h($game['cover_image_url']) ?>" 
             alt="<?= h($game['title']) ?>"
             onerror="this.src='https://placehold.co/300x400/1a1a2e/e94560?text=Sem+Capa'">
    </div>
    
    <!-- Info -->
    <div class="game-detail-info">
        <h1><?= h($game['title']) ?></h1>
        <div class="game-detail-meta">
            <span>✑ <?= h($game['developer']) ?></span>
            <span>⌨ <?= h($game['platform']) ?></span>
            <span>✉ <?= h($game['release_date'] ?? 'Data não informada') ?></span>
            <span>♙ <?= h($game['genre']) ?></span>
        </div>
        
        <!-- Tags -->
        <?php if ($tags): ?>
            <div class="tag-list">
                <?php foreach ($tags as $t): ?>
                    <a href="<?= SITE_URL ?>/catalog.php?tag=<?= urlencode($t['name']) ?>" class="tag"><?= h($t['name']) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <p class="game-detail-description"><?= nl2br(h($game['description'])) ?></p>
        
        <!-- Estatísticas -->
        <div class="game-detail-stats">
            <div class="stat-big">
                <div class="stat-number"><?= $avgRating ? number_format($avgRating, 1) : '—' ?></div>
                <div class="stat-label">Média Geral</div>
                <div class="stat-stars"><?= $avgRating ? renderStars(round($avgRating)) : 'Sem avaliações' ?></div>
            </div>
            <div class="stat-big">
                <div class="stat-number"><?= $reviewCount ?></div>
                <div class="stat-label">Resenhas</div>
            </div>
        </div>
    </div>
</div>

<!-- Formulário de Review -->
<?php if (isLoggedIn()): ?>
    <section style="margin-bottom:2rem;">
        <h3 style="margin-bottom:1rem;"><?= $userReview ? 'Editar sua avaliação' : 'Sua Avaliação' ?></h3>
        <form method="POST" action="" style="background:var(--bg-card);padding:1.5rem;border-radius:var(--radius);border:1px solid var(--border-color);">
            <div class="form-group">
                <label>Nota (1 a 10 estrelas):</label>
                <div class="star-rating-input" id="star-rating-container">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <span class="star <?= ($userReview['rating'] ?? 0) >= $i ? 'selected' : '' ?>" 
                              data-value="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="rating-input" value="<?= $userReview['rating'] ?? 0 ?>">
            </div>
            <div class="form-group">
                <label for="review-text">Análise (máx. <?= MAX_REVIEW_CHARS ?> caracteres):</label>
                <textarea name="review_text" id="review-text" maxlength="<?= MAX_REVIEW_CHARS ?>"><?= h($userReview['review_text'] ?? '') ?></textarea>
                <div class="char-count" id="char-count"><?= mb_strlen($userReview['review_text'] ?? '') ?>/<?= MAX_REVIEW_CHARS ?> caracteres</div>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="has_spoiler" value="1" <?= ($userReview['has_spoiler'] ?? 0) ? 'checked' : '' ?>>
                    Contém spoilers
                </label>
            </div>
            <button type="submit" class="btn-primary"><?= $userReview ? 'Atualizar' : 'Publicar' ?> Avaliação</button>
        </form>
    </section>
<?php else: ?>
    <div style="text-align:center;padding:1.5rem;background:var(--bg-card);border-radius:var(--radius);border:1px solid var(--border-color);margin-bottom:2rem;">
        <p><a href="<?= SITE_URL ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Faça login</a> para avaliar este jogo.</p>
    </div>
<?php endif; ?>

<!-- Lista de Reviews -->
<section>
    <h3 style="margin-bottom:1rem;">Resenhas da Comunidade (<?= $reviewCount ?>)</h3>
    <?php if ($reviews): ?>
        <?php foreach ($reviews as $review): 
            $votes = getReviewVotes($review['id']);
            $userVote = isLoggedIn() ? getUserVote($_SESSION['user_id'], $review['id']) : null;
        ?>
            <div class="review-card" id="review-<?= $review['id'] ?>">
                <div class="review-header">
                    <img src="<?= getAvatarUrl($review['avatar_url'], $review['username']) ?>" 
                         alt="<?= h($review['username']) ?>" 
                         class="review-avatar"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($review['username']) ?>&background=e94560&color=fff&size=40'">
                    <div>
                        <a href="<?= SITE_URL ?>/profile.php?id=<?= $review['user_id'] ?>" class="review-user"><?= h($review['username']) ?></a>
                        <div class="review-date"><?= date('d/m/Y', strtotime($review['created_at'])) ?></div>
                    </div>
                    <div class="review-rating"><?= renderStars($review['rating']) ?></div>
                </div>
                
                <?php if ($review['has_spoiler']): ?>
                    <span class="spoiler-badge" onclick="revealSpoiler(this.nextElementSibling)">Spoiler — Clique para revelar</span>
                    <div class="review-text spoiler-blur" onclick="revealSpoiler(this)">
                        <?= nl2br(h($review['review_text'])) ?>
                    </div>
                <?php else: ?>
                    <div class="review-text"><?= nl2br(h($review['review_text'])) ?></div>
                <?php endif; ?>
                
                <div class="review-actions">
                    <?php if (isLoggedIn()): ?>
                        <button class="btn-vote <?= $userVote === 'like' ? 'voted-like' : '' ?>" 
                                id="like-btn-<?= $review['id'] ?>"
                                onclick="voteReview(<?= $review['id'] ?>,'like')">
                            ◡‿◡ <span id="like-count-<?= $review['id'] ?>"><?= $votes['likes'] ?></span>
                        </button>
                        <button class="btn-vote <?= $userVote === 'dislike' ? 'voted-dislike' : '' ?>" 
                                id="dislike-btn-<?= $review['id'] ?>"
                                onclick="voteReview(<?= $review['id'] ?>,'dislike')">
                            ＞︿＜ <span id="dislike-count-<?= $review['id'] ?>"><?= $votes['dislikes'] ?></span>
                        </button>
                    <?php else: ?>
                        <span>◡‿◡ <?= $votes['likes'] ?> | ＞︿＜ <?= $votes['dislikes'] ?></span>
                    <?php endif; ?>
                    
                    <?php if (isLoggedIn()): ?>
                        <button class="btn-report" onclick="location.href='<?= SITE_URL ?>/report.php?type=review&id=<?= $review['id'] ?>'">☒ Denunciar</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:var(--text-muted);text-align:center;padding:2rem;">Nenhuma resenha ainda. Seja o primeiro a avaliar!</p>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/footer.php'; ?>