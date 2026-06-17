<?php
require_once __DIR__ . '/functions.php';
$recentGames = getRecentActiveGames(6);
$topGames = getTopRatedGames(6);
$lowGames = getLowestRatedGames(6);
$allTags = getAllTags();
include __DIR__ . '/header.php';
?>

<!-- Hero Banner -->
<section class="hero-banner">
    <h1>StarPad</h1>
    <p>Descubra, avalie e compartilhe suas experiências com jogos eletrônicos. A comunidade gamer que inspira suas próximas aventuras.</p>
    <a href="<?= SITE_URL ?>/catalog.php" class="btn-primary">Explorar Catálogo</a>
    <?php if (!isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/login.php" class="btn-secondary" style="margin-left:0.5rem;">Criar Conta</a>
    <?php endif; ?>
</section>

<!-- Jogos com Atividade Recente -->
<section class="home-section">
    <h2 class="section-title">Atividade Recente</h2>
    <?php if ($recentGames): ?>
        <div class="game-grid">
            <?php foreach ($recentGames as $game): 
                $avg = $game['avg_rating'] ? round($game['avg_rating'], 1) : null;
            ?>
                <div class="game-card">
                    <a href="<?= SITE_URL ?>/game.php?id=<?= $game['id'] ?>" class="game-card-link">
                        <img src="<?= h($game['cover_image_url']) ?>" 
                             alt="<?= h($game['title']) ?>" 
                             class="game-card-img"
                             loading="lazy"
                             onerror="this.src='https://placehold.co/300x400/1a1a2e/e94560?text=Sem+Capa'">
                        <div class="game-card-body">
                            <div class="game-card-title"><?= h($game['title']) ?></div>
                            <div class="game-card-meta"><?= h($game['genre']) ?> • <?= h($game['platform']) ?></div>
                            <?php if ($avg): ?>
                                <div class="game-card-rating"><?= $avg ?>/10 (<?= $game['review_count'] ?> reviews)</div>
                            <?php else: ?>
                                <div class="game-card-rating" style="color:var(--text-muted);">Sem avaliações</div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:var(--text-muted);">Nenhum jogo com atividade recente. Seja o primeiro a avaliar!</p>
    <?php endif; ?>
</section>

<!-- Melhores Avaliados -->
<section class="home-section">
    <h2 class="section-title">Melhores Avaliados</h2>
    <?php if ($topGames): ?>
        <div class="game-grid">
            <?php foreach ($topGames as $game): ?>
                <div class="game-card">
                    <a href="<?= SITE_URL ?>/game.php?id=<?= $game['id'] ?>" class="game-card-link">
                        <img src="<?= h($game['cover_image_url']) ?>" 
                             alt="<?= h($game['title']) ?>" 
                             class="game-card-img"
                             loading="lazy"
                             onerror="this.src='https://placehold.co/300x400/1a1a2e/e94560?text=Sem+Capa'">
                        <div class="game-card-body">
                            <div class="game-card-title"><?= h($game['title']) ?></div>
                            <div class="game-card-rating"><?= round($game['avg_rating'], 1) ?>/10</div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:var(--text-muted);">Ainda não há avaliações suficientes para ranquear.</p>
    <?php endif; ?>
</section>

<!-- Piores Avaliados -->
<section class="home-section">
    <h2 class="section-title">Piores Avaliados</h2>
    <?php if ($lowGames): ?>
        <div class="game-grid">
            <?php foreach ($lowGames as $game): ?>
                <div class="game-card">
                    <a href="<?= SITE_URL ?>/game.php?id=<?= $game['id'] ?>" class="game-card-link">
                        <img src="<?= h($game['cover_image_url']) ?>" 
                             alt="<?= h($game['title']) ?>" 
                             class="game-card-img"
                             loading="lazy"
                             onerror="this.src='https://placehold.co/300x400/1a1a2e/e94560?text=Sem+Capa'">
                        <div class="game-card-body">
                            <div class="game-card-title"><?= h($game['title']) ?></div>
                            <div class="game-card-rating"><?= round($game['avg_rating'], 1) ?>/10</div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:var(--text-muted);">Ainda não há avaliações suficientes para ranquear.</p>
    <?php endif; ?>
</section>

<!-- Tags Populares -->
<section class="home-section">
    <h2 class="section-title">Tags Populares</h2>
    <div class="tag-list">
        <?php foreach (array_slice($allTags, 0, 15) as $tag): ?>
            <a href="<?= SITE_URL ?>/catalog.php?tag=<?= urlencode($tag['name']) ?>" class="tag">
                <?= h($tag['name']) ?> (<?= $tag['game_count'] ?>)
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>