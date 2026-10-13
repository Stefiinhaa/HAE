<?php
require_once __DIR__ . '/config/seguranca.php';
require 'config/conexao.php';

header('Content-Type: application/json');

// SEGURANÇA: somente usuários autenticados podem usar esta API
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'msg' => 'Não autenticado.']);
    exit;
}

// Freio contra abuso: no máximo 60 chamadas por minuto por usuário
hae_limitar_requisicoes('api_categoria:' . (int) $_SESSION['usuario_id'], 60, 60, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'salvar';
    $categoria = trim($_POST['categoria'] ?? '');

    // A coluna aceita no máximo 100 caracteres
    if (mb_strlen($categoria, 'UTF-8') > 100) {
        echo json_encode(['sucesso' => false, 'msg' => 'Nome de categoria muito longo.']);
        exit;
    }
    
    if (!empty($categoria)) {
        try {
            if ($acao == 'excluir') {
                // Trava de segurança: Impede excluir as 3 categorias originais da faculdade
                $categorias_bloqueadas = ['Acadêmico', 'Administrativo', 'Extensão à comunidade'];
                
                if (!in_array($categoria, $categorias_bloqueadas)) {
                    $del = $pdo->prepare("DELETE FROM categorias_projeto WHERE nome = ?");
                    $del->execute([$categoria]);
                }
                echo json_encode(['sucesso' => true]);
                exit;
                
            } else {
                // Ação de SALVAR silenciosamente
                $stmt = $pdo->prepare("SELECT id FROM categorias_projeto WHERE nome = ?");
                $stmt->execute([$categoria]);
                
                if (!$stmt->fetch()) {
                    $ins = $pdo->prepare("INSERT INTO categorias_projeto (nome) VALUES (?)");
                    $ins->execute([$categoria]);
                    echo json_encode(['sucesso' => true, 'nova' => true]);
                    exit;
                } else {
                    echo json_encode(['sucesso' => true, 'nova' => false]);
                    exit;
                }
            }
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'msg' => hae_erro_seguro($e, 'api_categoria')]);
            exit;
        }
    }
}
echo json_encode(['sucesso' => false]);
?>