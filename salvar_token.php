<?php
session_start();
require 'config/conexao.php';

// Recebe os dados enviados pelo JavaScript
$dados = json_decode(file_get_contents('php://input'), true);

if (isset($_SESSION['usuario_id']) && isset($dados['token'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $token = $dados['token'];

    try {
        // Tenta salvar o token. O "INSERT IGNORE" evita erro se o token já existir
        $stmt = $pdo->prepare("INSERT IGNORE INTO fcm_tokens (usuario_id, token) VALUES (?, ?)");
        $stmt->execute([$usuario_id, $token]);
        
        echo json_encode(['sucesso' => true]);
    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    }
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos ou usuário não logado.']);
}
?>