<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$db = getDB();
$currentUser = getUser($_SESSION['user_id']);
$message = '';
$messageType = '';

// Atualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $bio = trim($_POST['bio'] ?? '');
    $favoriteGameId = (int)($_POST['favorite_game_id'] ?? 0);
    
    // Upload de avatar
    $avatarUrl = $currentUser['avatar_url'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($ext), $allowed) && $_FILES['avatar']['size'] < 2 * 1024 * 1024) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOAD_DIR . $filename);
            $avatarUrl = 'uploads/avatars/' . $filename;
        }
    }
    
    $stmt = $db->prepare("UPDATE users SET bio=?, avatar_url=?, favorite_game_id=? WHERE id=?");
    $stmt->execute([$bio, $avatarUrl, $favoriteGameId ?: null, $_SESSION['user_id']]);
    $message = 'Perfil atualizado com sucesso!';
    $messageType = 'success';
    $currentUser = getUser($_SESSION['user_id']);
}

// Excluir review (CRUD - Delete)
if (isset($_GET['delete_review']) && is_numeric($_GET['delete_review'])) {
    $reviewId = (int)$_GET['delete_review'];
    $stmt = $db->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
    $stmt->execute([$reviewId, $_SESSION['user_id']]);
    $message = 'Review excluída.';
    $messageType = 'success';
}

$userReviews = getUserReviews($_SESSION['user_id']);
$allGames = searchGames(null, null, null, null, 200);

include __DIR__ . '/header.php';
?>

<?php if ($message): ?>
    <div class="message <?= $messageType ?>"><?= h($message) ?></div>
<?php endif; ?>

<h1>Configurações do Perfil</h1>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-top:1.5rem;">
    <!-- Editar Perfil -->
    <div style="background:var(--bg-card);padding:1.5rem;border-radius:var(--radius);border:1px solid var(--border-color);">
        <h3>Editar Informações</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-group">
                <label>Username: <strong><?= h($currentUser['username']) ?></strong></label>
            </div>
            <div class="form-group">
                <label for="bio">Bio:</label>
                <textarea name="bio" id="bio" rows="3"><?= h($currentUser['bio'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="favorite_game_id">Jogo Favorito:</label>
                <select name="favorite_game_id" id="favorite_game_id">
                    <option value="">Nenhum</option>
                    <?php foreach ($allGames as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($currentUser['favorite_game_id'] ?? 0) == $g['id'] ? 'selected' : '' ?>>
                            <?= h($g['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="avatar">Foto de Perfil (máx 2MB):</label>
                <input type="file" name="avatar" id="avatar" accept="image/*">
                <small style="color:var(--text-muted);">Formatos: JPG, PNG, GIF, WebP</small>
            </div>
            <button type="submit" class="btn-primary">Salvar Alterações</button>
        </form>
    </div>
    
    <!-- Preview do avatar -->
    <div style="background:var(--bg-card);padding:1.5rem;border-radius:var(--radius);border:1px solid var(--border-color);text-align:center;">
        <h3>Avatar Atual</h3>
        <img src="<?= getAvatarUrl($currentUser['avatar_url'], $currentUser['username']) ?>" 
             alt="Avatar" 
             style="width:150px;height:150px;border-radius:50%;object-fit:cover;border:4px solid var(--accent);margin:1rem auto;"
             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($currentUser['username']) ?>&background=e94560&color=fff&size=150'">
        <p style="color:var(--text-muted);"><?= h($currentUser['username']) ?></p>
    </div>
</div>

<!-- Gerenciar Reviews (CRUD) -->
<section style="margin-top:3rem;">
    <h3>Gerenciar Minhas Análises (CRUD)</h3>
    <p style="color:var(--text-muted);margin-bottom:1rem;">Total: <?= count($userReviews) ?> análise(s)</p>
    
    <?php if ($userReviews): ?>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;background:var(--bg-card);border-radius:var(--radius);overflow:hidden;border:1px solid var(--border-color);">
                <thead>
                    <tr style="background:var(--bg-hover);">
                        <th style="padding:0.8rem;text-align:left;">Jogo</th>
                        <th style="padding:0.8rem;">Nota</th>
                        <th style="padding:0.8rem;">Data</th>
                        <th style="padding:0.8rem;">Spoiler</th>
                        <th style="padding:0.8rem;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userReviews as $review): ?>
                        <tr style="border-top:1px solid var(--border-color);">
                            <td style="padding:0.7rem;">
                                <a href="<?= SITE_URL ?>/game.php?id=<?= $review['game_id'] ?>">
                                    <?= h($review['title']) ?>
                                </a>
                            </td>
                            <td style="padding:0.7rem;text-align:center;color:var(--star-color);"><?= $review['rating'] ?>/10</td>
                            <td style="padding:0.7rem;text-align:center;"><?= date('d/m/Y', strtotime($review['created_at'])) ?></td>
                            <td style="padding:0.7rem;text-align:center;"><?= $review['has_spoiler'] ? '👁' : '—' ?></td>
                            <td style="padding:0.7rem;text-align:center;">
                                <a href="<?= SITE_URL ?>/game.php?id=<?= $review['game_id'] ?>#review-<?= $review['id'] ?>" 
                                   class="btn-secondary" style="font-size:0.75rem;padding:0.3rem 0.6rem;">Editar</a>
                                <a href="?delete_review=<?= $review['id'] ?>" 
                                   class="btn-danger" style="font-size:0.75rem;padding:0.3rem 0.6rem;"
                                   onclick="return confirm('Excluir esta análise?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color:var(--text-muted);">Você ainda não publicou nenhuma análise. <a href="<?= SITE_URL ?>/catalog.php">Explore o catálogo!</a></p>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/footer.php'; ?>