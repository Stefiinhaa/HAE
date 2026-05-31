<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$nome_usuario = $_SESSION['usuario_nome'];
$funcao_usuario = $_SESSION['usuario_funcao'];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Sistema HAE Fatec</title>
    <!-- Importando o CSS externo -->
    <link rel="stylesheet" href="assets/css/painel.css">
    <!-- Nova biblioteca de ícones profissionais -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

<?php
    // O PHP descobre magicamente em qual tela o usuário está agora
    $pagina_atual = basename($_SERVER['PHP_SELF']);
    ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="painel.php" class="brand">
                <img src="assets/img/logo.png" alt="Logo Fatec" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/5/52/Fatec_logo.svg'">
                <h2>HAE <span>FATEC</span></h2>
            </a>
            <button class="collapse-btn" id="collapse-btn"><i class="fa-solid fa-bars"></i></button>
        </div>
        
        <nav class="menu">
            <div class="menu-title">Navegação</div>
            <ul>
                <li><a href="painel.php" class="<?php echo ($pagina_atual == 'painel.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
                </a></li>
                
                <?php if ($_SESSION['usuario_funcao'] == 'Professor'): ?>
                    <li><a href="nova_solicitacao.php" class="<?php echo ($pagina_atual == 'nova_solicitacao.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-file-circle-plus"></i> <span>Nova Solicitação</span>
                    </a></li>
                    
                    <li><a href="meus_projetos.php" class="<?php echo ($pagina_atual == 'meus_projetos.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-folder-open"></i> <span>Meus Projetos</span>
                    </a></li>
                    
                    <li><a href="meus_projetos.php" class="<?php echo ($pagina_atual == 'enviar_relatorio.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-calendar-check"></i> <span>Enviar Relatório</span>
                    </a></li>
                    
                <?php else: ?>
                    <li><a href="analisar_solicitacoes.php" class="<?php echo ($pagina_atual == 'analisar_solicitacoes.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clipboard-check"></i> <span>Analisar Solicitações</span>
                    </a></li>
                    
                    <li><a href="acompanhar_relatorios.php" class="<?php echo ($pagina_atual == 'acompanhar_relatorios.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-chart-line"></i> <span>Acompanhar Relatórios</span>
                    </a></li>
                    
                    <li><a href="cadastrar_professor.php" class="<?php echo ($pagina_atual == 'cadastrar_professor.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-plus"></i> <span>Cadastrar Professor</span>
                    </a></li>
                <?php endif; ?>
                
                <li><a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-gear"></i> <span>Meu Perfil</span>
                </a></li>
                
                <li><a href="logout.php" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i> <span>Sair do Sistema</span>
                </a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <!-- Botão que aparece apenas no celular -->
                <button class="mobile-toggle" id="mobile-toggle">☰</button>
                <h1>Dashboard Overview</h1>
            </div>
            <div class="user-info">
                Olá, <strong>
                    <?php echo htmlspecialchars($nome_usuario); ?>
                </strong> (
                <?php echo $funcao_usuario; ?>)
            </div>
        </header>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'senha_alterada'): ?>
            <div class="alert-success">
                ✅ Sua senha foi atualizada com sucesso! Bem-vindo(a) ao portal.
            </div>
        <?php endif; ?>

        <div class="dashboard-cards">
            <?php if ($funcao_usuario == 'Professor'): ?>
                <div class="card">
                    <h3>Minhas Horas HAE</h3>
                    <p>Total de horas aprovadas neste semestre.</p>
                    <div class="number">0h</div>
                </div>
                <div class="card">
                    <h3>Projetos Ativos</h3>
                    <p>Projetos em andamento aguardando relatório.</p>
                    <div class="number">0</div>
                </div>
            <?php else: ?>
                <div class="card" style="border-top-color: #f39c12;">
                    <h3>Solicitações Pendentes</h3>
                    <p>Aguardando análise da direção.</p>
                    <div class="number">0</div>
                </div>
                <div class="card">
                    <h3>Relatórios Atrasados</h3>
                    <p>Pendências após o dia 10.</p>
                    <div class="number">0</div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Importando o JS externo -->
    <script src="assets/js/painel.js"></script>
</body>

</html>