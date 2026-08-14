<?php
session_start();

// Força o PHP a usar o fuso horário do Brasil
date_default_timezone_set('America/Sao_Paulo');
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$funcao = $_SESSION['usuario_funcao'];
$usuario_id = (int)$_SESSION['usuario_id'];
$pagina_atual = basename($_SERVER['PHP_SELF']);

$meses_nome = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];

// MUNDO REAL ATIVADO
$hoje = new DateTime(); 
$dia_atual = (int)$hoje->format('d');
$mes_atual = (int)$hoje->format('m');
$ano_atual = (int)$hoje->format('Y');

// ==============================================================================
// INTELIGÊNCIA TEMPORAL (VIRADA DE SEMESTRE AUTOMÁTICA)
// ==============================================================================
$semestre_real = ($mes_atual <= 6) ? "1/$ano_atual" : "2/$ano_atual";

// Captura o filtro selecionado (por padrão, é o semestre em que estamos)
$filtro_semestre = isset($_GET['semestre']) ? trim($_GET['semestre']) : $semestre_real;

// Segurança: Se tentarem injetar código, volta pro semestre atual
if ($filtro_semestre !== 'Todos' && !preg_match('/^[12]\/\d{4}$/', $filtro_semestre)) {
    $filtro_semestre = $semestre_real;
}

$sql_sem_filter = ($filtro_semestre === 'Todos') ? "" : " AND semestre = '$filtro_semestre'";
$sql_sem_filter_prefix = ($filtro_semestre === 'Todos') ? "" : " AND s.semestre = '$filtro_semestre'";

// Busca os semestres que já existem no banco para montar o select
$stmt_sem = $pdo->query("SELECT DISTINCT semestre FROM solicitacoes_hae ORDER BY semestre DESC");
$semestres_disponiveis = $stmt_sem->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($semestre_real, $semestres_disponiveis)) {
    array_unshift($semestres_disponiveis, $semestre_real); // Garante que o atual apareça mesmo sem projetos
}

$data_limite = new DateTime("$ano_atual-$mes_atual-01");
$mes_passado_obj = clone $data_limite;
$mes_passado_obj->modify('-1 month');
$mes_passado_num = (int)$mes_passado_obj->format('m');
$ano_passado_num = (int)$mes_passado_obj->format('Y');

$pendencias_professor = [];
$inadimplentes_geral = [];
$cobrancas_ativas_geral = [];

// ==============================================================================
// LÓGICA E KPIs DO PROFESSOR (APLICANDO FILTRO DE SEMESTRE)
// ==============================================================================
if ($funcao == 'Professor') {
    $kpi_projetos = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE professor_id = $usuario_id AND status_aprovacao = 'Aprovado' $sql_sem_filter")->fetchColumn();
    $kpi_projetos_devolvidos = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE professor_id = $usuario_id AND status_aprovacao = 'Devolvido' $sql_sem_filter")->fetchColumn();
    $kpi_entregues = $pdo->query("SELECT COUNT(*) FROM relatorios_hae r JOIN solicitacoes_hae s ON r.solicitacao_id = s.id WHERE s.professor_id = $usuario_id AND r.status = 'Publicado' $sql_sem_filter_prefix")->fetchColumn();

    $stmt = $pdo->prepare("SELECT id, titulo_projeto, COALESCE(data_aprovacao_diretor, data_aprovacao_coordenador, data_criacao) AS data_base FROM solicitacoes_hae WHERE professor_id = ? AND status_aprovacao = 'Aprovado' $sql_sem_filter");
    $stmt->execute([$usuario_id]);
    $projetos_aprovados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($projetos_aprovados as $proj) {
        $titulo_limpo = preg_replace('/\s*-\s*v\d+\.\d+\s*$/i', '', $proj['titulo_projeto']);
        $data_base_str = $proj['data_base'];
        $data_iteracao = new DateTime(date('Y-m-01', strtotime($data_base_str)));
        if ($data_iteracao >= $data_limite) continue;

        while ($data_iteracao < $data_limite) {
            $mes_ref = (int)$data_iteracao->format('m');
            $ano_ref = (int)$data_iteracao->format('Y');
            $stmt_rel = $pdo->prepare("SELECT id FROM relatorios_hae WHERE solicitacao_id = ? AND mes_referencia = ? AND ano_referencia = ? AND status = 'Publicado'");
            $stmt_rel->execute([$proj['id'], $mes_ref, $ano_ref]);
            
            if (!$stmt_rel->fetch()) {
                $is_mes_anterior_imediato = ($mes_ref == $mes_passado_num && $ano_ref == $ano_passado_num);
                if ($is_mes_anterior_imediato && $dia_atual <= 10) {
                    $pendencias_professor[] = ['projeto' => $titulo_limpo, 'mes_ano' => $meses_nome[$mes_ref] . '/' . $ano_ref, 'status' => 'aviso', 'msg' => "Período de envio aberto. Você tem até o dia 10 para enviar o relatório de " . $meses_nome[$mes_ref] . "."];
                } else {
                    $pendencias_professor[] = ['projeto' => $titulo_limpo, 'mes_ano' => $meses_nome[$mes_ref] . '/' . $ano_ref, 'status' => 'atrasado', 'msg' => "Prazo encerrado! Este relatório está oficialmente atrasado."];
                }
            }
            $data_iteracao->modify('+1 month');
        }
    }
} 
// ==============================================================================
// LÓGICA E KPIs DA COORDENAÇÃO E DIREÇÃO (APLICANDO FILTRO DE SEMESTRE)
// ==============================================================================
else {
    if ($funcao == 'Coordenador') {
        $kpi_analises = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_coordenador = 'Pendente' AND status_aprovacao NOT IN ('Rejeitado', 'Devolvido') AND (coordenador_alvo_id IS NULL OR coordenador_alvo_id = $usuario_id) $sql_sem_filter")->fetchColumn();
        $kpi_meus_rejeitados = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_coordenador = 'Rejeitado' AND coordenador_id = $usuario_id $sql_sem_filter")->fetchColumn();
        $kpi_meus_devolvidos = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_coordenador = 'Devolvido' AND coordenador_id = $usuario_id $sql_sem_filter")->fetchColumn();
        $kpi_meus_aprovados = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_coordenador = 'Aprovado' AND coordenador_id = $usuario_id $sql_sem_filter")->fetchColumn();
    } else {
        // DIRETOR
        $kpi_analises = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_diretor = 'Pendente' AND status_coordenador = 'Aprovado' AND status_aprovacao NOT IN ('Rejeitado', 'Devolvido') $sql_sem_filter")->fetchColumn();
        $kpi_total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(); // Usuários não dependem de semestre
        $kpi_meus_rejeitados = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_diretor = 'Rejeitado' AND diretor_id = $usuario_id $sql_sem_filter")->fetchColumn();
        $kpi_meus_devolvidos = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_diretor = 'Devolvido' AND diretor_id = $usuario_id $sql_sem_filter")->fetchColumn();
        $kpi_meus_aprovados = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_diretor = 'Aprovado' AND diretor_id = $usuario_id $sql_sem_filter")->fetchColumn();

        // ------------------ CÁLCULOS DO ORÇAMENTO DE HAE ------------------
        $kpi_total_horas_aprovadas = $pdo->query("SELECT SUM(quantidade_horas) FROM solicitacoes_hae WHERE status_aprovacao = 'Aprovado' $sql_sem_filter")->fetchColumn() ?: 0;
        
        $stmt_orcamento = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'total_hae_disponivel'");
        $orcamento_db = $stmt_orcamento->fetchColumn();
        $kpi_hae_disponivel = $orcamento_db ? (int)$orcamento_db : 0;
        $kpi_saldo_hae = $kpi_hae_disponivel - $kpi_total_horas_aprovadas;
        // ------------------------------------------------------------------
        
        $kpi_rejeitados_global = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_aprovacao = 'Rejeitado' $sql_sem_filter")->fetchColumn();
        $kpi_devolvidos_global = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_aprovacao = 'Devolvido' $sql_sem_filter")->fetchColumn();
    }

    $kpi_projetos_ativos = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_aprovacao = 'Aprovado' $sql_sem_filter")->fetchColumn();

    $stmt_kpi3 = $pdo->prepare("SELECT COUNT(*) FROM relatorios_hae r JOIN solicitacoes_hae s ON r.solicitacao_id = s.id WHERE r.mes_referencia = ? AND r.ano_referencia = ? AND r.status = 'Publicado' $sql_sem_filter_prefix");
    $stmt_kpi3->execute([$mes_passado_num, $ano_passado_num]);
    $kpi_relatorios_mes = $stmt_kpi3->fetchColumn();

    $stmt = $pdo->query("SELECT s.id, s.titulo_projeto, COALESCE(s.data_aprovacao_diretor, s.data_aprovacao_coordenador, s.data_criacao) AS data_base, u.nome, u.telefone_whatsapp FROM solicitacoes_hae s JOIN usuarios u ON s.professor_id = u.id WHERE s.status_aprovacao = 'Aprovado' $sql_sem_filter_prefix");
    $projetos_aprovados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($projetos_aprovados as $proj) {
        $data_base_str = $proj['data_base'];
        $data_iteracao = new DateTime(date('Y-m-01', strtotime($data_base_str)));
        if ($data_iteracao >= $data_limite) continue;

        while ($data_iteracao < $data_limite) {
            $mes_ref = (int)$data_iteracao->format('m');
            $ano_ref = (int)$data_iteracao->format('Y');
            $stmt_rel = $pdo->prepare("SELECT id FROM relatorios_hae WHERE solicitacao_id = ? AND mes_referencia = ? AND ano_referencia = ? AND status = 'Publicado'");
            $stmt_rel->execute([$proj['id'], $mes_ref, $ano_ref]);
            
            if (!$stmt_rel->fetch()) {
                $is_mes_anterior_imediato = ($mes_ref == $mes_passado_num && $ano_ref == $ano_passado_num);
                if ($is_mes_anterior_imediato && $dia_atual <= 10) {
                    $cobrancas_ativas_geral[] = ['professor' => $proj['nome'], 'projeto' => $proj['titulo_projeto'], 'mes_ano' => $meses_nome[$mes_ref] . '/' . $ano_ref, 'telefone' => $proj['telefone_whatsapp']];
                } else {
                    $inadimplentes_geral[] = ['professor' => $proj['nome'], 'projeto' => $proj['titulo_projeto'], 'mes_ano' => $meses_nome[$mes_ref] . '/' . $ano_ref, 'telefone' => $proj['telefone_whatsapp']];
                }
            }
            $data_iteracao->modify('+1 month');
        }
    }
}
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
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .card { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 20px; transition: 0.3s; border: 1px solid #eee; border-bottom: 4px solid var(--fatec-red); text-decoration: none; color: inherit; cursor: pointer; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .card-icon { width: 65px; height: 65px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .card-info { flex: 1; }
        .card-info h3 { font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 5px; font-weight: 700; letter-spacing: 0.5px; }
        .card-info p { font-size: 28px; font-weight: 700; color: #333; margin: 0; line-height: 1; }
        .alerta-box { padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .alerta-box.aviso { background: #fffdf5; border-left: 5px solid #f39c12; color: #856404; border-top: 1px solid #faeccc; border-right: 1px solid #faeccc; border-bottom: 1px solid #faeccc;}
        .alerta-box.atrasado { background: #fff9f9; border-left: 5px solid #e74c3c; color: #b91c1c; border-top: 1px solid #f8d7da; border-right: 1px solid #f8d7da; border-bottom: 1px solid #f8d7da;}
        .alerta-box i { font-size: 24px; margin-top: 2px; }
        .alerta-info h4 { margin-bottom: 5px; font-size: 16px; font-weight: bold; }
        .alerta-info p { font-size: 14px; margin-bottom: 5px; }
        .btn-whatsapp { background: #25D366; color: #fff; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-flex; align-items: center; gap: 6px; transition: 0.3s; }
        .btn-whatsapp:hover { background: #128C7E; }
        
        .card-orcamento { grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 1fr 1fr; background: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee; border-left: 5px solid #8e44ad; overflow: hidden; margin-bottom: 20px;}
        .orcamento-box { padding: 25px; text-align: center; border-right: 1px solid #eee; }
        .orcamento-box:last-child { border-right: none; }
        .orcamento-box h3 { font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 10px; font-weight: 700; }
        .orcamento-box p { font-size: 32px; font-weight: 700; color: #333; margin: 0; }
        .orcamento-box p span { font-size: 16px; color: #999; }
        @media (max-width: 768px) { .card-orcamento { grid-template-columns: 1fr; } .orcamento-box { border-right: none; border-bottom: 1px solid #eee; } .orcamento-box:last-child { border-bottom: none; } }
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
                    
                    <?php if ($_SESSION['usuario_funcao'] == 'Diretor'): ?>
                        <li><a href="cadastrar_professor.php" class="<?php echo ($pagina_atual == 'cadastrar_professor.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-plus"></i> <span class="menu-text">Cadastrar Usuário</span></a></li>
                        <li><a href="listar_usuarios.php" class="<?php echo ($pagina_atual == 'listar_usuarios.php') ? 'active' : ''; ?>"><i class="fa-solid fa-users"></i> <span class="menu-text">Lista de Usuários</span></a></li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <li><a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>
                
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
                <h1>Visão Geral do Sistema</h1>
            </div>
            
            <!-- CONTROLE DE FILTRO TEMPORAL INTEGRADO NO TOPO -->
            <div class="user-info" style="display:flex; align-items:center; gap: 15px; flex-wrap: wrap; justify-content: flex-end;">
                <span class="data-hoje">Hoje é <strong><?php echo $hoje->format('d/m/Y'); ?></strong></span>
                <form method="GET" style="margin:0; display:flex; align-items:center; gap:8px; background: #fff; padding: 6px 12px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #eee;">
                    <i class="fa-solid fa-calendar-days" style="color: var(--fatec-red);"></i>
                    <select name="semestre" onchange="this.form.submit()" style="border: none; background: transparent; font-weight: bold; color: #444; outline: none; cursor: pointer; font-size: 13px;">
                        <option value="Todos" <?php echo $filtro_semestre == 'Todos' ? 'selected' : ''; ?>>Histórico Completo</option>
                        <?php foreach($semestres_disponiveis as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $filtro_semestre == $s ? 'selected' : ''; ?>>Semestre <?php echo htmlspecialchars($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </header>

        <!-- ========================================== -->
        <!-- VISÃO DO PROFESSOR                         -->
        <!-- ========================================== -->
        <?php if ($funcao == 'Professor'): ?>
            <div class="dashboard-cards">
                <a href="meus_projetos.php?status_filtro=Aprovado" class="card" style="border-bottom-color: #3498db;">
                    <div class="card-icon" style="background: #e1f5fe; color: #0288d1;"><i class="fa-solid fa-folder-open"></i></div>
                    <div class="card-info"><h3>Projetos Ativos</h3><p><?php echo $kpi_projetos; ?></p></div>
                </a>
                
                <?php if($kpi_projetos_devolvidos > 0): ?>
                <a href="meus_projetos.php?status_filtro=Devolvido" class="card" style="border-bottom-color: #f39c12;">
                    <div class="card-icon" style="background: #fffdf5; color: #f39c12;"><i class="fa-solid fa-rotate-left"></i></div>
                    <div class="card-info"><h3>Devolvidos p/ Ajuste</h3><p style="color: #d68910;"><?php echo $kpi_projetos_devolvidos; ?></p></div>
                </a>
                <?php endif; ?>

                <a href="meus_projetos.php" class="card" style="border-bottom-color: #27ae60;">
                    <div class="card-icon" style="background: #e8f5e9; color: #2e7d32;"><i class="fa-solid fa-file-circle-check"></i></div>
                    <div class="card-info"><h3>Relatórios Entregues</h3><p><?php echo $kpi_entregues; ?></p></div>
                </a>
                <a href="enviar_relatorio.php" class="card" style="<?php echo count($pendencias_professor) > 0 ? 'border-bottom-color: #e74c3c;' : 'border-bottom-color: #2ecc71;'; ?>">
                    <div class="card-icon" style="background: <?php echo count($pendencias_professor) > 0 ? '#fdf2f2' : '#f4fbf7'; ?>; color: <?php echo count($pendencias_professor) > 0 ? '#e74c3c' : '#2ecc71'; ?>;"><i class="fa-solid fa-bell"></i></div>
                    <div class="card-info"><h3>Pendências Atuais</h3><p style="color: <?php echo count($pendencias_professor) > 0 ? '#c0392b' : '#27ae60'; ?>;"><?php echo count($pendencias_professor); ?></p></div>
                </a>
            </div>

            <h2 style="font-size: 18px; color: #333; margin-bottom: 20px;">Quadro de Avisos</h2>
            <?php if (count($pendencias_professor) == 0): ?>
                <div class="alerta-box" style="background: #f4fbf7; border-left: 5px solid #2ecc71; color: #27ae60; border: 1px solid #d1e7dd;">
                    <i class="fa-solid fa-circle-check"></i>
                    <div class="alerta-info"><h4>Tudo em dia!</h4><p>Você não possui nenhum relatório pendente no semestre selecionado.</p></div>
                </div>
            <?php else: ?>
                <?php foreach ($pendencias_professor as $pendencia): ?>
                    <div class="alerta-box <?php echo $pendencia['status']; ?>">
                        <i class="fa-solid <?php echo $pendencia['status'] == 'aviso' ? 'fa-clock' : 'fa-triangle-exclamation'; ?>"></i>
                        <div class="alerta-info">
                            <h4>Pendência: Mês de <?php echo $pendencia['mes_ano']; ?></h4>
                            <p><strong>Projeto:</strong> <?php echo htmlspecialchars($pendencia['projeto']); ?></p>
                            <p><?php echo $pendencia['msg']; ?></p>
                            <a href="enviar_relatorio.php" style="display:inline-block; margin-top:8px; color:inherit; font-weight:bold; text-decoration: none; border-bottom: 1px solid currentColor;">Ir para envio <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <!-- ========================================== -->
        <!-- VISÃO DA DIREÇÃO / COORDENAÇÃO             -->
        <!-- ========================================== -->
        <?php else: ?>
            
            <h2 style="font-size: 15px; color: #7f8c8d; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fa-solid fa-user-shield"></i> Minhas Ações de Avaliação</h2>
            <div class="dashboard-cards">
                <a href="analisar_solicitacoes.php" class="card" style="<?php echo $kpi_analises > 0 ? 'border-bottom-color: #f39c12;' : 'border-bottom-color: #2ecc71;'; ?>">
                    <div class="card-icon" style="background: <?php echo $kpi_analises > 0 ? '#fffdf5' : '#f4fbf7'; ?>; color: <?php echo $kpi_analises > 0 ? '#f39c12' : '#2ecc71'; ?>;"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div class="card-info"><h3>Aguardando Análise</h3><p style="color: <?php echo $kpi_analises > 0 ? '#d68910' : '#27ae60'; ?>;"><?php echo $kpi_analises; ?></p></div>
                </a>
                <a href="analisar_solicitacoes.php?status_filtro=MeusAprovados" class="card" style="border-bottom-color: #27ae60;">
                    <div class="card-icon" style="background: #e8f5e9; color: #2e7d32;"><i class="fa-solid fa-thumbs-up"></i></div>
                    <div class="card-info"><h3>Projetos Aprovei</h3><p><?php echo $kpi_meus_aprovados; ?></p></div>
                </a>
                <a href="analisar_solicitacoes.php?status_filtro=MeusDevolvidos" class="card" style="border-bottom-color: #f39c12;">
                    <div class="card-icon" style="background: #fffdf5; color: #f39c12;"><i class="fa-solid fa-rotate-left"></i></div>
                    <div class="card-info"><h3>Projetos Devolvi</h3><p><?php echo $kpi_meus_devolvidos; ?></p></div>
                </a>
                <a href="analisar_solicitacoes.php?status_filtro=MeusRejeitados" class="card" style="border-bottom-color: #c0392b;">
                    <div class="card-icon" style="background: #fdf2f2; color: #c0392b;"><i class="fa-solid fa-thumbs-down"></i></div>
                    <div class="card-info"><h3>Projetos Rejeitei</h3><p><?php echo $kpi_meus_rejeitados; ?></p></div>
                </a>
            </div>

            <h2 style="font-size: 15px; color: #7f8c8d; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fa-solid fa-building-columns"></i> Panorama Global da Fatec</h2>
            
            <?php if ($funcao == 'Diretor'): ?>
                <div class="card-orcamento">
                    <div class="orcamento-box" style="background: #fdfafc;">
                        <h3><i class="fa-solid fa-wallet" style="color: #8e44ad;"></i> Total HAE Disponível</h3>
                        <p style="color: #8e44ad;"><?php echo $kpi_hae_disponivel; ?> <span>h</span></p>
                    </div>
                    <div class="orcamento-box">
                        <h3><i class="fa-solid fa-chart-pie" style="color: #3498db;"></i> HAEs Já Aprovadas</h3>
                        <p style="color: #3498db;"><?php echo $kpi_total_horas_aprovadas; ?> <span>h</span></p>
                    </div>
                    <div class="orcamento-box" style="background: <?php echo $kpi_saldo_hae < 0 ? '#fdf2f2' : '#f4fbf7'; ?>;">
                        <h3><i class="fa-solid fa-scale-unbalanced" style="color: <?php echo $kpi_saldo_hae < 0 ? '#c0392b' : '#27ae60'; ?>;"></i> Saldo Restante</h3>
                        <p style="color: <?php echo $kpi_saldo_hae < 0 ? '#c0392b' : '#27ae60'; ?>;">
                            <?php echo $kpi_saldo_hae; ?> <span>h</span>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-cards">
                <a href="analisar_solicitacoes.php?status_filtro=Aprovados" class="card" style="border-bottom-color: #3498db;">
                    <div class="card-icon" style="background: #e1f5fe; color: #0288d1;"><i class="fa-solid fa-diagram-project"></i></div>
                    <div class="card-info"><h3>Projetos Ativos (Geral)</h3><p><?php echo $kpi_projetos_ativos; ?></p></div>
                </a>
                
                <a href="acompanhar_relatorios.php" class="card" style="border-bottom-color: #16a085;">
                    <div class="card-icon" style="background: #e8f6f3; color: #16a085;"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="card-info"><h3>Relatórios (<?php echo substr($meses_nome[$mes_passado_num], 0, 3); ?>)</h3><p><?php echo $kpi_relatorios_mes; ?></p></div>
                </a>

                <?php if ($funcao == 'Diretor'): ?>
                    <a href="analisar_solicitacoes.php?status_filtro=Devolvidos" class="card" style="border-bottom-color: #f39c12;">
                        <div class="card-icon" style="background: #fffdf5; color: #f39c12;"><i class="fa-solid fa-rotate-left"></i></div>
                        <div class="card-info"><h3>Devolvidos (Geral)</h3><p><?php echo $kpi_devolvidos_global; ?></p></div>
                    </a>
                    
                    <a href="analisar_solicitacoes.php?status_filtro=Rejeitados" class="card" style="border-bottom-color: #e74c3c;">
                        <div class="card-icon" style="background: #fdf2f2; color: #e74c3c;"><i class="fa-solid fa-ban"></i></div>
                        <div class="card-info"><h3>Rejeitados (Geral)</h3><p><?php echo $kpi_rejeitados_global; ?></p></div>
                    </a>

                    <a href="listar_usuarios.php" class="card" style="border-bottom-color: #e67e22;">
                        <div class="card-icon" style="background: #fdf2e9; color: #e67e22;"><i class="fa-solid fa-users"></i></div>
                        <div class="card-info"><h3>Total de Usuários</h3><p><?php echo $kpi_total_usuarios; ?></p></div>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($dia_atual >= 11): ?>
                <?php if (count($inadimplentes_geral) > 0): ?>
                    <div class="alerta-box atrasado">
                        <i class="fa-solid fa-file-invoice"></i>
                        <div class="alerta-info">
                            <h4>Atenção: Relatório de Inadimplentes Disponível</h4>
                            <p>Existem <strong><?php echo count($inadimplentes_geral); ?></strong> ocorrências de professores que ultrapassaram o prazo de envio estipulado pela coordenação.</p>
                            <a href="relatorios_atrasados.php" style="display:inline-block; margin-top:8px; color:inherit; font-weight:bold; text-decoration: none; border-bottom: 1px solid currentColor;">Gerar Documento de Cobrança <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alerta-box" style="background: #f4fbf7; border-left: 5px solid #2ecc71; color: #27ae60; border: 1px solid #d1e7dd;">
                        <i class="fa-solid fa-circle-check"></i>
                        <div class="alerta-info">
                            <h4>Nenhum inadimplente!</h4>
                            <p>Todos os professores enviaram os relatórios obrigatórios com sucesso neste mês.</p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($dia_atual <= 10): ?>
                <h2 style="font-size: 18px; color: #f39c12; margin-bottom: 15px;"><i class="fa-solid fa-bullhorn"></i> Período de Cobrança Ativo (Dia 01 ao 10)</h2>
                <p style="color: #666; margin-bottom: 20px; font-size: 14px;">Lembre os professores de enviar os relatórios referentes ao mês passado dentro do prazo legal.</p>
                
                <?php if (count($cobrancas_ativas_geral) > 0): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px;">
                        <?php foreach ($cobrancas_ativas_geral as $cob): ?>
                            <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #f39c12; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #eee;">
                                <h4 style="font-size: 15px; margin-bottom: 8px; color: #333;"><i class="fa-solid fa-user-tie" style="color:#ccc;"></i> <?php echo htmlspecialchars($cob['professor']); ?></h4>
                                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Falta entregar: <strong style="color: #d68910;"><?php echo $cob['mes_ano']; ?></strong><br><span style="font-size: 11px; color: #999;"><?php echo htmlspecialchars($cob['projeto']); ?></span></p>
                                
                                <?php 
                                    $num_whats = preg_replace('/[^0-9]/', '', $cob['telefone']);
                                    $msg = urlencode("Olá professor(a)! Lembrete amigável: você tem até o dia 10 para enviar o relatório HAE de " . $cob['mes_ano'] . " do projeto '" . $cob['projeto'] . "'. O portal já está liberado para envio!");
                                ?>
                                <?php if(strlen($num_whats) >= 10): ?>
                                    <a href="https://wa.me/55<?php echo $num_whats; ?>?text=<?php echo $msg; ?>" target="_blank" class="btn-whatsapp" style="background:#f39c12; width: 100%; justify-content: center;"><i class="fa-brands fa-whatsapp"></i> Enviar Lembrete</a>
                                <?php else: ?>
                                     <div style="font-size:12px; color:#888; background:#f4f4f4; padding: 8px; border-radius: 4px; text-align: center;">Sem Telefone</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alerta-box" style="background: #f4fbf7; border-left: 5px solid #2ecc71; color: #27ae60; border: 1px solid #d1e7dd;">
                        <i class="fa-solid fa-thumbs-up"></i>
                        <div class="alerta-info"><h4>Excelente!</h4><p>Todos os professores já enviaram os relatórios referentes ao mês anterior.</p></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        <?php endif; ?>
    </main>
    <script src="assets/js/painel.js"></script>
</body>
</html>