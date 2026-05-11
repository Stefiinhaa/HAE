<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas logados
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$sucesso = "";
$erro = "";

// 1. BUSCA DADOS ATUAIS PARA PREENCHER O FORMULÁRIO
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. PROCESSA A ATUALIZAÇÃO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $whatsapp = trim($_POST['whatsapp']);
    $data_nascimento = $_POST['data_nascimento'];
    $data_admissao = $_POST['data_admissao'];
    $tipo_contrato = $_POST['tipo_contrato'];
    $formacao_academica = $_POST['formacao_academica'];
    
    // Lógica para Nova Senha (Opcional)
    $nova_senha = $_POST['nova_senha'];
    $sql_senha = "";
    $params_senha = [];

    if (!empty($nova_senha)) {
        if ($nova_senha !== $_POST['confirma_senha']) {
            $erro = "As senhas não coincidem.";
        } else {
            $sql_senha = ", senha = ?";
            $params_senha[] = md5($nova_senha);
        }
    }

    // Lógica para Nova Assinatura (Opcional)
    $assinatura_path = $user['assinatura_path'];
    if (isset($_FILES['assinatura']) && $_FILES['assinatura']['error'] == 0) {
        $extensao = pathinfo($_FILES['assinatura']['name'], PATHINFO_EXTENSION);
        $novo_nome = md5(uniqid()) . "." . $extensao;
        $diretorio = "uploads/assinaturas/";
        if (!is_dir($diretorio)) mkdir($diretorio, 0777, true);
        move_uploaded_file($_FILES['assinatura']['tmp_name'], $diretorio . $novo_nome);
        $assinatura_path = $diretorio . $novo_nome;
    }

    if (empty($erro)) {
        try {
            $sql = "UPDATE usuarios SET 
                    nome = ?, telefone_whatsapp = ?, data_nascimento = ?, 
                    data_admissao = ?, tipo_contrato = ?, formacao_academica = ?, 
                    assinatura_path = ? $sql_senha 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $base_params = [$nome, $whatsapp, $data_nascimento, $data_admissao, $tipo_contrato, $formacao_academica, $assinatura_path];
            $final_params = array_merge($base_params, $params_senha, [$usuario_id]);
            
            $stmt->execute($final_params);
            
            // Atualiza o nome na sessão caso tenha mudado
            $_SESSION['usuario_nome'] = $nome;
            $sucesso = "Dados atualizados com sucesso!";
            
            // Recarrega os dados do usuário para o form refletir as mudanças
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - HAE Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --strength-weak: #ff4d4d; 
            --strength-medium: #ffd43b; 
            --strength-strong: #2ecc71;
        }

        .perfil-container { display: grid; grid-template-columns: 1fr 350px; gap: 30px; align-items: start; }
        .form-card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border-top: 4px solid var(--fatec-red); }
        
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .full { grid-column: span 2; }
        
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #444; }
        input, select { width: 100%; padding: 12px; border: 1.5px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; transition: 0.3s; }
        input:focus { border-color: var(--fatec-red); }
        input[readonly] { background: #f8f9fa; cursor: not-allowed; }
        input[type="password"] { padding-right: 40px; }

        .btn-save { background: var(--fatec-red); color: white; padding: 15px 30px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; width: 100%; font-size: 15px; }
        .btn-save:hover { background: var(--fatec-red-hover); transform: translateY(-1px); }
        .btn-save:disabled { background: #ccc; cursor: not-allowed; box-shadow: none; transform: none; }

        .signature-preview { text-align: center; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid #eee; }
        .signature-preview img { max-width: 100%; height: auto; margin-top: 15px; border: 1px dashed #ccc; padding: 10px; }

        .input-with-icon { position: relative; }
        .toggle-password { position: absolute; right: 12px; top: 38px; cursor: pointer; color: #888; transition: 0.3s; }
        .toggle-password:hover { color: #333; }
        
        .idade-info { font-size: 12px; color: #666; margin-top: 5px; font-weight: bold; display: block; }

        /* Barrinha de Força da Senha */
        .strength-meter { height: 4px; width: 100%; background-color: #eee; margin-top: 8px; border-radius: 2px; overflow: hidden; display: flex; }
        .strength-bar { height: 100%; width: 0%; transition: all 0.4s ease; }
        .strength-text { font-size: 11px; margin-top: 4px; font-weight: bold; text-transform: uppercase; }

        @media (max-width: 1024px) { .perfil-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="painel.php" class="brand">
                <img src="Img/cps_fatecgarca_logo.jfif" alt="Logo Fatec" >
                <h2>HAE </h2>
            </a>
            <button class="collapse-btn" id="collapse-btn"><i class="fa-solid fa-bars"></i></button>
        </div>
        <nav class="menu">
            <div class="menu-title">Navegação</div>
            <ul>
                <li><a href="painel.php"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></a></li>
                <?php if ($_SESSION['usuario_funcao'] == 'Professor'): ?>
                    <li><a href="nova_solicitacao.php"><i class="fa-solid fa-file-circle-plus"></i> <span>Nova Solicitação</span></a></li>
                    <li><a href="meus_projetos.php"><i class="fa-solid fa-folder-open"></i> <span>Meus Projetos</span></a></li>
                    <li><a href="#"><i class="fa-solid fa-calendar-check"></i> <span>Enviar Relatório</span></a></li>
                <?php else: ?>
                    <li><a href="analisar_solicitacoes.php"><i class="fa-solid fa-clipboard-check"></i> <span>Analisar Solicitações</span></a></li>
                    <li><a href="acompanhar_relatorios.php"><i class="fa-solid fa-chart-line"></i> <span>Acompanhar Relatórios</span></a></li>
                    <li><a href="cadastrar_professor.php"><i class="fa-solid fa-user-plus"></i> <span>Cadastrar Professor</span></a></li>
                <?php endif; ?>
                <li><a href="perfil.php" class="active"><i class="fa-solid fa-user-gear"></i> <span>Meu Perfil</span></a></li>
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span>Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Configurações de Perfil</h1>
            </div>
        </header>

        <?php if($sucesso) echo "<div class='alert-success'>✅ $sucesso</div>"; ?>
        <?php if($erro) echo "<div class='alert-success' style='background:#fee2e2; color:#b91c1c; border-color:#b91c1c;'>❌ $erro</div>"; ?>

        <div class="perfil-container">
            <div class="form-card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid">
                        <div class="full">
                            <label>Nome Completo</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                        </div>
                        <div>
                            <label>E-mail Institucional (Não alterável)</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        <div>
                            <label>WhatsApp</label>
                            <input type="text" name="whatsapp" id="whatsapp" value="<?php echo htmlspecialchars($user['telefone_whatsapp']); ?>" required>
                        </div>
                        <div>
                            <label>Data de Nascimento</label>
                            <input type="date" name="data_nascimento" id="data_nascimento" value="<?php echo $user['data_nascimento']; ?>" required oninput="calculateAge(this.value)">
                            <span id="idade-display" class="idade-info"></span>
                        </div>
                        <div>
                            <label>Data de Admissão</label>
                            <input type="date" name="data_admissao" value="<?php echo $user['data_admissao']; ?>" required>
                        </div>
                        <div class="full">
                            <label>Tipo de Contrato</label>
                            <select name="tipo_contrato" required>
                                <option value="Determinado" <?php echo ($user['tipo_contrato'] == 'Determinado') ? 'selected' : ''; ?>>Determinado</option>
                                <option value="Indeterminado" <?php echo ($user['tipo_contrato'] == 'Indeterminado') ? 'selected' : ''; ?>>Indeterminado</option>
                            </select>
                        </div>
                        <div class="full">
                            <label>Formação Acadêmica</label>
                            <input type="text" name="formacao_academica" value="<?php echo htmlspecialchars($user['formacao_academica']); ?>" required>
                        </div>
                    </div>

                    <h3 style="font-size:16px; margin: 30px 0 15px 0; color: var(--fatec-red); border-top: 1px solid #eee; padding-top: 20px;">Alterar Senha (Deixe em branco para manter a atual)</h3>
                    <div class="grid">
                        <div class="input-with-icon">
                            <label>Nova Senha</label>
                            <input type="password" name="nova_senha" id="nova_senha" oninput="checkStrength(this.value)">
                            <i class="fa-regular fa-eye toggle-password" onclick="toggleVisibility('nova_senha', this)"></i>
                            <div class="strength-meter"><div id="strength-bar" class="strength-bar"></div></div>
                            <div id="strength-text" class="strength-text"></div>
                        </div>
                        <div class="input-with-icon">
                            <label>Confirmar Nova Senha</label>
                            <input type="password" name="confirma_senha" id="confirma_senha" oninput="validateMatch()">
                            <i class="fa-regular fa-eye toggle-password" onclick="toggleVisibility('confirma_senha', this)"></i>
                            <div id="match-text" class="strength-text"></div>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn-save" id="submitBtn"><i class="fa-solid fa-floppy-disk"></i> Salvar Alterações</button>
                    </div>
                </form>
            </div>

            <div class="signature-column">
                <div class="signature-preview">
                    <label style="text-align: left;">Assinatura Atual</label>
                    <?php if($user['assinatura_path']): ?>
                        <img src="<?php echo $user['assinatura_path']; ?>" alt="Assinatura">
                    <?php else: ?>
                        <p style="font-size: 12px; color: #999; margin: 20px 0;">Nenhuma assinatura cadastrada.</p>
                    <?php endif; ?>
                    
                    <div style="margin-top: 25px; text-align: left;">
                        <label>Atualizar Assinatura</label>
                        <input type="file" name="assinatura" form="perfil-form" style="padding: 5px; font-size: 12px;">
                        <p style="font-size: 11px; color: #888; margin-top: 5px;">Formatos aceitos: PNG, JPG.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/painel.js"></script>
    <script>
        function calculateAge(dobString) {
            const display = document.getElementById('idade-display');
            if (!dobString) { display.innerText = ""; return; }
            const dob = new Date(dobString);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) { age--; }
            display.innerText = age + (age === 1 ? " ano" : " anos");
        }
        calculateAge(document.getElementById('data_nascimento').value);

        function toggleVisibility(id, el) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                el.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = "password";
                el.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function checkStrength(password) {
            const bar = document.getElementById('strength-bar');
            const text = document.getElementById('strength-text');
            
            if (!password) {
                bar.style.width = '0%';
                text.innerText = '';
                validateMatch();
                return;
            }

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

            // Se o usuário não digitou nada na senha, permite salvar o resto do perfil
            if (p1 === "" && p2 === "") { 
                matchText.innerText = ""; 
                btn.disabled = false; 
                return;
            } 
            
            if (p2 === "") { 
                matchText.innerText = ""; 
                btn.disabled = true; 
            } else if (p1 === p2 && p1.length >= 8) { 
                matchText.innerText = "As senhas coincidem"; 
                matchText.style.color = 'var(--strength-strong)'; 
                btn.disabled = false; 
            } else { 
                matchText.innerText = "As senhas não coincidem ou são curtas"; 
                matchText.style.color = 'var(--strength-weak)'; 
                btn.disabled = true; 
            }
        }

        // Máscara WhatsApp
        document.getElementById('whatsapp').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            if (value.length > 2) value = `(${value.slice(0,2)}) ${value.slice(2)}`;
            if (value.length > 10) value = `${value.slice(0,10)}-${value.slice(10)}`;
            e.target.value = value;
        });
    </script>
</body>
</html>