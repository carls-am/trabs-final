<?php
require_once __DIR__ . '/functions.php';

$search  = $_GET['search'] ?? null;
$genre   = $_GET['genre'] ?? null;
$platform = $_GET['platform'] ?? null;
$tag     = $_GET['tag'] ?? null;

$games   = searchGames($search, $genre, $platform, $tag, 50);
$allGenres = getAllGenres();
$allPlatforms = getAllPlatforms();
$allTags = getAllTags();

include __DIR__ . '/header.php';
?>

<h1 style="margin-bottom:0.5rem;">Catálogo de Jogos</h1>
<p style="color:var(--text-muted);margin-bottom:1.5rem;">Explore, filtre e encontre seu próximo jogo favorito.</p>

<!-- Filtros -->
<form class="filters-bar" method="GET" action="<?= SITE_URL ?>/catalog.php">
    <input type="text" name="search" placeholder="Buscar por título ou desenvolvedora..." value="<?= h($search) ?>">
    <select name="genre">
        <option value="">Todos os gêneros</option>
        <?php foreach ($allGenres as $g): ?>
            <option value="<?= h($g) ?>" <?= $genre === $g ? 'selected' : '' ?>><?= h($g) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="platform">
        <option value="">Todas as plataformas</option>
        <?php foreach ($allPlatforms as $p): ?>
            <option value="<?= h($p) ?>" <?= $platform === $p ? 'selected' : '' ?>><?= h($p) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="tag">
        <option value="">Todas as tags</option>
        <?php foreach ($allTags as $t): ?>
            <option value="<?= h($t['name']) ?>" <?= $tag === $t['name'] ? 'selected' : '' ?>><?= h($t['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-primary">Filtrar</button>
    <a href="<?= SITE_URL ?>/catalog.php" class="btn-secondary">Limpar</a>
</form>

<!-- Resultados -->
<?php if ($games): ?>
    <p style="color:var(--text-muted);margin-bottom:1rem;"><?= count($games) ?> jogo(s) encontrado(s).</p>
    <div class="game-grid">
        <?php foreach ($games as $game): 
            $avg = getGameAverageRating($game['id']);
            $count = getGameReviewCount($game['id']);
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
                        <div class="game-card-meta"><?= h($game['developer']) ?> • <?= h($game['platform']) ?></div>
                        <?php if ($avg): ?>
                            <div class="game-card-rating">✩ <?= $avg ?>/10 (<?= $count ?>)</div>
                        <?php else: ?>
                            <div class="game-card-rating" style="color:var(--text-muted);">Sem notas</div>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div style="text-align:center;padding:3rem;color:var(--text-muted);">
        <p style="font-size:3rem;">(jogos)</p>
        <p>Nenhum jogo encontrado com esses filtros.</p>
        <a href="<?= SITE_URL ?>/catalog.php" class="btn-secondary mt-1">Limpar filtros</a>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>