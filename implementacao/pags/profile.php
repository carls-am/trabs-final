<?php
require_once __DIR__ . '/functions.php';

$userId = $_GET['id'] ?? 0;
$profileUser = getUser((int)$userId);

if (!$profileUser) {
    include __DIR__ . '/header.php';
    echo '<div class="text-center" style="padding:4rem;"><h2>Usuário não encontrado</h2></div>';
    include __DIR__ . '/footer.php';
    exit;
}

$userReviews = getUserReviews($profileUser['id']);
$userLists = getUserLists($profileUser['id']);
$publicLists = array_filter($userLists, fn($l) => $l['is_public']);

include __DIR__ . '/header.php';
?>

<div class="profile-header">
    <img src="<?= getAvatarUrl($profileUser['avatar_url'], $profileUser['username']) ?>" 
         alt="<?= h($profileUser['username']) ?>" 
         class="profile-avatar"
         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($profileUser['username']) ?>&background=e94560&color=fff&size=150'">
    <div class="profile-info">
        <h1><?= h($profileUser['username']) ?></h1>
        <?php if ($profileUser['bio']): ?>
            <p class="bio"><?= nl2br(h($profileUser['bio'])) ?></p>
        <?php endif; ?>
        <div class="profile-stats">
            <span><?= count($userReviews) ?> análises</span>
            <span><?= count($userLists) ?> listas</span>
            <span>Membro desde <?= date('d/m/Y', strtotime($profileUser['created_at'])) ?></span>
        </div>
        <?php if (isLoggedIn() && $_SESSION['user_id'] == $profileUser['id']): ?>
            <a href="<?= SITE_URL ?>/my_profile.php" class="btn-secondary mt-1" style="display:inline-block;">Editar Perfil</a>
        <?php endif; ?>
    </div>
</div>

<!-- Listas Públicas -->
<?php if ($publicLists): ?>
    <section style="margin-bottom:2rem;">
        <h3>Listas de Jogos</h3>
        <?php foreach ($publicLists as $list): 
            $db = getDB();
            $stmt = $db->prepare("SELECT COUNT(*) FROM list_entries WHERE list_id = ?");
            $stmt->execute([$list['id']]);
            $gameCount = $stmt->fetchColumn();
        ?>
            <div class="list-card">
                <h3><?= h($list['name']) ?></h3>
                <div class="list-meta"><?= $gameCount ?> jogo(s) • Pública</div>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<!-- Histórico de Reviews -->
<section>
    <h3>Histórico de Análises</h3>
    <?php if ($userReviews): ?>
        <?php foreach ($userReviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <img src="<?= h($review['cover_image_url']) ?>" 
                         alt="<?= h($review['title']) ?>" 
                         style="width:50px;height:67px;object-fit:cover;border-radius:4px;"
                         onerror="this.src='https://placehold.co/50x67/1a1a2e/e94560'">
                    <div>
                        <a href="<?= SITE_URL ?>/game.php?id=<?= $review['game_id'] ?>" style="font-weight:600;"><?= h($review['title']) ?></a>
                        <div class="review-date"><?= date('d/m/Y', strtotime($review['created_at'])) ?></div>
                    </div>
                    <div class="review-rating"><?= renderStars($review['rating']) ?></div>
                </div>
                <?php if ($review['review_text']): ?>
                    <div class="review-text"><?= nl2br(h($review['review_text'])) ?></div>
                <?php endif; ?>
                <?php if ($review['has_spoiler']): ?>
                    <span class="spoiler-badge">Contém spoilers</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:var(--text-muted);">Nenhuma análise publicada ainda.</p>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/footer.php'; ?>