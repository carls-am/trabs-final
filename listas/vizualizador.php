<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/api.php';

require_method(['GET']);

$usuarioId = current_user_id();
$listaId = int_value($_GET, 'id');

try {
    $stmt = $pdo->prepare(
        '
        SELECT
            l.id,
            l.usuario_id,
            u.username,
            u.nome AS usuario_nome,
            l.nome,
            l.descricao,
            l.privada,
            l.criada_em
        FROM listas l
        INNER JOIN usuarios u ON u.id = l.usuario_id
        WHERE l.id = :id
        LIMIT 1
        '
    );
    $stmt->execute([':id' => $listaId]);
    $lista = $stmt->fetch();

    if (!$lista) {
        json_response(['erro' => 'Lista nao encontrada.'], 404);
    }

    $privada = in_array($lista['privada'], [true, 1, '1', 't', 'true'], true);

    if ($privada && $usuarioId !== (int) $lista['usuario_id']) {
        json_response(['erro' => 'Acesso negado.'], 403);
    }

    $jogosStmt = $pdo->prepare(
        '
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
            j.plataforma
        FROM lista_jogos lj
        INNER JOIN jogos j ON j.id = lj.jogo_id
        WHERE lj.lista_id = :lista_id
        ORDER BY j.nome ASC
        '
    );
    $jogosStmt->execute([':lista_id' => $listaId]);

    json_response([
        'data' => [
            'lista' => $lista,
            'jogos' => $jogosStmt->fetchAll(),
        ],
    ]);
} catch (PDOException $e) {
    error_log('Falha ao visualizar lista.');
    json_response(['erro' => 'Nao foi possivel carregar a lista.'], 500);
}
