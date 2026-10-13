<?php
require_once __DIR__ . '/config/seguranca.php';
require 'enviar_push.php';

// Verifica se você está logado
if (!isset($_SESSION['usuario_id'])) {
    die("Você precisa fazer login no sistema HAE primeiro para testar o Push!");
}

// SEGURANÇA: ferramenta de diagnóstico restrita ao administrador do sistema.
$nomes_admin = ['Administrador', 'Administrador MD5'];
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_nome'], $nomes_admin, true)) {
    http_response_code(403);
    die("Acesso restrito a desenvolvedores.");
}
hae_limitar_requisicoes('teste_push:' . hae_ip(), 10, 3600);

$meu_id = (int) $_SESSION['usuario_id'];

echo "<h3>Teste de Push Notification (Firebase)</h3>";
echo "Tentando disparar notificação para o usuário ID: <strong>" . $meu_id . "</strong><br><br>";

// Tenta enviar o push para você mesma
$resultado = dispararPush($meu_id, "Teste do Firebase 🚀", "Se você recebeu esta mensagem, a integração do Google FCM está 100% perfeita!", hae_url_base() . "/painel.php");

echo "<strong>Resposta do Servidor Firebase:</strong><br>";
echo "<pre style='background: #333; color: #2ecc71; padding: 15px; border-radius: 5px;'>" . htmlspecialchars($resultado) . "</pre>";
?>