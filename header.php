<?php
require_once __DIR__ . '/config.php';
$currentTheme = getCurrentTheme();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= $currentTheme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StarPad — Reviews de Jogos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⭐</text></svg>">
</head>
<body class="<?= $currentTheme ?>-theme">

<header class="main-header">
    <div class="header-inner">
        <a href="<?= SITE_URL ?>/index.php" class="logo">
            <span class="logo-icon">⭐</span>
            <span class="logo-text">StarPad</span>
        </a>
        
        <nav class="main-nav">
            <a href="<?= SITE_URL ?>/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Início</a>
            <a href="<?= SITE_URL ?>/catalog.php" class="<?= $currentPage === 'catalog.php' ? 'active' : '' ?>">Catálogo</a>
            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/my_lists.php" class="<?= $currentPage === 'my_lists.php' ? 'active' : '' ?>">Minhas Listas</a>
            <?php endif; ?>
        </nav>
        
        <div class="header-actions">
            <!-- Busca -->
            <form class="search-form" action="<?= SITE_URL ?>/catalog.php" method="GET">
                <input type="text" name="search" placeholder="Buscar jogos..." 
                       value="<?= h($_GET['search'] ?? '') ?>" aria-label="Buscar jogos">
                <button type="submit" aria-label="Pesquisar">🔍</button>
            </form>
            
            <!-- Toggle tema -->
            <button class="theme-toggle" onclick="toggleTheme()" aria-label="Alternar tema" title="Alternar tema claro/escuro">
                <span class="theme-icon-dark">🌙</span>
                <span class="theme-icon-light">☀️</span>
            </button>
            
            <!-- Usuário -->
            <?php if (isLoggedIn()): 
                $currentUser = getUser($_SESSION['user_id']);
            ?>
                <div class="user-menu">
                    <a href="<?= SITE_URL ?>/profile.php?id=<?= $_SESSION['user_id'] ?>" class="user-avatar-link">
                        <img src="<?= getAvatarUrl($currentUser['avatar_url'] ?? null, $currentUser['username']) ?>" 
                             alt="<?= h($currentUser['username']) ?>" class="avatar-small">
                        <span class="username-nav"><?= h($currentUser['username']) ?></span>
                    </a>
                    <a href="<?= SITE_URL ?>/my_profile.php" class="btn-edit-profile" title="Editar perfil">⚙️</a>
                    <a href="<?= SITE_URL ?>/logout.php" class="btn-logout">Sair</a>
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn-login">Entrar</a>
            <?php endif; ?>
        </div>
        
        <!-- Menu mobile -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Menu">☰</button>
    </div>
    <!-- Nav mobile (escondida por padrão) -->
    <div class="mobile-nav" id="mobileNav" style="display:none;">
        <a href="<?= SITE_URL ?>/index.php">Início</a>
        <a href="<?= SITE_URL ?>/catalog.php">Catálogo</a>
        <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/my_lists.php">Minhas Listas</a>
            <a href="<?= SITE_URL ?>/profile.php?id=<?= $_SESSION['user_id'] ?>">Meu Perfil</a>
            <a href="<?= SITE_URL ?>/my_profile.php">Configurações</a>
            <a href="<?= SITE_URL ?>/logout.php">Sair</a>
        <?php else: ?>
            <a href="<?= SITE_URL ?>/login.php">Entrar / Registrar</a>
        <?php endif; ?>
    </div>
</header>
<main class="main-content">