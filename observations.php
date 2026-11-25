<?php
// ============================================================
// observations.php — Lista todas as observações com filtros
// Os filtros são aplicados via AJAX (sem recarregar a página)
// A tabela também pode ser exportada como CSV
// ============================================================

// Lê mensagens de sucesso/erro vindas de redirect (insert.php ou delete.php)
$success = $_GET['success'] ?? null;
$error   = $_GET['error']   ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Astronomy Diary — Observations</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="index.php" class="site-logo">✦ Astronomy <span>Diary</span></a>
        <nav>
            <ul class="site-nav">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="observations.php" class="active">Observations</a></li>
                <li><a href="new-observation.php">+ New</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="page-main">
    <h1 class="page-title">Observations</h1>

    <!-- Mensagens de feedback (sucesso/erro de operações anteriores) -->
    <?php if ($success === '1'): ?>
        <div class="alert alert-success">✓ Observation saved successfully!</div>
    <?php elseif ($success === 'deleted'): ?>
        <div class="alert alert-success">✓ Observation deleted.</div>
    <?php elseif ($error): ?>
        <div class="alert alert-error">⚠ An error occurred. Please try again.</div>
    <?php endif; ?>

    <!-- ============================================================
         BARRA DE FILTROS
         Os inputs têm id e serão lidos pelo JavaScript para o AJAX
         ============================================================ -->
    <div class="filter-bar">
        <div class="form-group">
            <label for="filter-month">Filter by Month</label>
            <!-- type="month" exibe seletor de mês/ano nativo -->
            <input type="month" id="filter-month" placeholder="YYYY-MM">
        </div>

        <div class="form-group">
            <label for="filter-object">Filter by Object</label>
            <input type="text" id="filter-object" placeholder="e.g. Saturn">
        </div>

        <!-- Botões de ação dos filtros -->
        <div style="display:flex; gap:0.5rem; align-items:flex-end; padding-bottom:0.05rem">
            <button class="btn btn-primary" id="btn-filter">Search</button>
            <button class="btn btn-secondary" id="btn-clear">Clear</button>

            <!-- Link para exportar CSV; inclui os filtros atuais como parâmetros GET -->
            <a href="export.php" id="export-link" class="btn btn-secondary" style="white-space:nowrap">
                ↓ Export CSV
            </a>
        </div>
    </div>

    <!-- Exibe quantos resultados foram encontrados (atualizado pelo JS) -->
    <div class="results-info" id="results-info">Loading...</div>

    <!-- ============================================================
         TABELA DE RESULTADOS
         O <tbody> é preenchido dinamicamente pelo JavaScript via AJAX
         ============================================================ -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="table-hide-mobile">Location</th>
                    <th>Object</th>
                    <th class="table-hide-mobile">Sky</th>
                    <th class="table-hide-mobile">Notes</th>
                    <th>Photo</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <!-- tbody vazio: será preenchido pelo JS com os dados do servidor -->
            <tbody id="observations-body">
                <tr>
                    <td colspan="7" style="text-align:center; padding:2rem; color:var(--fg-muted)">
                        Loading observations...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</main>

<!-- ============================================================
     MODAL PARA VISUALIZAR FOTO EM TAMANHO MAIOR
     Aparece ao clicar na miniatura de uma foto
     ============================================================ -->
<div class="modal-overlay" id="photo-modal" role="dialog" aria-modal="true">
    <button class="modal-close" id="modal-close" aria-label="Close">×</button>
    <img class="modal-img" id="modal-img" src="" alt="Observation photo">
</div>

<!-- ============================================================
     JAVASCRIPT — AJAX, tabela dinâmica e modal
     ============================================================ -->
<script>
// ============================================================
// VARIÁVEIS GLOBAIS DO MÓDULO
// ============================================================

// Referências aos elementos do DOM que serão manipulados
const tbody        = document.getElementById('observations-body');
const resultsInfo  = document.getElementById('results-info');
const filterMonth  = document.getElementById('filter-month');
const filterObject = document.getElementById('filter-object');
const exportLink   = document.getElementById('export-link');
const modal        = document.getElementById('photo-modal');
const modalImg     = document.getElementById('modal-img');

// ============================================================
// FUNÇÃO PRINCIPAL: busca observações via AJAX
// AJAX = Asynchronous JavaScript and XML
// Permite buscar dados do servidor sem recarregar a página inteira
// ============================================================
async function loadObservations() {
    // Pega os valores atuais dos filtros
    const month  = filterMonth.value.trim();
    const object = filterObject.value.trim();

    // Monta os parâmetros de query string
    // URLSearchParams formata os parâmetros corretamente (escapa caracteres especiais)
    const params = new URLSearchParams();
    if (month)  params.set('month',  month);
    if (object) params.set('object', object);

    // Atualiza o link de exportação com os mesmos filtros
    exportLink.href = 'export.php' + (params.toString() ? '?' + params.toString() : '');

    // Mostra loading enquanto busca
    tbody.innerHTML = `
        <tr>
            <td colspan="7" style="text-align:center; padding:2rem; color:var(--fg-muted)">
                Searching...
            </td>
        </tr>`;

    try {
        // fetch() faz uma requisição HTTP assíncrona
        // await pausa a execução até a resposta chegar (sem travar a página)
        const url      = 'api-observations.php' + (params.toString() ? '?' + params.toString() : '');
        const response = await fetch(url);

        // Verifica se a requisição foi bem-sucedida (status 200-299)
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }

        // response.json() lê o corpo da resposta e converte de JSON para objeto JS
        const result = await response.json();

        if (!result.success) {
            throw new Error('API error: ' + result.message);
        }

        // Atualiza o contador de resultados
        const count = result.count;
        resultsInfo.innerHTML = `Showing <strong>${count}</strong> observation${count !== 1 ? 's' : ''}`;

        // Renderiza as linhas da tabela
        if (count === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align:center; padding:2.5rem; color:var(--fg-muted)">
                        No observations found. <a href="new-observation.php">Add your first!</a>
                    </td>
                </tr>`;
            return;
        }

        // Array.map() transforma cada observação em uma linha HTML
        // join('') une o array de strings em uma só string
        tbody.innerHTML = result.data.map(obs => buildRow(obs)).join('');

    } catch (err) {
        // Exibe mensagem de erro na tabela se algo falhar
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align:center; padding:2rem; color:#f87171">
                    Error loading data. Please refresh the page.
                </td>
            </tr>`;
        console.error('Error loading observations:', err);
    }
}

// ============================================================
// FUNÇÃO: Constrói o HTML de uma linha da tabela
// Recebe um objeto com os dados de uma observação
// Template literals (crase) permitem interpolação de variáveis com ${...}
// ============================================================
function buildRow(obs) {
    // Formata a data de "2026-10-15" para "15 Oct 2026"
    const dateFormatted = new Date(obs.obs_date + 'T00:00:00')
        .toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

    // Trunca notas longas para caber na célula da tabela
    const notesText = obs.notes
        ? (obs.notes.length > 80 ? obs.notes.substring(0, 80) + '...' : obs.notes)
        : '<span style="color:var(--fg-muted)">—</span>';

    // Célula da foto: miniatura clicável ou ícone de "sem foto"
    const photoCell = obs.photo_path
        ? `<img src="${escapeHtml(obs.photo_path)}"
                class="photo-thumb"
                alt="Observation photo"
                data-src="${escapeHtml(obs.photo_path)}"
                title="Click to enlarge">`
        : `<div class="no-photo" title="No photo">☽</div>`;

    // Retorna a string HTML da linha completa
    return `
        <tr>
            <td>${escapeHtml(dateFormatted)}</td>
            <td class="table-hide-mobile">${escapeHtml(obs.location)}</td>
            <td><strong>${escapeHtml(obs.object_name)}</strong></td>
            <td class="table-hide-mobile">
                <span class="sky-badge ${escapeHtml(obs.sky_conditions)}">
                    ${escapeHtml(obs.sky_conditions)}
                </span>
            </td>
            <td class="table-hide-mobile"
                style="max-width:220px; color:var(--fg-muted); font-size:0.85rem">
                ${notesText}
            </td>
            <td>${photoCell}</td>
            <td>
                <a href="delete.php?id=${obs.id}"
                   class="btn btn-danger"
                   onclick="return confirmDelete(event, this.href)">
                   Delete
                </a>
            </td>
        </tr>`;
}

// ============================================================
// FUNÇÃO: Escapa caracteres HTML para prevenir XSS
// Converte <, >, ", ' e & em entidades HTML seguras
// IMPORTANTE: sempre escape dados vindos do servidor ao inserir no DOM
// ============================================================
function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ============================================================
// FUNÇÃO: Confirmação de exclusão
// Substitui o alert/confirm nativo (que não funciona em alguns contextos)
// ============================================================
function confirmDelete(event, href) {
    event.preventDefault(); // Cancela a navegação imediata

    // Cria um diálogo simples inline na página
    if (window.confirm('Delete this observation? This cannot be undone.')) {
        window.location.href = href; // Navega apenas se confirmar
    }
    return false;
}

// ============================================================
// MODAL PARA VISUALIZAR FOTOS
// Usa delegação de eventos: um único listener no tbody
// captura cliques em qualquer miniatura de foto
// ============================================================

// Event delegation: ouve cliques no tbody inteiro
tbody.addEventListener('click', function (event) {
    // event.target é o elemento que foi clicado
    const thumb = event.target.closest('.photo-thumb');
    if (!thumb) return; // Ignora cliques em outros elementos

    // data-src é um atributo de dados customizado (data-*)
    // Guardamos o caminho da foto lá para recuperar aqui
    modalImg.src = thumb.dataset.src;
    modal.classList.add('open'); // Exibe o modal adicionando a classe CSS
    document.body.style.overflow = 'hidden'; // Impede rolagem do fundo
});

// Fecha o modal ao clicar no botão × ou fora da imagem
document.getElementById('modal-close').addEventListener('click', closeModal);
modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal(); // Clique no overlay escuro (fora da imagem)
});

// Fecha com a tecla Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
});

function closeModal() {
    modal.classList.remove('open');
    document.body.style.overflow = ''; // Restaura rolagem
    modalImg.src = ''; // Libera a memória da imagem
}

// ============================================================
// BOTÕES DE FILTRO
// ============================================================

// Busca ao clicar no botão "Search"
document.getElementById('btn-filter').addEventListener('click', loadObservations);

// Limpa os filtros e recarrega tudo
document.getElementById('btn-clear').addEventListener('click', function () {
    filterMonth.value  = '';
    filterObject.value = '';
    loadObservations();
});

// Busca também ao pressionar Enter nos campos de filtro
filterMonth.addEventListener('keydown',  e => { if (e.key === 'Enter') loadObservations(); });
filterObject.addEventListener('keydown', e => { if (e.key === 'Enter') loadObservations(); });

// ============================================================
// INICIALIZAÇÃO: carrega os dados quando a página abre
// DOMContentLoaded garante que o DOM está pronto antes de manipulá-lo
// ============================================================
document.addEventListener('DOMContentLoaded', loadObservations);
</script>

</body>
</html>
