<?php
// ============================================================
// new-observation.php — Formulário para registrar nova observação
// ============================================================

// Lê o parâmetro de erro da URL, se houver (vindo de insert.php)
// Ex: new-observation.php?error=missing_fields
$error = $_GET['error'] ?? null;

// Mapeia os códigos de erro para mensagens legíveis
$error_messages = [
    'missing_fields'    => 'Please fill in all required fields.',
    'invalid_condition' => 'Invalid sky condition selected.',
    'file_too_large'    => 'Photo must be smaller than 5 MB.',
    'invalid_file_type' => 'Only JPG, PNG, GIF and WEBP images are allowed.',
    'db_error'          => 'A database error occurred. Please try again.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Astronomy Diary — New Observation</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="index.php" class="site-logo">✦ Astronomy <span>Diary</span></a>
        <nav>
            <ul class="site-nav">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="observations.php">Observations</a></li>
                <li><a href="new-observation.php" class="active">+ New</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="page-main">
    <h1 class="page-title">New Observation</h1>

    <!-- Exibe mensagem de erro se houver um código na URL -->
    <?php if ($error && isset($error_messages[$error])): ?>
        <div class="alert alert-error">
            ⚠ <?= htmlspecialchars($error_messages[$error]) ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         FORMULÁRIO PRINCIPAL
         action="insert.php" → envia para o script PHP que processa
         method="post"       → dados no corpo da requisição (não na URL)
         enctype="multipart/form-data" → OBRIGATÓRIO quando há upload de arquivo
         ============================================================ -->
    <div class="card">
        <form action="insert.php" method="post" enctype="multipart/form-data" id="obs-form" novalidate>

            <div class="form-grid">

                <!-- Campo: Data da observação -->
                <div class="form-group">
                    <label for="obs_date">Observation Date *</label>
                    <!-- type="date" exibe um seletor de data nativo do navegador -->
                    <input type="date"
                           id="obs_date"
                           name="obs_date"
                           required
                           max="<?= date('Y-m-d') ?>"> <!-- max = hoje (não permite datas futuras) -->
                </div>

                <!-- Campo: Local da observação -->
                <div class="form-group">
                    <label for="location">Location *</label>
                    <input type="text"
                           id="location"
                           name="location"
                           placeholder="e.g. Parque Estadual, São Paulo"
                           maxlength="150"
                           required>
                </div>

                <!-- Campo: Nome do objeto celeste -->
                <div class="form-group">
                    <label for="object_name">Object Observed *</label>
                    <input type="text"
                           id="object_name"
                           name="object_name"
                           placeholder="e.g. Saturn, Orion Nebula, Moon"
                           maxlength="100"
                           required>
                    <!-- Datalist: sugestões ao digitar (não obrigatório escolher uma delas) -->
                    <datalist id="common-objects">
                        <option value="Moon">
                        <option value="Sun">
                        <option value="Mercury">
                        <option value="Venus">
                        <option value="Mars">
                        <option value="Jupiter">
                        <option value="Saturn">
                        <option value="Uranus">
                        <option value="Neptune">
                        <option value="Orion Nebula">
                        <option value="Andromeda Galaxy">
                        <option value="Pleiades">
                        <option value="Milky Way">
                    </datalist>
                </div>

                <!-- Campo: Condições do céu (select = menu dropdown) -->
                <div class="form-group">
                    <label for="sky_conditions">Sky Conditions *</label>
                    <select id="sky_conditions" name="sky_conditions" required>
                        <!-- value="" é inválido — força o usuário a escolher -->
                        <option value="" disabled selected>Select condition...</option>
                        <option value="excellent">🟢 Excellent — Perfect transparency</option>
                        <option value="good">🔵 Good — Slight interference</option>
                        <option value="regular">🟡 Regular — Moderate clouds/light pollution</option>
                        <option value="poor">🔴 Poor — Heavy interference</option>
                    </select>
                </div>

                <!-- Campo: Notas (textarea = campo de texto longo) -->
                <div class="form-group form-full">
                    <label for="notes">Notes</label>
                    <textarea id="notes"
                              name="notes"
                              placeholder="Describe what you saw, equipment used, magnification..."
                              maxlength="2000"></textarea>
                    <span class="form-hint">Optional. Max 2000 characters.</span>
                </div>

                <!-- Campo: Upload de foto -->
                <div class="form-group form-full">
                    <label for="photo">Photo</label>
                    <input type="file"
                           id="photo"
                           name="photo"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <span class="form-hint">Optional. JPG, PNG, GIF or WEBP. Max 5 MB.</span>
                    <!-- Área de preview da foto antes de enviar (preenchida pelo JS) -->
                    <div id="photo-preview" style="margin-top:0.75rem; display:none">
                        <img id="preview-img" src="" alt="Preview"
                             style="max-height:180px; border-radius:var(--radius-sm)">
                    </div>
                </div>

            </div><!-- /form-grid -->

            <!-- Botões de ação -->
            <div style="display:flex; gap:0.75rem; margin-top:1.75rem; flex-wrap:wrap">
                <button type="submit" class="btn btn-primary">✦ Save Observation</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </div><!-- /card -->
</main>

<!-- ============================================================
     JAVASCRIPT — Funcionalidades do formulário
     ============================================================ -->
<script>
// ------------------------------------------------------------
// 1. PREVIEW DA FOTO ANTES DO UPLOAD
// Quando o usuário seleciona um arquivo, mostra uma prévia
// ------------------------------------------------------------

// document.getElementById() busca um elemento pelo atributo id no DOM
const photoInput   = document.getElementById('photo');
const previewBox   = document.getElementById('photo-preview');
const previewImg   = document.getElementById('preview-img');

// addEventListener escuta um evento; aqui escuta "change" no input de arquivo
photoInput.addEventListener('change', function () {
    // this.files é um FileList com os arquivos selecionados
    const file = this.files[0];

    if (!file) {
        // Se não selecionou nada, esconde o preview
        previewBox.style.display = 'none';
        return;
    }

    // FileReader lê o arquivo como URL de dados (base64) para exibir no <img>
    const reader = new FileReader();

    // onload é chamado quando a leitura termina
    reader.onload = function (e) {
        previewImg.src = e.target.result; // Define o src da imagem com os dados lidos
        previewBox.style.display = 'block';
    };

    reader.readAsDataURL(file); // Inicia a leitura do arquivo
});

// ------------------------------------------------------------
// 2. DEFINIR DATA PADRÃO COMO HOJE
// Preenche o campo de data com a data atual ao carregar a página
// ------------------------------------------------------------

// Quando o DOM estiver completamente carregado...
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('obs_date');

    if (!dateInput.value) {
        // new Date() retorna a data/hora atual
        // toISOString() retorna "2026-11-03T22:00:00.000Z"
        // slice(0, 10) pega apenas os 10 primeiros caracteres: "2026-11-03"
        const today = new Date().toISOString().slice(0, 10);
        dateInput.value = today;
    }
});

// ------------------------------------------------------------
// 3. VALIDAÇÃO DO FORMULÁRIO NO CLIENTE (FRONT-END)
// Verifica campos antes de enviar para o servidor
// Obs: a validação do servidor (insert.php) é mais segura e obrigatória
// A validação client-side é uma melhoria de UX (experiência do usuário)
// ------------------------------------------------------------

const form = document.getElementById('obs-form');

form.addEventListener('submit', function (event) {
    // Pega os valores dos campos
    const date      = document.getElementById('obs_date').value.trim();
    const location  = document.getElementById('location').value.trim();
    const objName   = document.getElementById('object_name').value.trim();
    const condition = document.getElementById('sky_conditions').value;

    // Valida se os campos obrigatórios estão preenchidos
    if (!date || !location || !objName || !condition) {
        // preventDefault() cancela o envio do formulário
        event.preventDefault();
        alert('Please fill in all required fields (marked with *).');
        return;
    }

    // Valida tamanho do arquivo de foto, se selecionado
    const photoFile = photoInput.files[0];
    const MAX_SIZE  = 5 * 1024 * 1024; // 5 MB em bytes

    if (photoFile && photoFile.size > MAX_SIZE) {
        event.preventDefault();
        alert('Photo is too large. Maximum size is 5 MB.');
    }
});
</script>

</body>
</html>
