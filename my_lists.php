<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$db = getDB();
$message = '';
$messageType = '';

// Criar lista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_list') {
    $name = trim($_POST['list_name'] ?? '');
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    if (mb_strlen($name) >= 1) {
        $stmt = $db->prepare("INSERT INTO user_lists (user_id, name, is_public) VALUES (?,?,?)");
        $stmt->execute([$_SESSION['user_id'], $name, $isPublic]);
        $message = 'Lista criada!';
        $messageType = 'success';
    }
}

// Excluir lista
if (isset($_GET['delete_list'])) {
    $listId = (int)$_GET['delete_list'];
    $stmt = $db->prepare("DELETE FROM user_lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$listId, $_SESSION['user_id']]);
    $message = 'Lista excluída.';
    $messageType = 'success';
}

$userLists = getUserLists($_SESSION['user_id']);
$allGames = searchGames(null, null, null, null, 200);

include __DIR__ . '/header.php';
?>

<?php if ($message): ?>
    <div class="message <?= $messageType ?>"><?= h($message) ?></div>
<?php endif; ?>

<h1>📋 Minhas Listas</h1>

<!-- Criar nova lista -->
<div style="background:var(--bg-card);padding:1.5rem;border-radius:var(--radius);border:1px solid var(--border-color);margin:1.5rem 0;">
    <h3>➕ Nova Lista</h3>
    <form method="POST">
        <input type="hidden" name="action" value="create_list">
        <div style="display:flex;gap:1rem;align-items:end;flex-wrap:wrap;">
            <div class="form-group" style="flex:1;min-width:200px;margin:0;">
                <label for="list_name">Nome da lista:</label>
                <input type="text" name="list_name" id="list_name" required placeholder="Ex: Jogos Zerados 2026">
            </div>
            <div class="form-group" style="margin:0;">
                <label><input type="checkbox" name="is_public" value="1" checked> 🌐 Pública</label>
            </div>
            <button type="submit" class="btn-primary">Criar</button>
        </div>
    </form>
</div>

<!-- Listas existentes -->
<?php if ($userLists): ?>
    <?php foreach ($userLists as $list): 
        $stmt = $db->prepare("SELECT COUNT(*) FROM list_entries WHERE list_id = ?");
        $stmt->execute([$list['id']]);
        $gameCount = $stmt->fetchColumn();
        
        // Jogos da lista
        $stmt2 = $db->prepare(
            "SELECT g.* FROM games g JOIN list_entries le ON g.id = le.game_id WHERE le.list_id = ? ORDER BY le.added_at DESC"
        );
        $stmt2->execute([$list['id']]);
        $listGames = $stmt2->fetchAll();
    ?>
        <div class="list-card" style="margin-bottom:2rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
                <h3><?= h($list['name']) ?> 
                    <span style="font-size:0.8rem;color:var(--text-muted);">
                        (<?= $gameCount ?> jogos • <?= $list['is_public'] ? '🌐 Pública' : '🔒 Privada' ?>)
                    </span>
                </h3>
                <div>
                    <!-- Adicionar jogo -->
                    <form method="POST" action="<?= SITE_URL ?>/api.php?action=add_to_list" style="display:inline;">
                        <input type="hidden" name="list_id" value="<?= $list['id'] ?>">
                        <select name="game_id" style="padding:0.3rem;border-radius:4px;background:var(--input-bg);color:var(--text-primary);border:1px solid var(--input-border);">
                            <option value="">+ Adicionar jogo...</option>
                            <?php foreach ($allGames as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= h($g['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-secondary" style="font-size:0.8rem;padding:0.3rem 0.6rem;">OK</button>
                    </form>
                    <a href="?delete_list=<?= $list['id'] ?>" class="btn-danger" style="font-size:0.75rem;padding:0.3rem 0.6rem;" 
                       onclick="return confirm('Excluir esta lista?')">Excluir</a>
                </div>
            </div>
            
            <?php if ($listGames): ?>
                <div class="game-grid" style="margin-top:1rem;grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));">
                    <?php foreach ($listGames as $lg): ?>
                        <div class="game-card">
                            <a href="<?= SITE_URL ?>/game.php?id=<?= $lg['id'] ?>" class="game-card-link">
                                <img src="<?= h($lg['cover_image_url']) ?>" alt="<?= h($lg['title']) ?>" class="game-card-img"
                                     onerror="this.src='https://placehold.co/300x400/1a1a2e/e94560?text=Sem+Capa'">
                                <div class="game-card-body">
                                    <div class="game-card-title" style="font-size:0.8rem;"><?= h($lg['title']) ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color:var(--text-muted);margin-top:0.5rem;">Nenhum jogo nesta lista ainda.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="color:var(--text-muted);text-align:center;padding:2rem;">Você ainda não criou nenhuma lista. Crie uma acima! 📋</p>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>