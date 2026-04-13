<?php
session_start();
require 'conexao.php';

$msg_sucesso = isset($_GET['cadastro']) && $_GET['cadastro'] == 'sucesso';
$link_whatsapp = isset($_GET['wa']) ? $_GET['wa'] : null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = md5($_POST['senha']);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND senha = ?");
    $stmt->execute([$email, $senha]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $_SESSION['id_usuario'] = $usuario['id'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['funcao'] = $usuario['funcao'];
        
        if ($usuario['trocar_senha'] == 1) {
            header("Location: troca_senha.php");
        } else {
            header("Location: painel.php");
        }
        exit;
    } else {
        $erro = "Credenciais incorretas.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - Portal HAE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-container { background: #fff; width: 400px; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 6px solid #b20000; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #333; font-size: 24px; }
        .input-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; }
        input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-login { width: 100%; padding: 12px; background: #b20000; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .btn-wa { display: block; text-align: center; background: #25d366; color: #fff; padding: 12px; border-radius: 4px; text-decoration: none; font-weight: bold; margin-top: 10px; }
        .btn-wa:hover { background: #128c7e; }
        .footer { text-align: center; margin-top: 20px; font-size: 13px; }
        .footer a { color: #b20000; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="header">
        <h1>Portal HAE</h1>
        <p>Fatec Garça</p>
    </div>

    <?php if($msg_sucesso): ?>
        <div class="alert-success">
            <strong>Cadastro concluído!</strong><br>
            A senha foi enviada por e-mail.
            <?php if($link_whatsapp): ?>
                <a href="<?php echo $link_whatsapp; ?>" target="_blank" class="btn-wa">Enviar Senha via WhatsApp</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label>E-mail Institucional</label>
            <input type="email" name="email" required>
        </div>
        <div class="input-group">
            <label>Senha</label>
            <input type="password" name="senha" required>
        </div>
        <button type="submit" class="btn-login">Entrar</button>
    </form>

    <div class="footer">
        <p>Novo por aqui? <a href="cadastro.php">Cadastre-se</a></p>
    </div>
</div>
</body>
</html>