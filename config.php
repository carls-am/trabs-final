<?php
session_start();

//conectar cm o mysql, porta do xampp (?)
//define = constante = variavel que nao varia
//dados de acesso do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'starpad_db');
define('DB_USER', 'root');
define('DB_PASS', '');
// site url é influenciado pelo nome da pasta
define('SITE_URL', 'http://localhost/starpad');
//redireciona diretório de upload de pfp, com dir
define('UPLOAD_DIR', __DIR__ . '/uploads/avatars/');
//so pra ter ctz q um maluco nao vai botar o roteiro de bee movie no site
define('MAX_REVIEW_CHARS', 2000);
//requisito nao funcional de tema padrao escuro
define('DEFAULT_THEME', 'dark');

//singleton = controle de tráfico no banco de dados e evita 30 mil consultas em meio segundo
//PDO = php data object = conexao chique
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                //errmode = em caso de erro, achar o erro
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                //transforma saídas do bd em uma string fofíssima
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                //evita sql injection
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die("Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    }
    return $pdo;
}

//ve se ta logado, se nao tiver, pede pro usuário fazer login
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

//pede o login, mas salva onde o usuário tava
function requireLogin(): void {
    if (!isLoggedIn()) {
        //urlencode salva onde o usuário tava pra botar ele devolta dps
        header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

//evita sql injection de javascript
function h(?string $str): string {
    //htmlsepcialchars converte texto em html
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Tema atual (cookie/padrão)
function getCurrentTheme(): string {
    //bota o tema dark
    return $_COOKIE['theme'] ?? DEFAULT_THEME;
}