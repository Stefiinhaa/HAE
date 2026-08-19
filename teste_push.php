<?php
session_start();
require 'enviar_push.php';

// Verifica se você está logado
if (!isset($_SESSION['usuario_id'])) {
    die("Você precisa fazer login no sistema HAE primeiro para testar o Push!");
}

$meu_id = $_SESSION['usuario_id'];

echo "<h3>Teste de Push Notification</h3>";
echo "Tentando disparar notificação para o usuário ID: <strong>" . $meu_id . "</strong><br><br>";

// Tenta enviar o push para você mesma
$resultado = dispararPush($meu_id, "Teste do Sistema 🚀", "Stefani, se você recebeu esta mensagem, a integração do OneSignal está 100% perfeita!", "https://sistemahae.page.gd/painel.php");

echo "<strong>Resposta do Servidor OneSignal:</strong><br>";
echo "<pre style='background: #333; color: #2ecc71; padding: 15px; border-radius: 5px;'>" . htmlspecialchars($resultado) . "</pre>";
?>