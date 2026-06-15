<?php

declare(strict_types=1);

require_once '../config/database.php';

function json_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function normalize_bool(mixed $value): bool
{
    return $value === true || $value === 1 || $value === '1' || $value === 't' || $value === 'true';
}

function normalize_jogo(array $jogo): array
{
    $tags = [];

    if (isset($jogo['tags'])) {
        $decodedTags = json_decode((string) $jogo['tags'], true);
        $tags = is_array($decodedTags) ? $decodedTags : [];
    }

    return [
        'id' => (int) $jogo['id'],
        'nome' => $jogo['nome'],
        'descricao' => $jogo['descricao'],
        'capa' => $jogo['capa'],
        'banner' => $jogo['banner'],
        'data_lancamento' => $jogo['data_lancamento'],
        'desenvolvedora' => $jogo['desenvolvedora'],
        'publisher' => $jogo['publisher'],
        'genero' => $jogo['genero'],
        'plataforma' => $jogo['plataforma'],
        'tags' => $tags,
        'criado_em' => $jogo['criado_em'],
    ];
}

function normalize_review(array $review): array
{
    return [
        'id' => (int) $review['id'],
        'usuario' => [
            'id' => (int) $review['usuario_id'],
            'username' => $review['username'],
            'nome' => $review['nome'],
            'avatar' => $review['avatar'],
        ],
        'nota' => $review['nota'] !== null ? (float) $review['nota'] : null,
        'review' => $review['review'],
        'spoiler' => normalize_bool($review['spoiler']),
        'criado_em' => $review['criado_em'],
        'total_likes' => (int) $review['total_likes'],
        'total_comentarios' => (int) $review['total_comentarios'],
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    json_response(['erro' => 'Metodo nao permitido.'], 405);
}

$jogoId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($jogoId === false) {
    json_response(['erro' => 'Jogo invalido.'], 400);
}

try {
    $stmt = $pdo->prepare(
        "
        SELECT
            j.id,
            j.nome,
            j.descricao,
            j.capa,
            j.banner,
            j.data_lancamento,
            j.desenvolvedora,
            j.publisher,
            j.genero,
            j.plataforma,
            (
                SELECT COALESCE(json_agg(t.nome ORDER BY t.nome), '[]'::json)
                FROM jogo_tags jt
                INNER JOIN tags t ON t.id = jt.tag_id
                WHERE jt.jogo_id = j.id
            ) AS tags,
            j.criado_em,
            COUNT(r.id) AS total_reviews,
            ROUND(AVG(r.nota), 2) AS media_nota
        FROM jogos j
        LEFT JOIN reviews r ON r.jogo_id = j.id
        WHERE j.id = :id
        GROUP BY
            j.id,
            j.nome,
            j.descricao,
            j.capa,
            j.banner,
            j.data_lancamento,
            j.desenvolvedora,
            j.publisher,
            j.genero,
            j.plataforma,
            j.criado_em
        LIMIT 1
        "
    );
    $stmt->bindValue(':id', $jogoId, PDO::PARAM_INT);
    $stmt->execute();
    $jogo = $stmt->fetch();

    if (!$jogo) {
        json_response(['erro' => 'Jogo nao encontrado.'], 404);
    }

    $reviewsStmt = $pdo->prepare(
        '
        SELECT
            r.id,
            r.usuario_id,
            u.username,
            u.nome,
            u.avatar,
            r.nota,
            r.review,
            r.spoiler,
            r.criado_em,
            COUNT(DISTINCT rl.usuario_id) AS total_likes,
            COUNT(DISTINCT c.id) AS total_comentarios
        FROM reviews r
        INNER JOIN usuarios u ON u.id = r.usuario_id
        LEFT JOIN review_likes rl ON rl.review_id = r.id
        LEFT JOIN comentarios c ON c.review_id = r.id
        WHERE r.jogo_id = :id
        GROUP BY
            r.id,
            r.usuario_id,
            u.username,
            u.nome,
            u.avatar,
            r.nota,
            r.review,
            r.spoiler,
            r.criado_em
        ORDER BY r.criado_em DESC
        LIMIT 10
        '
    );
    $reviewsStmt->bindValue(':id', $jogoId, PDO::PARAM_INT);
    $reviewsStmt->execute();

    json_response([
        'data' => [
            'jogo' => normalize_jogo($jogo),
            'estatisticas' => [
                'total_reviews' => (int) $jogo['total_reviews'],
                'media_nota' => $jogo['media_nota'] !== null ? (float) $jogo['media_nota'] : null,
            ],
            'reviews_recentes' => array_map('normalize_review', $reviewsStmt->fetchAll()),
        ],
    ]);
} catch (PDOException $e) {
    error_log('Falha ao carregar detalhes do jogo.');
    json_response(['erro' => 'Nao foi possivel carregar o jogo.'], 500);
}
