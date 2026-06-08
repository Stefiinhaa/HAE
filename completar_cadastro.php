<?php
session_start();
require 'config/conexao.php';

// Segurança: Só entra quem logou e tem o status de 'primeiro_acesso = 1'
if (!isset($_SESSION['usuario_id']) || $_SESSION['primeiro_acesso'] != 1) {
    header("Location: painel.php");
    exit;
}

$erro = "";

// BUSCA OS DADOS JÁ CADASTRADOS (Para pré-preencher o formulário)
$stmt_user = $pdo->prepare("SELECT data_nascimento FROM usuarios WHERE id = ?");
$stmt_user->execute([$_SESSION['usuario_id']]);
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

$data_nascimento_db = $user_data ? $user_data['data_nascimento'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data_nascimento = $_POST['data_nascimento'];
    $data_admissao = $_POST['data_admissao'];
    $tipo_contrato = $_POST['tipo_contrato'];
    $formacao_academica = $_POST['formacao_academica'];
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    // Validação de Segurança da Senha (No back-end)
    $uppercase = preg_match('@[A-Z]@', $nova_senha);
    $lowercase = preg_match('@[a-z]@', $nova_senha);
    $number    = preg_match('@[0-9]@', $nova_senha);
    $special   = preg_match('@[^\w]@', $nova_senha);

    if (!$uppercase || !$lowercase || !$number || !$special || strlen($nova_senha) < 8) {
        $erro = "A senha não atende aos requisitos mínimos de segurança.";
    } elseif ($nova_senha !== $confirma_senha) {
        $erro = "As senhas não coincidem.";
    } else {
        // Tratar o upload da assinatura
        $assinatura_path = "";
        if (isset($_FILES['assinatura']) && $_FILES['assinatura']['error'] == 0) {
            $extensao = pathinfo($_FILES['assinatura']['name'], PATHINFO_EXTENSION);
            $novo_nome = md5(uniqid()) . "." . $extensao;
            $diretorio = "uploads/assinaturas/";
            if (!is_dir($diretorio)) mkdir($diretorio, 0777, true);
            move_uploaded_file($_FILES['assinatura']['tmp_name'], $diretorio . $novo_nome);
            $assinatura_path = $diretorio . $novo_nome;
        } else {
            $erro = "Você precisa enviar a imagem da sua assinatura digitalizada.";
        }

        if (empty($erro)) {
            $senha_hash = md5($nova_senha);
            
            // Atualiza o perfil completo e tira a flag de primeiro acesso
            $sql = "UPDATE usuarios SET 
                    data_nascimento = ?, data_admissao = ?, tipo_contrato = ?, 
                    formacao_academica = ?, assinatura_path = ?, senha = ?, primeiro_acesso = 0 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data_nascimento, $data_admissao, $tipo_contrato, $formacao_academica, $assinatura_path, $senha_hash, $_SESSION['usuario_id']]);
            
            // Atualiza a sessão
            $_SESSION['primeiro_acesso'] = 0;
            
            header("Location: painel.php?status=perfil_concluido");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Cadastro - HAE</title>
    <style>
        :root { --fatec-red: #b20000; --strength-weak: #ff4d4d; --strength-medium: #ffd43b; --strength-strong: #2ecc71; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f7f6; padding: 40px 20px; }
        
        .card { background: #fff; width: 100%; max-width: 700px; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-top: 8px solid var(--fatec-red); margin: 0 auto; }
        h2 { color: #333; margin-bottom: 8px; text-align: center; font-size: 22px; }
        p.subtitle { color: #666; font-size: 14px; text-align: center; margin-bottom: 30px; }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        .full { grid-column: span 2; }
        
        .input-group { position: relative; }
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #444; }
        input, select { width: 100%; padding: 12px; border: 1.5px solid #ddd; border-radius: 6px; font-size: 14px; transition: 0.3s; }
        input:focus, select:focus { border-color: var(--fatec-red); outline: none; }
        input[type="file"] { padding: 9px; }

        input[type="password"], input[type="text"] { padding-right: 40px; }

        .form-section { padding-top: 20px; border-top: 1px solid #eee; margin-top: 20px; }

        /* Barrinha de Força da Senha */
        .strength-meter { height: 4px; width: 100%; background-color: #eee; margin-top: 8px; border-radius: 2px; overflow: hidden; display: flex; }
        .strength-bar { height: 100%; width: 0%; transition: all 0.4s ease; }
        .strength-text { font-size: 11px; margin-top: 4px; font-weight: bold; text-transform: uppercase; }

        .btn { width: 100%; padding: 15px; background: var(--fatec-red); color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; margin-top: 10px; box-shadow: 0 4px 6px rgba(178, 0, 0, 0.2);}
        .btn:hover { background: #8a0000; transform: translateY(-1px); }
        .btn:disabled { background: #ccc; cursor: not-allowed; box-shadow: none; }

        .error-msg { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; text-align: center; }

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

        /* Estilo para a idade */
        .idade-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-weight: bold;
            display: block;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</h2>
    <p class="subtitle">Este é o seu primeiro acesso. Por favor, complete seu perfil acadêmico e defina sua senha definitiva.</p>
    
    <?php if($erro) echo "<div class='error-msg'>$erro</div>"; ?>

    <form method="POST" enctype="multipart/form-data">
        
        <h3 style="font-size:16px; margin-bottom:15px; color:var(--fatec-red);">1. Dados Acadêmicos</h3>
        <div class="grid">
            <div class="input-group">
                <label>Data de Nascimento</label>
                <input type="date" name="data_nascimento" id="data_nascimento" required value="<?php echo htmlspecialchars($data_nascimento_db); ?>" oninput="calculateAge(this.value)">
                <span id="idade-display" class="idade-info"></span>
            </div>
            <div class="input-group"><label>Data de Admissão na Fatec</label><input type="date" name="data_admissao" required></div>
            
            <div class="input-group full">
                <label>Tipo de Contrato</label>
                <select name="tipo_contrato" required>
                    <option value="">Selecione...</option>
                    <option value="Determinado">Prazo Determinado</option>
                    <option value="Indeterminado">Prazo Indeterminado</option>
                </select>
            </div>
            
            <div class="input-group full"><label>Formação Acadêmica (Graduação / Titulação)</label><input type="text" name="formacao_academica" required placeholder="Ex: Doutorado em Ciência da Computação"></div>
            
            <div class="input-group full">
                <label>Assinatura Digitalizada (Imagem sem fundo transparente idealmente)</label>
                <input type="file" name="assinatura" accept="image/*" required>
            </div>
        </div>

        <div class="form-section">
            <h3 style="font-size:16px; margin-bottom:15px; color:var(--fatec-red);">2. Segurança</h3>
            <div class="grid">
                <div class="input-group full">
                    <label>Sua Nova Senha Definitiva</label>
                    <input type="password" name="nova_senha" id="nova_senha" required oninput="checkStrength(this.value)">
                    <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye.svg" class="toggle-password" onclick="toggleVisibility('nova_senha', this)" alt="Ver senha">
                    <div class="strength-meter"><div id="strength-bar" class="strength-bar"></div></div>
                    <div id="strength-text" class="strength-text"></div>
                </div>

                <div class="input-group full">
                    <label>Confirmar Nova Senha</label>
                    <input type="password" name="confirma_senha" id="confirma_senha" required oninput="validateMatch()">
                    <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye.svg" class="toggle-password" onclick="toggleVisibility('confirma_senha', this)" alt="Ver senha">
                    <div id="match-text" class="strength-text"></div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn" id="submitBtn" disabled>Salvar Perfil e Acessar Sistema</button>
    </form>
</div>

<script>
    // Função para calcular a idade em tempo real
    function calculateAge(dobString) {
        const display = document.getElementById('idade-display');
        if (!dobString) {
            display.innerText = "";
            return;
        }

        const dob = new Date(dobString);
        const today = new Date();
        
        // Ajuste de fuso horário para evitar bugs de data no JS
        dob.setMinutes(dob.getMinutes() + dob.getTimezoneOffset());
        
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        
        // Ajusta a idade se o aniversário ainda não aconteceu este ano
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
        
        if (age >= 0) {
            display.innerText = age + (age === 1 ? " ano" : " anos");
            display.style.color = "#666";
        } else {
            display.innerText = "Data inválida";
            display.style.color = "var(--fatec-red)";
        }
    }

    // Aciona o cálculo da idade imediatamente ao carregar a página se já houver uma data preenchida
    document.addEventListener("DOMContentLoaded", function() {
        const dataNascimentoInput = document.getElementById('data_nascimento').value;
        if(dataNascimentoInput) {
            calculateAge(dataNascimentoInput);
        }
    });

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
            case 1: bar.style.width = '25%'; bar.style.backgroundColor = 'var(--strength-weak)'; text.innerText = 'Fraca'; text.style.color = 'var(--strength-weak)'; break;
            case 2:
            case 3: bar.style.width = '60%'; bar.style.backgroundColor = 'var(--strength-medium)'; text.innerText = 'Média'; text.style.color = 'var(--strength-medium)'; break;
            case 4: bar.style.width = '100%'; bar.style.backgroundColor = 'var(--strength-strong)'; text.innerText = 'Forte'; text.style.color = 'var(--strength-strong)'; break;
        }
        validateMatch();
    }

    function validateMatch() {
        const p1 = document.getElementById('nova_senha').value;
        const p2 = document.getElementById('confirma_senha').value;
        const matchText = document.getElementById('match-text');
        const btn = document.getElementById('submitBtn');

        if (p2 === "") { matchText.innerText = ""; btn.disabled = true; } 
        else if (p1 === p2 && p1.length >= 8) { matchText.innerText = "As senhas coincidem"; matchText.style.color = 'var(--strength-strong)'; btn.disabled = false; } 
        else { matchText.innerText = "As senhas não coincidem"; matchText.style.color = 'var(--strength-weak)'; btn.disabled = true; }
    }
</script>

</body>
</html>