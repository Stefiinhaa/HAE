<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Diretor acessa as configurações globais
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] !== 'Diretor') {
    header("Location: painel.php");
    exit;
}

$sucesso = "";
$erro = "";

// ==============================================================================
// PROCESSA A ATUALIZAÇÃO DAS CONFIGURAÇÕES
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ano_eleitoral = isset($_POST['ano_eleitoral']) ? '1' : '0';
    
    try {
        // Atualiza o Ano Eleitoral
        $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'ano_eleitoral'");
        $stmt->execute([$ano_eleitoral]);
        
        // Lógica de Upload da Nova Logo
        if (isset($_FILES['nova_logo']) && $_FILES['nova_logo']['error'] == 0) {
            $extensao = strtolower(pathinfo($_FILES['nova_logo']['name'], PATHINFO_EXTENSION));
            $extensoes_permitidas = ['jpg', 'jpeg', 'png'];
            
            if (in_array($extensao, $extensoes_permitidas)) {
                $novo_nome = "logo_fatec_" . time() . "." . $extensao;
                $diretorio = "uploads/";
                
                if (!is_dir($diretorio)) mkdir($diretorio, 0777, true);
                
                if (move_uploaded_file($_FILES['nova_logo']['tmp_name'], $diretorio . $novo_nome)) {
                    $caminho_logo = $diretorio . $novo_nome;
                    $stmt_logo = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'logo_institucional'");
                    $stmt_logo->execute([$caminho_logo]);
                } else {
                    $erro = "Falha ao salvar a imagem da logo no servidor.";
                }
            } else {
                $erro = "Formato de imagem inválido. Use apenas JPG ou PNG.";
            }
        }
        
        if (empty($erro)) {
            $sucesso = "Configurações do sistema atualizadas com sucesso!";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao atualizar banco de dados: " . $e->getMessage();
    }
}

// Busca as configurações atuais para preencher a tela
$stmt_conf = $pdo->query("SELECT chave, valor FROM configuracoes");
$config_db = $stmt_conf->fetchAll(PDO::FETCH_KEY_PAIR);

$status_eleitoral = $config_db['ano_eleitoral'] === '1' ? 'checked' : '';
$logo_atual = $config_db['logo_institucional'] ?? 'img/header-cps-documento.jpeg';

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema - HAE Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border-top: 4px solid var(--fatec-red); margin-bottom: 30px; }
        .form-section { margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .form-section h3 { color: var(--fatec-red); margin-bottom: 15px; font-size: 16px; border-left: 3px solid var(--fatec-red); padding-left: 10px; }
        
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #444; }
        .texto-apoio { font-size: 12px; color: #666; margin-bottom: 15px; line-height: 1.5; }
        
        .logo-preview { background: #f8f9fa; border: 1px dashed #ccc; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 15px; }
        .logo-preview img { max-width: 100%; max-height: 120px; }
        
        /* Estilo do Toggle Switch (Botão de Ligar/Desligar) */
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #2ecc71; }
        input:checked + .slider:before { transform: translateX(24px); }

        .flex-toggle { display: flex; align-items: center; gap: 15px; background: #fdf2f2; padding: 15px; border-radius: 8px; border: 1px solid #faeccc; }

        .btn-save { background: var(--fatec-red); color: white; padding: 12px 25px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-save:hover { background: #8a0000; }
        .alert-success { background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 6px; border-left: 4px solid #198754; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 6px; border-left: 4px solid #b91c1c; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="painel.php" class="brand">
                <img src="img/cps_fatecgarca_logo.jfif" alt="Logo Fatec">
                <h2 class="brand-text">HAE</h2>
            </a>
            <button class="collapse-btn" id="collapse-btn" title="Minimizar Menu"><i class="fa-solid fa-bars-staggered"></i></button>
        </div>
        <nav class="menu">
            <div class="menu-title">Navegação</div>
            <ul>
                <li><a href="painel.php" class="<?php echo ($pagina_atual == 'painel.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie"></i> <span class="menu-text">Dashboard</span></a></li>
                
                <?php if ($_SESSION['usuario_funcao'] == 'Professor'): ?>
                    <li><a href="nova_solicitacao.php" class="<?php echo ($pagina_atual == 'nova_solicitacao.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-circle-plus"></i> <span class="menu-text">Nova Solicitação</span></a></li>
                    <li><a href="meus_projetos.php" class="<?php echo ($pagina_atual == 'meus_projetos.php') ? 'active' : ''; ?>"><i class="fa-solid fa-folder-open"></i> <span class="menu-text">Meus Projetos</span></a></li>
                    <li><a href="enviar_relatorio.php" class="<?php echo ($pagina_atual == 'enviar_relatorio.php') ? 'active' : ''; ?>"><i class="fa-solid fa-calendar-check"></i> <span class="menu-text">Enviar Relatório</span></a></li>
                    <li><a href="meus_rascunhos.php" class="<?php echo ($pagina_atual == 'meus_rascunhos.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-pen"></i> <span class="menu-text">Meus Rascunhos</span></a></li>
                <?php else: ?>
                    <li><a href="analisar_solicitacoes.php" class="<?php echo ($pagina_atual == 'analisar_solicitacoes.php') ? 'active' : ''; ?>"><i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">Analisar Solicitações</span></a></li>
                    <li><a href="acompanhar_relatorios.php" class="<?php echo ($pagina_atual == 'acompanhar_relatorios.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> <span class="menu-text">Acompanhar Relatórios</span></a></li>
                    <li><a href="relatorios_atrasados.php" class="<?php echo ($pagina_atual == 'relatorios_atrasados.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-invoice"></i> <span class="menu-text">Relatórios Atrasados</span></a></li>
                    <li><a href="cadastrar_professor.php" class="<?php echo ($pagina_atual == 'cadastrar_professor.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-plus"></i> <span class="menu-text">Cadastrar Usuário</span></a></li>
                    
                    <?php if ($_SESSION['usuario_funcao'] == 'Diretor'): ?>
                        <li><a href="listar_usuarios.php" class="<?php echo ($pagina_atual == 'listar_usuarios.php') ? 'active' : ''; ?>"><i class="fa-solid fa-users"></i> <span class="menu-text">Lista de Usuários</span></a></li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <li><a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>
                
                <!-- MENU DE CONFIGURAÇÕES: Oculto para Coordenador/Professor, posicionado antes de Sair -->
                <?php if ($_SESSION['usuario_funcao'] == 'Diretor'): ?>
                    <li><a href="configuracoes.php" class="<?php echo ($pagina_atual == 'configuracoes.php') ? 'active' : ''; ?>"><i class="fa-solid fa-cogs"></i> <span class="menu-text">Configurações</span></a></li>
                <?php endif; ?>
                
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span class="menu-text">Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Configurações do Sistema</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <?php if($sucesso): ?>
            <div class="alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $sucesso; ?></div>
        <?php endif; ?>
        <?php if($erro): ?>
            <div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $erro; ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="form-section">
                    <h3><i class="fa-solid fa-image"></i> Identidade Visual dos Documentos</h3>
                    <p class="texto-apoio">Faça o upload de uma nova imagem para alterar a logomarca do Governo/CPS exibida no cabeçalho de todos os PDFs gerados pelo sistema (Solicitações e Relatórios).</p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: end;">
                        <div>
                            <label>Logo Atual</label>
                            <div class="logo-preview">
                                <img src="<?php echo htmlspecialchars($logo_atual); ?>?v=<?php echo time(); ?>" alt="Logo Institucional">
                            </div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label>Enviar Nova Logo (PNG ou JPG)</label>
                            <input type="file" name="nova_logo" accept=".png, .jpg, .jpeg" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                        </div>
                    </div>
                </div>

                <div class="form-section" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                    <h3><i class="fa-solid fa-scale-balanced"></i> Legislação e Conformidade</h3>
                    <p class="texto-apoio">De acordo com a Lei das Eleições (Lei nº 9.504/1997), logomarcas de órgãos públicos devem ser ocultadas durante o período eleitoral. Ative esta opção para remover automaticamente as logos de todos os documentos.</p>
                    
                    <div class="flex-toggle">
                        <label class="switch">
                            <input type="checkbox" name="ano_eleitoral" <?php echo $status_eleitoral; ?>>
                            <span class="slider"></span>
                        </label>
                        <div>
                            <strong style="color: #333; font-size: 14px;">Modo Período Eleitoral</strong><br>
                            <span style="font-size: 12px; color: #666;">Se ativado, a logo acima será ocultada dos PDFs, mantendo apenas o nome da unidade por extenso.</span>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 30px; text-align: right;">
                    <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Salvar Configurações</button>
                </div>
            </form>
        </div>

    </main>
    <script src="assets/js/painel.js"></script>
</body>
</html>