<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$logado = !empty($_SESSION['usuario']);
?>
<nav>
    <a href="index.php">Inicio</a>
    <a href="jogos/listar.php">Jogos</a>
    <?php if ($logado): ?>
        <a href="perfil/perfil.php">Perfil</a>
        <a href="listas/minha_lista.php">Minhas listas</a>
        <a href="auth/logout.php">Sair</a>
    <?php else: ?>
        <a href="auth/login.php">Login</a>
        <a href="auth/cadastro.php">Cadastro</a>
    <?php endif; ?>
</nav>
