<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

$erro = "";
$sucesso = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    // Validação de complexidade (Pelo menos 8 chars, 1 maiúscula, 1 minúscula, 1 número, 1 símbolo)
    if (strlen($nova_senha) < 8 || !preg_match("#[0-9]+#", $nova_senha) || !preg_match("#[A-Z]+#", $nova_senha) || !preg_match("#[a-z]+#", $nova_senha) || !preg_match("#[\W]+#", $nova_senha)) {
        $erro = "A senha não atende aos requisitos de complexidade.";
    } elseif ($nova_senha !== $confirma_senha) {
        $erro = "As senhas não coincidem.";
    } else {
        $senha_hash = md5($nova_senha); // Use password_hash em produção
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, trocar_senha = 0 WHERE id = ?");
        $stmt->execute([$senha_hash, $_SESSION['id_usuario']]);
        
        header("Location: painel.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Troca de Senha - HAE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: #fff; width: 100%; max-width: 450px; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #b20000; }
        h2 { color: #000; margin-bottom: 10px; text-align: center; }
        p { color: #666; font-size: 14px; text-align: center; margin-bottom: 20px; }
        .input-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; }
        input:focus { border-color: #b20000; outline: none; }
        .btn { width: 100%; padding: 12px; background: #b20000; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .requisitos { font-size: 12px; color: #888; background: #f9f9f9; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .erro { background: #f8d7da; color: #721c24; padding: 10px; text-align: center; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Redefinição de Senha Obrigatória</h2>
    <p>Por motivos de segurança, altere sua senha provisória.</p>
    
    <?php if($erro) echo "<div class='erro'>$erro</div>"; ?>

    <div class="requisitos">
        Sua senha deve conter:<br>
        - Mínimo de 8 caracteres<br>
        - Letras maiúsculas e minúsculas<br>
        - Números e caracteres especiais (!@#$%&)
    </div>

    <form method="POST">
        <div class="input-group">
            <label>Nova Senha</label>
            <input type="password" name="nova_senha" required>
        </div>
        <div class="input-group">
            <label>Confirmar Nova Senha</label>
            <input type="password" name="confirma_senha" required>
        </div>
        <button type="submit" class="btn">Atualizar e Entrar</button>
    </form>
</div>
</body>
</html>