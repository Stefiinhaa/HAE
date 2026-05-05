<?php
session_start();
require 'config/conexao.php'; // Caminho atualizado conforme a nova organização

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    // Validação de complexidade no PHP (Segurança de Back-end)
    $uppercase = preg_match('@[A-Z]@', $nova_senha);
    $lowercase = preg_match('@[a-z]@', $nova_senha);
    $number    = preg_match('@[0-9]@', $nova_senha);
    $special   = preg_match('@[^\w]@', $nova_senha);

    if (!$uppercase || !$lowercase || !$number || !$special || strlen($nova_senha) < 8) {
        $erro = "A senha não atende aos requisitos mínimos de segurança.";
    } elseif ($nova_senha !== $confirma_senha) {
        $erro = "As senhas não coincidem.";
    } else {
        $senha_hash = md5($nova_senha);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, primeiro_acesso = 0 WHERE id = ?");
        $stmt->execute([$senha_hash, $_SESSION['usuario_id']]);
        
        header("Location: painel.php?status=senha_alterada");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Troca de Senha - Sistema HAE</title>
    <style>
        :root {
            --fatec-red: #b20000;
            --strength-weak: #ff4d4d;
            --strength-medium: #ffd43b;
            --strength-strong: #2ecc71;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        
        .card {
            background: #fff;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border-top: 8px solid var(--fatec-red);
        }

        h2 { color: #333; margin-bottom: 8px; text-align: center; font-size: 22px; }
        p { color: #666; font-size: 14px; text-align: center; margin-bottom: 25px; }

        .input-group { margin-bottom: 18px; position: relative; }
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #444; }
        
        input { 
            width: 100%; 
            padding: 12px 40px 12px 12px; 
            border: 1.5px solid #ddd; 
            border-radius: 6px; 
            font-size: 15px;
            transition: border-color 0.3s;
        }
        input:focus { border-color: var(--fatec-red); outline: none; }

        /* Ícone de Olho Profissional (SVG) */
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

        /* Barrinha de Força da Senha */
        .strength-meter {
            height: 4px;
            width: 100%;
            background-color: #eee;
            margin-top: 8px;
            border-radius: 2px;
            overflow: hidden;
            display: flex;
        }
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.4s ease;
        }
        .strength-text { font-size: 11px; margin-top: 4px; font-weight: bold; text-transform: uppercase; }

        .btn { 
            width: 100%; 
            padding: 14px; 
            background: var(--fatec-red); 
            color: #fff; 
            border: none; 
            border-radius: 6px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 15px;
            margin-top: 10px;
            box-shadow: 0 4px 6px rgba(178, 0, 0, 0.2);
        }
        .btn:hover { background: #8a0000; transform: translateY(-1px); }
        .btn:disabled { background: #ccc; cursor: not-allowed; box-shadow: none; }

        .error-msg { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; text-align: center; }
    </style>
</head>
<body>

<div class="card">
    <h2>Nova Senha</h2>
    <p>Crie uma senha forte para proteger seu acesso.</p>
    
    <?php if($erro) echo "<div class='error-msg'>$erro</div>"; ?>

    <form method="POST" id="passwordForm">
        <div class="input-group">
            <label>Nova Senha</label>
            <input type="password" name="nova_senha" id="nova_senha" required oninput="checkStrength(this.value)">
            <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye.svg" 
                 class="toggle-password" onclick="toggleVisibility('nova_senha', this)" alt="Ver senha">
            
            <div class="strength-meter">
                <div id="strength-bar" class="strength-bar"></div>
            </div>
            <div id="strength-text" class="strength-text"></div>
        </div>

        <div class="input-group">
            <label>Confirmar Nova Senha</label>
            <input type="password" name="confirma_senha" id="confirma_senha" required oninput="validateMatch()">
            <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye.svg" 
                 class="toggle-password" onclick="toggleVisibility('confirma_senha', this)" alt="Ver senha">
            <div id="match-text" class="strength-text"></div>
        </div>

        <button type="submit" class="btn" id="submitBtn" disabled>Atualizar Senha</button>
    </form>
</div>

<script>
    // Função para ver/esconder senha com troca de ícone SVG
    function toggleVisibility(id, el) {
        const input = document.getElementById(id);
        const eyeOpen = "https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye.svg";
        const eyeSlash = "https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye-slash.svg";
        
        if (input.type === "password") {
            input.type = "text";
            el.src = eyeSlash;
        } else {
            input.type = "password";
            el.src = eyeOpen;
        }
    }

    // Lógica da Barrinha de Força
    function checkStrength(password) {
        const bar = document.getElementById('strength-bar');
        const text = document.getElementById('strength-text');
        let strength = 0;

        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        switch (strength) {
            case 0:
            case 1:
                bar.style.width = '25%';
                bar.style.backgroundColor = 'var(--strength-weak)';
                text.innerText = 'Fraca';
                text.style.color = 'var(--strength-weak)';
                break;
            case 2:
            case 3:
                bar.style.width = '60%';
                bar.style.backgroundColor = 'var(--strength-medium)';
                text.innerText = 'Média';
                text.style.color = 'var(--strength-medium)';
                break;
            case 4:
                bar.style.width = '100%';
                bar.style.backgroundColor = 'var(--strength-strong)';
                text.innerText = 'Forte';
                text.style.color = 'var(--strength-strong)';
                break;
        }
        validateMatch();
    }

    // Valida se as senhas são iguais e libera o botão
    function validateMatch() {
        const p1 = document.getElementById('nova_senha').value;
        const p2 = document.getElementById('confirma_senha').value;
        const matchText = document.getElementById('match-text');
        const btn = document.getElementById('submitBtn');

        if (p2 === "") {
            matchText.innerText = "";
            btn.disabled = true;
        } else if (p1 === p2 && p1.length >= 8) {
            matchText.innerText = "As senhas coincidem";
            matchText.style.color = 'var(--strength-strong)';
            btn.disabled = false;
        } else {
            matchText.innerText = "As senhas não coincidem";
            matchText.style.color = 'var(--strength-weak)';
            btn.disabled = true;
        }
    }
</script>

</body>
</html>