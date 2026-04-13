<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

$id_professor = $_SESSION['id_usuario'];
$nome_professor = $_SESSION['nome'];

// Busca se existe alguma oferta de HAE pendente para este professor
$stmt = $pdo->prepare("SELECT * FROM ofertas_hae WHERE id_professor = ? AND status = 'Pendente'");
$stmt->execute([$id_professor]);
$oferta = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel HAE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        header { background-color: #000; color: #fff; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid #b20000; }
        .logout { color: #fff; text-decoration: none; font-weight: bold; background: #b20000; padding: 8px 15px; border-radius: 4px; }
        .logout:hover { background: #8a0000; }
        .container { max-width: 900px; margin: 50px auto; padding: 20px; }
        .card-vazio { text-align: center; padding: 60px 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); color: #666; border: 1px dashed #ccc; }
        .card-oferta { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 5px solid #b20000; }
        .card-oferta h2 { color: #b20000; margin-bottom: 15px; }
        .btn-preencher { display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #b20000; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body style="background-color: #f9f9f9;">

<header>
    <h2>Portal HAE</h2>
    <div>
        <span>Olá, Prof. <?php echo htmlspecialchars($nome_professor); ?></span>
        <a href="logout.php" class="logout" style="margin-left: 20px;">Sair</a>
    </div>
</header>

<div class="container">
    <?php if ($oferta): ?>
        <div class="card-oferta">
            <h2>Nova Solicitação de Projeto HAE Disponível</h2>
            <p>A direção liberou um novo formulário de Horas Atividades Específicas para você preencher.</p>
            <p>Caso você concorde em assumir o projeto, clique no botão abaixo para iniciar o preenchimento detalhado.</p>
            
            <a href="formulario_solicitacao.php?id_oferta=<?php echo $oferta['id']; ?>" class="btn-preencher">
                Preencher Formulário de Solicitação
            </a>
        </div>
    <?php else: ?>
        <div class="card-vazio">
            <h2>Nenhuma atividade pendente</h2>
            <p>Você não possui nenhum projeto de HAE oferecido pela coordenação ou direção no momento.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>