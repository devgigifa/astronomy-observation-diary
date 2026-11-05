<?php
// ============================================================
// delete.php — Remove uma observação do banco de dados
// Chamado quando o usuário clica no botão "Excluir" na tabela
// ============================================================

require_once 'db.php';

// Verifica se o parâmetro 'id' foi enviado na URL (método GET)
// Exemplo de URL: delete.php?id=5
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: observations.php?error=invalid_id');
    exit;
}

// (int) converte o valor para inteiro, eliminando qualquer tentativa de injeção
$id = (int) $_GET['id'];

try {
    // Busca a observação antes de deletar para verificar se existe
    // e também para recuperar o caminho da foto (se houver)
    $stmt = $pdo->prepare("SELECT photo_path FROM observations WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $observation = $stmt->fetch(); // fetch() retorna uma linha ou false se não encontrar

    if (!$observation) {
        header('Location: observations.php?error=not_found');
        exit;
    }

    // Se havia uma foto associada, remove o arquivo físico do servidor
    if (!empty($observation['photo_path'])) {
        $file_path = __DIR__ . '/' . $observation['photo_path'];

        // file_exists() verifica se o arquivo existe antes de tentar remover
        if (file_exists($file_path)) {
            unlink($file_path); // unlink() deleta um arquivo do sistema de arquivos
        }
    }

    // Deleta o registro do banco de dados
    $stmt = $pdo->prepare("DELETE FROM observations WHERE id = :id");
    $stmt->execute([':id' => $id]);

    header('Location: observations.php?success=deleted');
    exit;

} catch (PDOException $e) {
    header('Location: observations.php?error=db_error');
    exit;
}
