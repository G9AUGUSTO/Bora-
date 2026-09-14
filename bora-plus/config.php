<?php
/**
 * config.php
 * Configuração de conexão com o banco de dados (MySQL/MariaDB) via PDO.
 */

// ==== DADOS DO BANCO ====
define('DB_HOST', 'localhost');
define('DB_NAME', 'boraplus');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // Senha do MySQL 8.0 local (com fallback automático para senha vazia do XAMPP)
define('DB_CHARSET', 'utf8mb4');
// ========================

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = null;
$senhasTentativas = [DB_PASS, '']; // Tenta 'root' e vazio ''
$databases = [DB_NAME, 'boramais'];
$ultimoErro = null;

foreach ($databases as $db) {
    foreach (array_unique($senhasTentativas) as $senha) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . $db . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, $senha, $options);
            break 2;
        } catch (PDOException $e) {
            $ultimoErro = $e;
        }
    }
}

if (!$pdo) {
    http_response_code(500);
    die('Erro ao conectar ao banco de dados. Verifique config.php. (' . ($ultimoErro ? $ultimoErro->getMessage() : 'Falha na conexão') . ')');
}
