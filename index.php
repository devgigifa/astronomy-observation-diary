<?php
// ============================================================
// index.php — Página inicial (Dashboard)
// Exibe estatísticas gerais e as últimas observações
// ============================================================

require_once 'db.php';

// --- Consulta 1: Total de observações registradas ---
// fetchColumn() retorna apenas o valor da primeira coluna da primeira linha
$total = $pdo->query("SELECT COUNT(*) FROM observations")->fetchColumn();

// --- Consulta 2: Quantidade de objetos únicos já observados ---
$unique_objects = $pdo->query("SELECT COUNT(DISTINCT object_name) FROM observations")->fetchColumn();

// --- Consulta 3: Mês com mais observações ---
// GROUP BY agrupa por mês; ORDER BY COUNT DESC + LIMIT 1 pega o maior
$stmt = $pdo->query("
    SELECT DATE_FORMAT(obs_date, '%M %Y') AS month_label, COUNT(*) AS qty
    FROM observations
    GROUP BY DATE_FORMAT(obs_date, '%Y-%m')
    ORDER BY qty DESC
    LIMIT 1
");
$best_month = $stmt->fetch();

// --- Consulta 4: Últimas 5 observações para exibir na lista ---
$recent = $pdo->query("
    SELECT id, obs_date, object_name, sky_conditions, location
    FROM observations
    ORDER BY obs_date DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Astronomy Diary — Dashboard</title>
    <!-- Linka o arquivo CSS externo -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ============================================================
     CABEÇALHO COM NAVEGAÇÃO
     ============================================================ -->
<header class="site-header">
    <div class="header-inner">
        <a href="index.php" class="site-logo">
            ✦ Astronomy <span>Diary</span>
        </a>
        <nav>
            <ul class="site-nav">
                <!-- A classe "active" marca a página atual na navegação -->
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="observations.php">Observations</a></li>
                <li><a href="new-observation.php">+ New</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- ============================================================
     CONTEÚDO PRINCIPAL
     ============================================================ -->
<main class="page-main">

    <h1 class="page-title">Dashboard</h1>

    <!-- GRADE DE ESTATÍSTICAS -->
    <div class="stats-grid">
        <!-- Cada card mostra um número importante -->
        <div class="stat-card">
            <div class="stat-label">Total Observations</div>
            <!-- intval() converte para inteiro; number_format() formata com milhar -->
            <div class="stat-value"><?= number_format((int)$total) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Unique Objects</div>
            <div class="stat-value"><?= (int)$unique_objects ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Most Active Month</div>
            <!-- O operador ternário (?:) é um if/else resumido -->
            <div class="stat-value" style="font-size:1.2rem; padding-top:0.4rem">
                <?= $best_month ? htmlspecialchars($best_month['month_label']) : '—' ?>
            </div>
        </div>
    </div>

    <!-- LISTA DE ÚLTIMAS OBSERVAÇÕES -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem">
            <h2 style="font-family:var(--font-display); font-size:1.1rem">Recent Observations</h2>
            <a href="observations.php" class="btn btn-secondary btn-sm">View all →</a>
        </div>

        <?php if (empty($recent)): ?>
            <!-- Bloco exibido quando não há dados ainda -->
            <p style="color:var(--fg-muted); text-align:center; padding:2rem 0">
                No observations yet. <a href="new-observation.php">Record your first one!</a>
            </p>
        <?php else: ?>
            <div class="recent-list">
                <?php foreach ($recent as $obs): ?>
                    <div class="recent-item">
                        <!-- htmlspecialchars() converte caracteres especiais em entidades HTML
                             Previne XSS (Cross-Site Scripting): ex. <script> vira &lt;script&gt; -->
                        <span class="item-date">
                            <?= htmlspecialchars(date('d M Y', strtotime($obs['obs_date']))) ?>
                        </span>
                        <span class="item-object">
                            <?= htmlspecialchars($obs['object_name']) ?>
                        </span>
                        <span class="sky-badge <?= htmlspecialchars($obs['sky_conditions']) ?>">
                            <?= htmlspecialchars($obs['sky_conditions']) ?>
                        </span>
                        <a href="observations.php" class="btn btn-secondary btn-sm">Details</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</main>

</body>
</html>
