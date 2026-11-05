<?php
// ============================================================
// insert.php — Recebe o formulário e salva a observação no banco
// Chamado via método POST pelo formulário em new-observation.php
// ============================================================

// Inclui a conexão com o banco (variável $pdo fica disponível aqui)
require_once 'db.php';

// Verifica se a requisição é do tipo POST
// Isso impede que alguém acesse insert.php diretamente pelo navegador
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: new-observation.php');
    exit;
}

// ============================================================
// COLETA E SANITIZAÇÃO DOS DADOS DO FORMULÁRIO
// $_POST contém os campos enviados pelo formulário HTML
// trim() remove espaços em branco do início e fim da string
// ============================================================
$obs_date       = trim($_POST['obs_date']       ?? '');
$location       = trim($_POST['location']       ?? '');
$object_name    = trim($_POST['object_name']    ?? '');
$sky_conditions = trim($_POST['sky_conditions'] ?? '');
$notes          = trim($_POST['notes']          ?? '');

// Validação básica dos campos obrigatórios
// Se qualquer campo obrigatório estiver vazio, volta para o formulário com erro
if (empty($obs_date) || empty($location) || empty($object_name) || empty($sky_conditions)) {
    header('Location: new-observation.php?error=missing_fields');
    exit;
}

// Valida se sky_conditions é um dos valores permitidos pelo ENUM no banco
$allowed_conditions = ['excellent', 'good', 'regular', 'poor'];
if (!in_array($sky_conditions, $allowed_conditions)) {
    header('Location: new-observation.php?error=invalid_condition');
    exit;
}

// ============================================================
// UPLOAD DA FOTO (opcional)
// $_FILES contém informações sobre arquivos enviados pelo formulário
// ============================================================
$photo_path = null; // Valor padrão: sem foto

// Verifica se um arquivo foi enviado e se não houve erro no upload
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {

    $file     = $_FILES['photo'];
    $max_size = 5 * 1024 * 1024; // 5 MB em bytes

    // Verifica o tamanho do arquivo
    if ($file['size'] > $max_size) {
        header('Location: new-observation.php?error=file_too_large');
        exit;
    }

    // Verifica o tipo do arquivo usando a extensão
    // pathinfo() retorna partes do caminho do arquivo; PATHINFO_EXTENSION = extensão
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowed_extensions)) {
        header('Location: new-observation.php?error=invalid_file_type');
        exit;
    }

    // Gera um nome único para o arquivo para evitar sobrescrever uploads anteriores
    // uniqid() gera uma string baseada no tempo atual; date() adiciona a data
    $new_filename = date('Y-m-d') . '_' . uniqid() . '.' . $extension;
    $destination  = __DIR__ . '/uploads/' . $new_filename;

    // move_uploaded_file() move o arquivo da pasta temporária para a pasta definitiva
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Salva apenas o caminho relativo (não o absoluto) no banco
        $photo_path = 'uploads/' . $new_filename;
    }
}

// ============================================================
// INSERÇÃO NO BANCO DE DADOS COM PREPARED STATEMENT
// Prepared statements evitam SQL Injection:
// os valores do usuário são separados da query SQL
// ============================================================
try {
    // prepare() compila a query com placeholders (:nome_do_campo)
    $stmt = $pdo->prepare("
        INSERT INTO observations (obs_date, location, object_name, sky_conditions, notes, photo_path)
        VALUES (:obs_date, :location, :object_name, :sky_conditions, :notes, :photo_path)
    ");

    // execute() substitui os placeholders pelos valores reais de forma segura
    $stmt->execute([
        ':obs_date'       => $obs_date,
        ':location'       => $location,
        ':object_name'    => $object_name,
        ':sky_conditions' => $sky_conditions,
        ':notes'          => $notes ?: null, // Converte string vazia em NULL
        ':photo_path'     => $photo_path,
    ]);

    // Redireciona para a lista com mensagem de sucesso
    header('Location: observations.php?success=1');
    exit;

} catch (PDOException $e) {
    // Em caso de erro no banco, redireciona com mensagem de erro
    header('Location: new-observation.php?error=db_error');
    exit;
}
