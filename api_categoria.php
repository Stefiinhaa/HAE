<?php
session_start();
require 'config/conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'salvar';
    $categoria = trim($_POST['categoria'] ?? '');
    
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
            echo json_encode(['sucesso' => false, 'msg' => $e->getMessage()]);
            exit;
        }
    }
}
echo json_encode(['sucesso' => false]);
?>