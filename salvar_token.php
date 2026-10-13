<?php
require_once __DIR__ . '/config/seguranca.php';
require 'config/conexao.php';

header('Content-Type: application/json');

// Aceita apenas POST com JSON
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido.']);
    exit;
}

// Recebe os dados enviados pelo JavaScript (limitado a 4 KB)
$corpo = file_get_contents('php://input', false, null, 0, 4096);
$dados = json_decode($corpo, true);

if (isset($_SESSION['usuario_id']) && is_array($dados) && isset($dados['token']) && is_string($dados['token'])) {
    $usuario_id = (int) $_SESSION['usuario_id'];
    $token = trim($dados['token']);

    // O token FCM cabe em varchar(255) e só tem caracteres seguros
    if ($token === '' || strlen($token) > 255 || !preg_match('/^[A-Za-z0-9_\-:.]+$/', $token)) {
        echo json_encode(['sucesso' => false, 'erro' => 'Token inválido.']);
        exit;
    }

    // Evita que um usuário encha a tabela com tokens
    hae_limitar_requisicoes('salvar_token:' . $usuario_id, 30, 3600, true);

    try {
        // Tenta salvar o token. O "INSERT IGNORE" evita erro se o token já existir
        $stmt = $pdo->prepare("INSERT IGNORE INTO fcm_tokens (usuario_id, token) VALUES (?, ?)");
        $stmt->execute([$usuario_id, $token]);
        
        echo json_encode(['sucesso' => true]);
    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'erro' => hae_erro_seguro($e, 'salvar_token')]);
    }
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos ou usuário não logado.']);
}
?>