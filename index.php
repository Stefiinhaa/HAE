<?php
session_start();
require 'config/conexao.php';

// Captura a mensagem de sucesso vinda do cadastro.php
$mensagem_sucesso = "";
if (isset($_GET['cadastro']) && $_GET['cadastro'] == 'sucesso') {
    $mensagem_sucesso = "Cadastro realizado com sucesso! Acesse com seu e-mail e senha provisória.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $senha = md5(trim($_POST['senha'])); // Senha em MD5 (como gerado no cadastro)

    $sql = "SELECT * FROM usuarios WHERE email = ? AND senha = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $senha]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_funcao'] = $usuario['funcao'];
        $_SESSION['primeiro_acesso'] = $usuario['primeiro_acesso'];

        // Regra: Obrigar troca de senha no primeiro login
        if ($usuario['primeiro_acesso'] == 1) {
            header("Location: troca_senha.php");
            exit;
        } else {
            header("Location: painel.php");
            exit;
        }
    } else {
        $erro = "E-mail ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portal HAE Fatec</title>
    <style>
        :root {
            --fatec-red: #b20000;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        
        .login-box { 
            background: #fff; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            width: 100%; 
            max-width: 400px; 
            border-top: 6px solid var(--fatec-red); 
        }
        
        h2 { text-align: center; color: #333; margin-bottom: 25px; font-size: 24px; }
        h2 span { color: var(--fatec-red); }

        .input-group { margin-bottom: 18px; position: relative; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #444; }
        
        input { 
            width: 100%; 
            padding: 12px 40px 12px 12px; /* Espaço na direita para o ícone não sobrepor o texto */
            border: 1.5px solid #ddd; 
            border-radius: 6px; 
            outline: none; 
            font-size: 15px;
            transition: border-color 0.3s;
        }
        input:focus { border-color: var(--fatec-red); }

        .btn-submit { 
            width: 100%; 
            padding: 14px; 
            background: var(--fatec-red); 
            color: #fff; 
            border: none; 
            border-radius: 6px; 
            font-weight: bold; 
            cursor: pointer; 
            margin-top: 10px; 
            font-size: 15px;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(178, 0, 0, 0.2);
        }
        .btn-submit:hover { background: #8a0000; transform: translateY(-1px); }

        /* Alertas de Erro e Sucesso */
        .error { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; text-align: center; }
        .alert-success { background: #d1e7dd; color: #0f5132; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; text-align: center; border-left: 4px solid #198754; }
        
        /* Estilo do olhinho SVG */
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 34px;
            cursor: pointer;
            width: 20px;
            opacity: 0.5;
            transition: 0.3s;
        }
        .toggle-password:hover { opacity: 1; }

        /* Link extra */
        .links { margin-top: 20px; text-align: center; font-size: 13px; color: #666; }
        .links a { color: var(--fatec-red); text-decoration: none; font-weight: 600; transition: 0.3s; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Portal <span>HAE</span></h2>
    
    <!-- Exibe mensagem de sucesso se vier do cadastro -->
    <?php if($mensagem_sucesso) echo "<div class='alert-success'>$mensagem_sucesso</div>"; ?>
    
    <!-- Exibe mensagem de erro de login -->
    <?php if(isset($erro)) echo "<div class='error'>$erro</div>"; ?>
    
    <form method="POST">
        <div class="input-group">
            <label>E-mail Institucional</label>
            <input type="email" name="email" required placeholder="exemplo@fatec.sp.gov.br">
        </div>
        
        <div class="input-group">
            <label>Senha</label>
            <input type="password" name="senha" id="senha" required placeholder="Sua senha">
            <!-- Ícone SVG de Olho Profissional -->
            <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye.svg" 
                 class="toggle-password" onclick="toggleVisibility('senha', this)" alt="Ver senha">
        </div>
        
        <button type="submit" class="btn-submit">Entrar no Sistema</button>
    </form>

    <div class="links">
        Primeiro acesso? <a href="cadastro.php">Faça seu cadastro</a>
    </div>
</div>

<script>
    // Função atualizada para usar os SVGs
    function toggleVisibility(inputId, element) {
        const input = document.getElementById(inputId);
        const eyeOpen = "https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye.svg";
        const eyeSlash = "https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye-slash.svg";
        
        if (input.type === "password") {
            input.type = "text";
            element.src = eyeSlash;
        } else {
            input.type = "password";
            element.src = eyeOpen;
        }
    }
</script>

</body>
</html>