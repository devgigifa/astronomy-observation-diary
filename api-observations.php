<?php
// ============================================================
// api-observations.php — Endpoint JSON para requisições AJAX
// Retorna observações filtradas em formato JSON
// Chamado pelo JavaScript em observations.php via fetch()
// ============================================================

// Define o tipo da resposta como JSON para o navegador interpretar corretamente
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

// ============================================================
// CONSTRUÇÃO DINÂMICA DA QUERY COM FILTROS
// Técnica: acumular condições em array e juntar com implode()
// ============================================================
$where_clauses = [];
$params        = [];

// Filtro por mês (formato esperado: "YYYY-MM")
if (!empty($_GET['month'])) {
    $where_clauses[] = "DATE_FORMAT(obs_date, '%Y-%m') = :month";
    $params[':month'] = $_GET['month'];
}

// Filtro por objeto (busca parcial, case-insensitive no MySQL)
if (!empty($_GET['object'])) {
    $where_clauses[] = "object_name LIKE :object";
    $params[':object'] = '%' . $_GET['object'] . '%';
}

$where_sql = count($where_clauses) > 0
    ? 'WHERE ' . implode(' AND ', $where_clauses)
    : '';

try {
    $stmt = $pdo->prepare("
        SELECT id, obs_date, location, object_name, sky_conditions, notes, photo_path, created_at
        FROM observations
        $where_sql
        ORDER BY obs_date DESC
    ");
    $stmt->execute($params);
    $observations = $stmt->fetchAll();

    // json_encode() converte o array PHP em uma string JSON
    // JSON_UNESCAPED_UNICODE mantém acentos legíveis (sem ã etc.)
    echo json_encode([
        'success' => true,
        'count'   => count($observations), // Quantidade de registros encontrados
        'data'    => $observations,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Em caso de erro, retorna JSON com mensagem de erro e código HTTP 500
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar observações.',
    ]);
}
