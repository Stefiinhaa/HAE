<?php
session_start();
require 'conexao.php';

// Verifica se já existe uma sessão ativa para redirecionar ao painel
if (isset($_SESSION['id_usuario'])) {
    header("Location: painel.php");
    exit;
}

$erro = "";
$msg_sucesso = isset($_GET['cadastro']) && $_GET['cadastro'] == 'sucesso';

// Lógica de Autenticação
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = md5($_POST['senha']); // Nota: Em produção, utiliza password_hash e password_verify

    $stmt = $pdo->prepare("SELECT id, nome, funcao, trocar_senha FROM usuarios WHERE email = ? AND senha = ?");
    $stmt->execute([$email, $senha]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $_SESSION['id_usuario'] = $usuario['id'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['funcao'] = $usuario['funcao'];

        // Regra do projeto: Obrigar a trocar a senha no primeiro login
        if ($usuario['trocar_senha'] == 1) {
            header("Location: troca_senha.php");
        } else {
            header("Location: painel.php");
        }
        exit;
    } else {
        $erro = "E-mail ou senha incorretos. Tente novamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema HAE Fatec Garça</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        
        body { 
            background-color: #f4f4f4; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-image: linear-gradient(135deg, #fdfdfd 0%, #e0e0e0 100%);
        }

        .login-container { 
            background-color: #ffffff; 
            width: 100%; 
            max-width: 420px; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.15); 
            border-top: 6px solid #b20000; 
        }

        .login-header { text-align: center; margin-bottom: 35px; }
        
        .login-header h1 { 
            color: #000; 
            font-size: 26px; 
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .login-header p { color: #b20000; font-size: 14px; font-weight: 600; margin-top: 5px; }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
            line-height: 1.5;
        }

        .alert-success { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }

        .alert-error { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }

        .input-group { margin-bottom: 20px; }
        
        .input-group label { 
            display: block; 
            margin-bottom: 8px; 
            color: #333; 
            font-weight: 600; 
            font-size: 14px; 
        }

        .input-group input { 
            width: 100%; 
            padding: 14px; 
            border: 2px solid #eee; 
            border-radius: 6px; 
            font-size: 15px; 
            transition: all 0.3s ease; 
        }

        .input-group input:focus { 
            border-color: #b20000; 
            outline: none; 
            background-color: #fff;
            box-shadow: 0 0 8px rgba(178, 0, 0, 0.1);
        }

        .btn-login { 
            width: 100%; 
            padding: 15px; 
            background-color: #b20000; 
            color: #fff; 
            border: none; 
            border-radius: 6px; 
            font-size: 16px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: background 0.3s; 
            text-transform: uppercase;
        }

        .btn-login:hover { background-color: #8a0000; }

        .footer-links { 
            margin-top: 25px; 
            text-align: center; 
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .footer-links a { 
            color: #b20000; 
            text-decoration: none; 
            font-size: 14px; 
            font-weight: 600; 
        }

        .footer-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h1>Portal HAE</h1>
        <p>Fatec Garça</p>
    </div>
    
    <?php if($msg_sucesso): ?>
        <div class="alert alert-success">
            <strong>Registo concluído com sucesso!</strong><br>
            A tua senha provisória foi enviada para o teu e-mail e WhatsApp.
        </div>
    <?php endif; ?>

    <?php if($erro): ?>
        <div class="alert alert-error">
            <?php echo $erro; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="input-group">
            <label for="email">E-mail Institucional</label>
            <input type="email" id="email" name="email" required placeholder="exemplo@fatec.sp.gov.br">
        </div>
        
        <div class="input-group">
            <label for="senha">Palavra-passe</label>
            <input type="password" id="senha" name="senha" required placeholder="Introduza a sua senha">
        </div>
        
        <button type="submit" class="btn-login">Entrar no Sistema</button>
    </form>

    <div class="footer-links">
        <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Ainda não tem acesso?</p>
        <a href="cadastro.php">Solicitar Registo de Professor/Direção</a>
    </div>
</div>

</body>
</html>