<?php
session_start();
require 'config/conexao.php';

// Redireciona para o login se não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Se for o primeiro acesso, obriga a completar o cadastro
if (isset($_SESSION['primeiro_acesso']) && $_SESSION['primeiro_acesso'] == 1) {
    header("Location: completar_cadastro.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$funcao = $_SESSION['usuario_funcao'];
$nome_usuario = $_SESSION['usuario_nome'];

// Variáveis de data para a inteligência do sistema
$mes_atual = date('n');
$ano_atual = date('Y');
$dia_atual = date('j');

$mes_anterior = $mes_atual - 1;
$ano_anterior = $ano_atual;
if ($mes_anterior == 0) {
    $mes_anterior = 12;
    $ano_anterior--;
}

$meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];

// ==========================================
// LÓGICA DO DASHBOARD PARA O PROFESSOR
// ==========================================
if ($funcao == 'Professor') {
    // Busca a quantidade de projetos por status
    $stmt = $pdo->prepare("SELECT status_aprovacao, COUNT(*) as qtd FROM solicitacoes_hae WHERE professor_id = ? GROUP BY status_aprovacao");
    $stmt->execute([$usuario_id]);
    $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $aprovados = $stats['Aprovado'] ?? 0;
    $pendentes = $stats['Pendente'] ?? 0;
    $rejeitados = $stats['Rejeitado'] ?? 0;
    $total_projetos = $aprovados + $pendentes + $rejeitados;

    // Inteligência de Alertas (Regra do dia 1 ao 10)
    $alertas = [];
    if ($aprovados > 0 && $dia_atual <= 10) {
        $sql_falta = "SELECT titulo_projeto FROM solicitacoes_hae s
                      WHERE professor_id = ? AND status_aprovacao = 'Aprovado'
                      AND NOT EXISTS (
                          SELECT 1 FROM relatorios_hae r
                          WHERE r.solicitacao_id = s.id AND r.mes_referencia = ? AND r.ano_referencia = ? AND r.status = 'Publicado'
                      )";
        $stmt_falta = $pdo->prepare($sql_falta);
        $stmt_falta->execute([$usuario_id, $mes_anterior, $ano_anterior]);
        $pendentes_relatorio = $stmt_falta->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($pendentes_relatorio as $p) {
            $alertas[] = "Você tem até o dia 10 para enviar o relatório de <strong>{$meses[$mes_anterior]}/{$ano_anterior}</strong> referente ao projeto: <em>{$p['titulo_projeto']}</em>";
        }
    }
} 
// ==========================================
// LÓGICA DO DASHBOARD PARA A DIREÇÃO
// ==========================================
else {
    // Projetos aguardando análise geral
    $stmt = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_aprovacao = 'Pendente'");
    $solicitacoes_pendentes = $stmt->fetchColumn();

    // Relatórios publicados neste mês atual
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM relatorios_hae WHERE status = 'Publicado' AND mes_referencia = ? AND ano_referencia = ?");
    $stmt->execute([$mes_atual, $ano_atual]);
    $relatorios_mes = $stmt->fetchColumn();

    // Total de professores cadastrados
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE funcao = 'Professor'");
    $total_professores = $stmt->fetchColumn();
    
    // Busca os 5 projetos mais recentes aguardando análise para mostrar numa tabelinha rápida
    $sql_recentes = "SELECT s.id, s.titulo_projeto, s.data_criacao, u.nome AS professor_nome 
                     FROM solicitacoes_hae s JOIN usuarios u ON s.professor_id = u.id 
                     WHERE s.status_aprovacao = 'Pendente' ORDER BY s.data_criacao ASC LIMIT 5";
    $stmt_recentes = $pdo->query($sql_recentes);
    $projetos_recentes = $stmt_recentes->fetchAll(PDO::FETCH_ASSOC);
}

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - HAE Fatec</title>
    
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); display: flex; align-items: center; border-top: 4px solid #ddd; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.05); }
        .stat-card.red { border-top-color: var(--fatec-red); }
        .stat-card.green { border-top-color: #2ecc71; }
        .stat-card.orange { border-top-color: #f39c12; }
        .stat-card.blue { border-top-color: #3498db; }
        
        .stat-icon { font-size: 35px; color: #ddd; margin-right: 20px; }
        .stat-card.red .stat-icon { color: var(--fatec-red); }
        .stat-card.green .stat-icon { color: #2ecc71; }
        .stat-card.orange .stat-icon { color: #f39c12; }
        .stat-card.blue .stat-icon { color: #3498db; }
        
        .stat-info h3 { font-size: 28px; color: #333; margin-bottom: 5px; line-height: 1; }
        .stat-info p { color: #888; font-size: 13px; font-weight: 600; text-transform: uppercase; }

        .alerta-caixa { background: #fee2e2; color: #b91c1c; padding: 20px; border-radius: 8px; border-left: 5px solid #b91c1c; margin-bottom: 25px; display: flex; align-items: flex-start; gap: 15px; box-shadow: 0 4px 6px rgba(185, 28, 28, 0.05); }
        .alerta-caixa i { font-size: 24px; margin-top: 2px; }
        .alerta-caixa ul { margin-top: 10px; padding-left: 20px; }
        .alerta-caixa li { margin-bottom: 5px; font-size: 14px; }

        .bloco-conteudo { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .bloco-conteudo h3 { color: #333; font-size: 16px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        
        /* Tabela simplificada para Dashboard */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { color: #888; font-weight: 600; text-transform: uppercase; font-size: 11px; }
        tr:hover { background-color: #fcfcfc; }
        .btn-sm { background: var(--fatec-red); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 11px; font-weight: bold; }
        
        .empty-state { text-align: center; padding: 30px; color: #888; font-size: 14px; }
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
                        <a href="meus_relatorios.php" class="<?php echo ($pagina_atual == 'meus_relatorios.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-list-check"></i> <span class="menu-text">Meus Relatórios</span>
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
                <h1>Painel de Controle</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($nome_usuario); ?></strong></div>
        </header>

        <p style="color: #666; margin-bottom: 25px;">Bem-vindo(a) ao Sistema de Gestão de Horas Atividades Específicas da Fatec.</p>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'perfil_concluido'): ?>
            <div class="alert-success" style="margin-bottom: 25px;">✅ Perfil completado com sucesso! Agora você tem acesso total ao sistema.</div>
        <?php endif; ?>

        <?php 
        // ==========================================
        // VISÃO DO PROFESSOR
        // ==========================================
        if ($funcao == 'Professor'): ?>
            
            <?php if (!empty($alertas)): ?>
                <div class="alerta-caixa">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong style="font-size: 15px;">Aviso Importante: Prazo de Relatórios</strong>
                        <ul>
                            <?php foreach($alertas as $alerta): ?>
                                <li><?php echo $alerta; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <div class="dashboard-cards">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fa-solid fa-folder-open"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_projetos; ?></h3>
                        <p>Total de Projetos HAE</p>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon"><i class="fa-solid fa-check-double"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $aprovados; ?></h3>
                        <p>Projetos Aprovados</p>
                    </div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $pendentes; ?></h3>
                        <p>Aguardando Análise</p>
                    </div>
                </div>
            </div>

            <div class="bloco-conteudo">
                <h3><i class="fa-solid fa-circle-info" style="color: var(--fatec-red); margin-right: 5px;"></i> Orientações Rápidas</h3>
                <p style="font-size: 14px; color: #555; margin-bottom: 10px;">• Para solicitar novas horas, acesse o menu <strong>Nova Solicitação</strong> e preencha o formulário detalhado.</p>
                <p style="font-size: 14px; color: #555; margin-bottom: 10px;">• Após a aprovação da direção, lembre-se de acessar <strong>Enviar Relatório</strong> até o dia 10 do mês seguinte.</p>
                <p style="font-size: 14px; color: #555;">• O PDF final com as assinaturas fica disponível em <strong>Meus Projetos</strong>.</p>
            </div>

        <?php 
        // ==========================================
        // VISÃO DA DIREÇÃO / COORDENAÇÃO
        // ==========================================
        else: ?>
            
            <div class="dashboard-cards">
                <div class="stat-card orange">
                    <div class="stat-icon"><i class="fa-solid fa-bell"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $solicitacoes_pendentes; ?></h3>
                        <p>Projetos P/ Análise</p>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon"><i class="fa-solid fa-file-signature"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $relatorios_mes; ?></h3>
                        <p>Relatórios Entregues (Mês)</p>
                    </div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_professores; ?></h3>
                        <p>Professores Ativos</p>
                    </div>
                </div>
            </div>

            <div class="bloco-conteudo">
                <h3 style="display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fa-solid fa-clipboard-list" style="color: #f39c12; margin-right: 5px;"></i> Fila de Análise Recente</span>
                    <?php if (count($projetos_recentes) > 0): ?>
                        <a href="analisar_solicitacoes.php" style="font-size: 12px; color: var(--fatec-red); text-decoration: none; font-weight: normal;">Ver todos →</a>
                    <?php endif; ?>
                </h3>
                
                <?php if (count($projetos_recentes) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Professor(a)</th>
                                <th>Projeto</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($projetos_recentes as $proj): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($proj['data_criacao'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($proj['professor_nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($proj['titulo_projeto']); ?></td>
                                    <td><a href="analisar_solicitacoes.php?id=<?php echo $proj['id']; ?>" class="btn-sm">Analisar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-check-circle" style="color: #2ecc71; font-size: 30px; margin-bottom: 10px; display:block;"></i>
                        A fila de análise está zerada. Todos os projetos foram verificados!
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </main>

    <script src="assets/js/painel.js"></script>
</body>
</html>