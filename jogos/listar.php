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

function query_string(string $name, int $maxLength): string
{
    $value = trim((string) ($_GET[$name] ?? ''));

    if (strlen($value) > $maxLength) {
        json_response([
            'erro' => sprintf('Parametro %s muito longo.', $name),
        ], 400);
    }

    return $value;
}

function query_int(string $name, int $default, int $min, int $max): int
{
    if (!isset($_GET[$name]) || $_GET[$name] === '') {
        return $default;
    }

    $value = filter_var($_GET[$name], FILTER_VALIDATE_INT);

    if ($value === false || $value < $min || $value > $max) {
        json_response([
            'erro' => sprintf('Parametro %s invalido.', $name),
        ], 400);
    }

    return $value;
}

function bind_params(PDOStatement $stmt, array $params): void
{
    foreach ($params as $name => $value) {
        $stmt->bindValue($name, $value);
    }
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
        'total_reviews' => (int) $jogo['total_reviews'],
        'media_nota' => $jogo['media_nota'] !== null ? (float) $jogo['media_nota'] : null,
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    json_response(['erro' => 'Metodo nao permitido.'], 405);
}

$q = query_string('q', 120);
$genero = query_string('genero', 80);
$plataforma = query_string('plataforma', 80);
$tag = query_string('tag', 80);
$limit = query_int('limit', 20, 1, 50);
$offset = query_int('offset', 0, 0, 1000);

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(j.nome ILIKE :q_nome OR j.descricao ILIKE :q_descricao OR j.desenvolvedora ILIKE :q_desenvolvedora)';
    $params[':q_nome'] = '%' . $q . '%';
    $params[':q_descricao'] = '%' . $q . '%';
    $params[':q_desenvolvedora'] = '%' . $q . '%';
}

if ($genero !== '') {
    $where[] = 'j.genero ILIKE :genero';
    $params[':genero'] = '%' . $genero . '%';
}

if ($plataforma !== '') {
    $where[] = 'j.plataforma ILIKE :plataforma';
    $params[':plataforma'] = '%' . $plataforma . '%';
}

if ($tag !== '') {
    $where[] = '
        EXISTS (
            SELECT 1
            FROM jogo_tags jt
            INNER JOIN tags t ON t.id = jt.tag_id
            WHERE jt.jogo_id = j.id
                AND t.nome ILIKE :tag
        )
    ';
    $params[':tag'] = '%' . $tag . '%';
}

$whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countStmt = $pdo->prepare(
        "
        SELECT COUNT(1) AS total
        FROM jogos j
        $whereSql
        "
    );
    bind_params($countStmt, $params);
    $countStmt->execute();
    $total = (int) $countStmt->fetch()['total'];

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
            COUNT(r.id) AS total_reviews,
            ROUND(AVG(r.nota), 2) AS media_nota
        FROM jogos j
        LEFT JOIN reviews r ON r.jogo_id = j.id
        $whereSql
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
            j.plataforma
        ORDER BY j.nome ASC
        LIMIT :limit OFFSET :offset
        "
    );

    bind_params($stmt, $params);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $jogos = array_map('normalize_jogo', $stmt->fetchAll());

    json_response([
        'data' => $jogos,
        'meta' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ],
    ]);
} catch (PDOException $e) {
    error_log('Falha ao listar jogos.');
    json_response(['erro' => 'Nao foi possivel listar os jogos.'], 500);
}
