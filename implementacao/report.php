<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$type = $_GET['type'] ?? '';
$entityId = (int)($_GET['id'] ?? 0);
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO reports (reporter_id, entity_type, entity_id, reason) VALUES (?,?,?,?)");
    $stmt->execute([$_SESSION['user_id'], $type, $entityId, $reason]);
    $message = 'Denúncia enviada. Obrigado por ajudar a comunidade!';
    $messageType = 'success';
}

include __DIR__ . '/header.php';
?>

<div class="form-container">
    <h2>Denunciar</h2>
    <?php if ($message): ?>
        <div class="message <?= $messageType ?>"><?= h($message) ?></div>
        <?php if ($messageType === 'success'): ?>
            <a href="<?= SITE_URL ?>/index.php" class="btn-primary" style="display:block;text-align:center;">Voltar ao Início</a>
        <?php endif; ?>
    <?php else: ?>
        <p style="color:var(--text-secondary);margin-bottom:1rem;">
            Denunciando <?= h($type) ?> #<?= $entityId ?>. Descreva o motivo:
        </p>
        <form method="POST">
            <div class="form-group">
                <textarea name="reason" required placeholder="Ex: Conteúdo ofensivo, spam, etc." rows="4"></textarea>
            </div>
            <button type="submit" class="btn-danger" style="width:100%;">Enviar Denúncia</button>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>