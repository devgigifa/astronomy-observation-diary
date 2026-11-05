<?php
// ============================================================
// db.php — Conexão com o banco de dados usando PDO
// Este arquivo é incluído por todos os outros scripts PHP
// que precisam conversar com o banco de dados.
// ============================================================

// Configurações de conexão — ajuste conforme seu ambiente
define('DB_HOST', 'localhost');   // Endereço do servidor MySQL (geralmente localhost no XAMPP)
define('DB_NAME', 'astronomy_diary'); // Nome do banco criado no database.sql
define('DB_USER', 'root');        // Usuário do MySQL (padrão do XAMPP é 'root')
define('DB_PASS', '');            // Senha do MySQL (padrão do XAMPP é vazia)
define('DB_CHARSET', 'utf8mb4'); // Mesmo charset definido no banco

// DSN = Data Source Name: string que identifica o banco para o PDO
// Formato: "driver:host=HOST;dbname=BANCO;charset=CHARSET"
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// Opções de comportamento do PDO:
$options = [
    // Lança exceções (PDOException) em caso de erro — facilita o tratamento de erros
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

    // Retorna resultados como arrays associativos por padrão (ex: $row['obs_date'])
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    // Desativa a emulação de prepared statements — usa os do MySQL de verdade
    // Isso é mais seguro contra SQL Injection
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Tenta criar a conexão com o banco de dados
    // O objeto $pdo é o que usaremos em todo o projeto para executar queries
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // Se a conexão falhar, interrompe o script e exibe uma mensagem amigável
    // Em produção, você nunca mostraria $e->getMessage() para o usuário final!
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Erro de conexão com o banco de dados: ' . $e->getMessage()
    ]));
}
