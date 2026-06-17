<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'starpad_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost/starpad');
define('UPLOAD_DIR', __DIR__ . '/uploads/avatars/');
define('MAX_REVIEW_CHARS', 2000);
define('DEFAULT_THEME', 'dark');

// Conexão PDO
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die("Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Verifica se usuário está logado
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// Redireciona se não estiver logado
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Sanitiza output
function h(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Tema atual (cookie/padrão)
function getCurrentTheme(): string {
    return $_COOKIE['theme'] ?? DEFAULT_THEME;
}