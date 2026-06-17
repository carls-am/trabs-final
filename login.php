<?php
require_once __DIR__ . '/functions.php';

$mode = $_GET['mode'] ?? 'login'; // 'login' ou 'register'
$redirect = $_GET['redirect'] ?? SITE_URL . '/index.php';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    
    if (($mode === 'register') && isset($_POST['username'], $_POST['password'], $_POST['confirm_password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm  = $_POST['confirm_password'];
        
        if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
            $message = 'Username deve ter entre 3 e 50 caracteres.';
            $messageType = 'error';
        } elseif ($password !== $confirm) {
            $message = 'As senhas não coincidem.';
            $messageType = 'error';
        } elseif (mb_strlen($password) < 4) {
            $message = 'A senha deve ter pelo menos 4 caracteres.';
            $messageType = 'error';
        } elseif (getUserByUsername($username)) {
            $message = 'Este username já está em uso.';
            $messageType = 'error';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);
            $userId = $db->lastInsertId();
            $_SESSION['user_id'] = $userId;
            header('Location: ' . $redirect);
            exit;
        }
    } elseif (($mode === 'login') && isset($_POST['username'], $_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $user = getUserByUsername($username);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: ' . $redirect);
            exit;
        } else {
            $message = 'Username ou senha incorretos.';
            $messageType = 'error';
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="form-container">
    <h2><?= $mode === 'register' ? 'Criar Conta' : 'Entrar no StarPad' ?></h2>
    
    <?php if ($message): ?>
        <div class="message <?= $messageType ?>"><?= h($message) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required minlength="3" maxlength="50" 
                   placeholder="Seu username" autocomplete="username">
        </div>
        <div class="form-group">
            <label for="password">Senha</label>
            <input type="password" name="password" id="password" required minlength="4" 
                   placeholder="Sua senha" autocomplete="<?= $mode === 'register' ? 'new-password' : 'current-password' ?>">
        </div>
        <?php if ($mode === 'register'): ?>
            <div class="form-group">
                <label for="confirm_password">Confirmar Senha</label>
                <input type="password" name="confirm_password" id="confirm_password" required minlength="4" 
                       placeholder="Repita a senha" autocomplete="new-password">
            </div>
        <?php endif; ?>
        
        <button type="submit" class="btn-primary" style="width:100%;">
            <?= $mode === 'register' ? 'Criar Conta' : 'Entrar' ?>
        </button>
    </form>
    
    <p style="text-align:center;margin-top:1.2rem;color:var(--text-secondary);">
        <?php if ($mode === 'register'): ?>
            Já tem conta? <a href="?mode=login&redirect=<?= urlencode($redirect) ?>">Faça login</a>
        <?php else: ?>
            Não tem conta? <a href="?mode=register&redirect=<?= urlencode($redirect) ?>">Crie uma agora</a>
        <?php endif; ?>
    </p>
    <p style="text-align:center;font-size:0.75rem;color:var(--text-muted);margin-top:0.5rem;">
        Apenas username e senha são necessários.
    </p>
</div>

<?php include __DIR__ . '/footer.php'; ?>