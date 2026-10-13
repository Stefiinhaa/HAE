<?php
if (!defined('HAE_SEGURANCA')) {
    http_response_code(403);
    exit('Acesso restrito.');
}

$host = 'localhost';
$dbname = 'sistema_hae';
$user = 'root'; // mude se necessário
$pass = ''; // mude se necessário

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("[HAE] Falha na conexão com o banco de dados: " . $e->getMessage());
    http_response_code(500);
    die("Sistema temporariamente indisponível. Tente novamente em instantes.");
}
?>