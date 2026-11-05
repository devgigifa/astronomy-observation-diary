<?php
// ============================================================
// export.php — Exporta as observações como arquivo CSV
// O navegador faz o download do arquivo automaticamente
// ============================================================

require_once 'db.php';

// ============================================================
// FILTROS (os mesmos de observations.php para exportar o que está sendo visto)
// ============================================================
$where_clauses = []; // Array com condições SQL que serão montadas dinamicamente
$params        = []; // Parâmetros que serão ligados ao prepared statement

// Filtro por mês/ano: espera o formato "YYYY-MM" (ex: "2026-10")
if (!empty($_GET['month'])) {
    $month = $_GET['month'];
    // DATE_FORMAT converte a data para "YYYY-MM" para comparação
    $where_clauses[] = "DATE_FORMAT(obs_date, '%Y-%m') = :month";
    $params[':month'] = $month;
}

// Filtro por nome do objeto: busca parcial com LIKE e curingas %
if (!empty($_GET['object'])) {
    $where_clauses[] = "object_name LIKE :object";
    // O % antes e depois significa "qualquer coisa antes e depois do termo"
    $params[':object'] = '%' . $_GET['object'] . '%';
}

// Monta a cláusula WHERE se houver filtros, ou string vazia se não houver
$where_sql = count($where_clauses) > 0
    ? 'WHERE ' . implode(' AND ', $where_clauses)
    : '';

// ============================================================
// CONSULTA AO BANCO
// ============================================================
$stmt = $pdo->prepare("
    SELECT obs_date, location, object_name, sky_conditions, notes, created_at
    FROM observations
    $where_sql
    ORDER BY obs_date DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll(); // fetchAll() retorna todas as linhas de uma vez como array

// ============================================================
// CABEÇALHOS HTTP PARA DOWNLOAD
// Instruem o navegador a tratar a resposta como um arquivo para download
// ============================================================
$filename = 'observations_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
// Content-Disposition: attachment diz ao navegador para baixar (não exibir) o arquivo
header('Content-Disposition: attachment; filename="' . $filename . '"');
// Evita que proxies façam cache do arquivo
header('Pragma: no-cache');
header('Expires: 0');

// ============================================================
// GERAÇÃO DO CSV
// Escrevemos diretamente na saída padrão (php://output)
// fputcsv() formata um array como uma linha CSV corretamente
// (adiciona aspas, escapa vírgulas, etc.)
// ============================================================
$output = fopen('php://output', 'w');

// Adiciona BOM (Byte Order Mark) para que o Excel abra corretamente com acentos
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Linha de cabeçalho do CSV
fputcsv($output, ['Date', 'Location', 'Object', 'Sky Conditions', 'Notes', 'Registered At']);

// Linhas de dados: percorre cada observação com foreach
foreach ($rows as $row) {
    fputcsv($output, [
        $row['obs_date'],
        $row['location'],
        $row['object_name'],
        $row['sky_conditions'],
        $row['notes'],
        $row['created_at'],
    ]);
}

fclose($output); // Fecha o "arquivo" de saída
exit; // Encerra o script imediatamente após o download
