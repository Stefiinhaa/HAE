<?php
session_start();
require 'config/conexao.php';

// Apenas Direção e Coordenação podem acessar
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_funcao'], ['Coordenador', 'Diretor'])) {
    header("Location: painel.php");
    exit;
}

$sucesso = "";
$erro = "";
$link_wa = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $whatsapp = trim($_POST['whatsapp']);
    $data_nascimento = $_POST['data_nascimento'];
    
    // AGORA A FUNÇÃO VEM DO FORMULÁRIO (Professor, Coordenador ou Diretor)
    $funcao = $_POST['funcao']; 

    // Gera a senha provisória baseada na data de nascimento (DDMMAAAA)
    $senha_provisoria = date('dmY', strtotime($data_nascimento));
    $senha_hash = md5($senha_provisoria);

    // Formata a saudação do WhatsApp baseada na função
    $saudacao = ($funcao == 'Professor') ? "Prof(a)." : (($funcao == 'Coordenador') ? "Coordenador(a)" : "Diretor(a)");

    try {
        // Verifica se o email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $erro = "Este e-mail já está cadastrado no sistema.";
        } else {
            // Insere os dados básicos + data de nascimento + Função Escolhida
            $sql = "INSERT INTO usuarios (nome, email, telefone_whatsapp, data_nascimento, funcao, senha, primeiro_acesso) 
                    VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $whatsapp, $data_nascimento, $funcao, $senha_hash]);

            // Gera o link do WhatsApp para notificar o novo usuário
            $num_limpo = preg_replace('/\D/', '', $whatsapp);
            if (substr($num_limpo, 0, 2) !== '55') $num_limpo = '55' . $num_limpo;
            $msg = "Olá, $saudacao $nome! Seu acesso ao Portal HAE Fatec foi criado.\n\n*E-mail:* $email\n*Senha provisória:* $senha_provisoria (Sua data de nascimento)\n\nPor favor, acesse o sistema para completar seu perfil, cadastrar sua imagem de assinatura digital e criar uma nova senha definitiva.";
            $link_wa = "https://wa.me/{$num_limpo}?text=" . urlencode($msg);

            $sucesso = "Usuário ($funcao) cadastrado com sucesso!";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao cadastrar: " . $e->getMessage();
    }
}

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Usuário - Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border-top: 4px solid var(--fatec-red); max-width: 600px; margin: 0 auto; }
        .input-group { margin-bottom: 20px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #444; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; transition: 0.3s; }
        input:focus, select:focus { border-color: var(--fatec-red); }
        .btn-submit { width: 100%; background: var(--fatec-red); color: white; padding: 15px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; transition: 0.3s;}
        .btn-submit:hover { background: #8a0000; }
        .btn-whatsapp { display: block; text-align: center; background: #25D366; color: white; padding: 15px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 15px; transition: 0.3s; }
        .btn-whatsapp:hover { background: #128C7E; }
        
        /* Estilo da caixinha de idade calculada */
        #idade_display { font-size: 12px; color: #27ae60; font-weight: bold; margin-top: 5px; display: none; }
    </style>
</head>
<body>

<aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="painel.php" class="brand">
                <img src="img/cps_fatecgarca_logo.jfif" alt="Logo Fatec">
                <h2 class="brand-text">HAE</h2>
            </a>
            <button class="collapse-btn" id="collapse-btn" title="Minimizar Menu">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
        </div>
        
        <nav class="menu">
            <div class="menu-title">Navegação</div>
            <ul>
                <li>
                    <a href="painel.php" class="<?php echo ($pagina_atual == 'painel.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-chart-pie"></i> <span class="menu-text">Dashboard</span>
                    </a>
                </li>
                
                <?php if ($_SESSION['usuario_funcao'] == 'Professor'): ?>
                    <li>
                        <a href="nova_solicitacao.php" class="<?php echo ($pagina_atual == 'nova_solicitacao.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-file-circle-plus"></i> <span class="menu-text">Nova Solicitação</span>
                        </a>
                    </li>
                    <li>
                        <a href="meus_projetos.php" class="<?php echo ($pagina_atual == 'meus_projetos.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-folder-open"></i> <span class="menu-text">Meus Projetos</span>
                        </a>
                    </li>
                    <li>
                        <a href="enviar_relatorio.php" class="<?php echo ($pagina_atual == 'enviar_relatorio.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-calendar-check"></i> <span class="menu-text">Enviar Relatório</span>
                        </a>
                    </li>
                    <li>
                        <a href="meus_rascunhos.php" class="<?php echo ($pagina_atual == 'meus_rascunhos.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-file-pen"></i> <span class="menu-text">Meus Rascunhos</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="analisar_solicitacoes.php" class="<?php echo ($pagina_atual == 'analisar_solicitacoes.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">Analisar Solicitações</span>
                        </a>
                    </li>
                    <li>
                        <a href="acompanhar_relatorios.php" class="<?php echo ($pagina_atual == 'acompanhar_relatorios.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-chart-line"></i> <span class="menu-text">Acompanhar Relatórios</span>
                        </a>
                    </li>
                    <li>
                        <a href="relatorios_atrasados.php" class="<?php echo ($pagina_atual == 'relatorios_atrasados.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-file-invoice"></i> <span class="menu-text">Relatórios Atrasados</span>
                        </a>
                    </li>
                    <li>
                        <a href="cadastrar_professor.php" class="<?php echo ($pagina_atual == 'cadastrar_professor.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-user-plus"></i> <span class="menu-text">Cadastrar Usuário</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <li>
                    <a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php" class="logout-link">
                        <i class="fa-solid fa-right-from-bracket"></i> <span class="menu-text">Sair do Sistema</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Cadastrar Novo Usuário</h1>
            </div>
        </header>

        <?php if($erro) echo "<div class='alert-success' style='background:#fee2e2; color:#b91c1c; border-color:#b91c1c;'>❌ $erro</div>"; ?>

        <div class="form-card">
            <?php if($sucesso): ?>
                <div class="alert-success" style="margin-bottom: 20px;">✅ <?php echo $sucesso; ?></div>
                <p style="color: #666; font-size: 14px; text-align: center; margin-bottom: 20px;">
                    O sistema gerou a senha provisória com a data de nascimento. Clique abaixo para enviar os dados de acesso para o WhatsApp do usuário:
                </p>
                <a href="<?php echo $link_wa; ?>" target="_blank" class="btn-whatsapp">
                    <i class="fa-brands fa-whatsapp"></i> Enviar Acesso via WhatsApp
                </a>
                <a href="cadastrar_professor.php" style="display:block; text-align:center; margin-top:20px; color:var(--fatec-red); font-weight:bold; text-decoration:none;">← Cadastrar outro usuário</a>
            <?php else: ?>
                <form method="POST">
                    <div class="input-group">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" required>
                    </div>
                    
                    <div class="grid-2">
                        <div class="input-group">
                            <label>E-mail Institucional</label>
                            <input type="email" name="email" required placeholder="exemplo@fatec.sp.gov.br">
                        </div>
                        
                        <div class="input-group">
                            <label>Função no Sistema</label>
                            <select name="funcao" required>
                                <option value="Professor">Professor(a)</option>
                                <option value="Coordenador">Coordenador(a)</option>
                                <option value="Diretor">Diretor(a)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid-2">
                        <div class="input-group">
                            <label>Data de Nascimento</label>
                            <input type="date" name="data_nascimento" id="data_nascimento" required>
                            <div id="idade_display"></div>
                        </div>
                        <div class="input-group">
                            <label>Número do WhatsApp</label>
                            <input type="text" name="whatsapp" id="whatsapp" required placeholder="(00) 00000-0000">
                        </div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px; color: #666; margin-bottom: 20px; border-left: 3px solid #ccc;">
                        <i class="fa-solid fa-circle-info"></i> A senha provisória será gerada automaticamente utilizando os números da data de nascimento (DDMMAAAA).
                    </div>

                    <button type="submit" class="btn-submit"><i class="fa-solid fa-user-check"></i> Registrar Usuário</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/js/painel.js"></script>
    <script>
        // Máscara de WhatsApp
        document.getElementById('whatsapp').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            if (value.length > 2) value = `(${value.slice(0,2)}) ${value.slice(2)}`;
            if (value.length > 10) value = `${value.slice(0,10)}-${value.slice(10)}`;
            e.target.value = value;
        });

        // Cálculo da idade em tempo real
        document.getElementById('data_nascimento').addEventListener('input', function() {
            let valData = this.value;
            let displayIdade = document.getElementById('idade_display');
            
            if(valData) {
                let hoje = new Date();
                let nascimento = new Date(valData);
                
                // Compensa o fuso horário para evitar erros no dia
                nascimento.setMinutes(nascimento.getMinutes() + nascimento.getTimezoneOffset());
                
                let idade = hoje.getFullYear() - nascimento.getFullYear();
                let mes = hoje.getMonth() - nascimento.getMonth();
                
                // Se o mês ainda não chegou, ou se estamos no mês mas o dia ainda não chegou, diminui 1 ano
                if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
                    idade--;
                }

                if (idade >= 0) {
                    displayIdade.innerHTML = `<i class="fa-solid fa-user-clock"></i> Idade: ${idade} anos`;
                    displayIdade.style.display = 'block';
                } else {
                    displayIdade.style.display = 'none'; // Evita mostrar idade negativa
                }
            } else {
                displayIdade.style.display = 'none';
            }
        });
    </script>
</body>
</html>