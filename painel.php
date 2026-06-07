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
$usuario_id = $_SESSION['usuario_id'];
$pagina_atual = basename($_SERVER['PHP_SELF']);

$meses_nome = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];

// =========================================================================
// ⏳ MÁQUINA DO TEMPO (SIMULAÇÃO) - Mude a data abaixo para testar!
// =========================================================================
$hoje = new DateTime('2026-07-11'); // Testando o dia 11 (Gera Inadimplência)
// =========================================================================

$dia_atual = (int)$hoje->format('d');
$mes_atual = (int)$hoje->format('m');
$ano_atual = (int)$hoje->format('Y');

$data_limite = new DateTime("$ano_atual-$mes_atual-01");
$mes_passado_obj = clone $data_limite;
$mes_passado_obj->modify('-1 month');
$mes_passado_num = (int)$mes_passado_obj->format('m');
$ano_passado_num = (int)$mes_passado_obj->format('Y');

$pendencias_professor = [];
$inadimplentes_geral = [];
$cobrancas_ativas_geral = [];

if ($funcao == 'Professor') {
    $stmt_kpi1 = $pdo->prepare("SELECT COUNT(*) FROM solicitacoes_hae WHERE professor_id = ? AND status_aprovacao = 'Aprovado'");
    $stmt_kpi1->execute([$usuario_id]);
    $kpi_projetos = $stmt_kpi1->fetchColumn();

    $stmt_kpi2 = $pdo->prepare("SELECT COUNT(*) FROM relatorios_hae r JOIN solicitacoes_hae s ON r.solicitacao_id = s.id WHERE s.professor_id = ? AND r.status = 'Publicado'");
    $stmt_kpi2->execute([$usuario_id]);
    $kpi_entregues = $stmt_kpi2->fetchColumn();

    $stmt = $pdo->prepare("SELECT id, titulo_projeto, COALESCE(data_aprovacao_diretor, data_aprovacao_coordenador, data_criacao) AS data_base FROM solicitacoes_hae WHERE professor_id = ? AND status_aprovacao = 'Aprovado'");
    $stmt->execute([$usuario_id]);
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
                    $pendencias_professor[] = ['projeto' => $proj['titulo_projeto'], 'mes_ano' => $meses_nome[$mes_ref] . '/' . $ano_ref, 'status' => 'aviso', 'msg' => "Período de envio aberto. Você tem até o dia 10 para enviar o relatório de " . $meses_nome[$mes_ref] . "."];
                } else {
                    $pendencias_professor[] = ['projeto' => $proj['titulo_projeto'], 'mes_ano' => $meses_nome[$mes_ref] . '/' . $ano_ref, 'status' => 'atrasado', 'msg' => "Prazo encerrado! Este relatório está oficialmente atrasado."];
                }
            }
            $data_iteracao->modify('+1 month');
        }
    }
} else {
    if ($funcao == 'Coordenador') {
        $stmt_kpi1 = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_coordenador = 'Pendente' AND status_aprovacao != 'Rejeitado'");
    } else {
        $stmt_kpi1 = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_diretor = 'Pendente' AND status_aprovacao != 'Rejeitado'");
    }
    $kpi_analises = $stmt_kpi1->fetchColumn();

    $kpi_projetos_ativos = $pdo->query("SELECT COUNT(*) FROM solicitacoes_hae WHERE status_aprovacao = 'Aprovado'")->fetchColumn();

    $stmt_kpi3 = $pdo->prepare("SELECT COUNT(*) FROM relatorios_hae WHERE mes_referencia = ? AND ano_referencia = ? AND status = 'Publicado'");
    $stmt_kpi3->execute([$mes_passado_num, $ano_passado_num]);
    $kpi_relatorios_mes = $stmt_kpi3->fetchColumn();

    $stmt = $pdo->query("SELECT s.id, s.titulo_projeto, COALESCE(s.data_aprovacao_diretor, s.data_aprovacao_coordenador, s.data_criacao) AS data_base, u.nome, u.telefone_whatsapp FROM solicitacoes_hae s JOIN usuarios u ON s.professor_id = u.id WHERE s.status_aprovacao = 'Aprovado'");
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
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 35px; }
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
                <li><a href="painel.php" class="active"><i class="fa-solid fa-chart-pie"></i> <span class="menu-text">Dashboard</span></a></li>
                <?php if ($_SESSION['usuario_funcao'] == 'Professor'): ?>
                    <li><a href="nova_solicitacao.php"><i class="fa-solid fa-file-circle-plus"></i> <span class="menu-text">Nova Solicitação</span></a></li>
                    <li><a href="meus_projetos.php"><i class="fa-solid fa-folder-open"></i> <span class="menu-text">Meus Projetos</span></a></li>
                    <li><a href="enviar_relatorio.php"><i class="fa-solid fa-calendar-check"></i> <span class="menu-text">Enviar Relatório</span></a></li>
                    <li><a href="meus_rascunhos.php"><i class="fa-solid fa-file-pen"></i> <span class="menu-text">Meus Rascunhos</span></a></li>
                <?php else: ?>
                    <li><a href="analisar_solicitacoes.php"><i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">Analisar Solicitações</span></a></li>
                    <li><a href="acompanhar_relatorios.php"><i class="fa-solid fa-chart-line"></i> <span class="menu-text">Acompanhar Relatórios</span></a></li>
                    <!-- NOVO MENU EXCLUSIVO PARA O DOCUMENTO DE INADIMPLENTES -->
                    <li><a href="relatorio_inadimplentes.php"><i class="fa-solid fa-file-invoice"></i> <span class="menu-text">Relatório de Inadimplência</span></a></li>
                    <li><a href="cadastrar_professor.php"><i class="fa-solid fa-user-plus"></i> <span class="menu-text">Cadastrar Usuário</span></a></li>
                <?php endif; ?>
                <li><a href="perfil.php"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>
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
            <div class="user-info">
                <span style="background: #e74c3c; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-right: 10px;"><i class="fa-solid fa-flask"></i> MODO SIMULAÇÃO</span>
                Hoje é <strong><?php echo $hoje->format('d/m/Y'); ?></strong>
            </div>
        </header>

        <!-- VISÃO DO PROFESSOR -->
        <?php if ($funcao == 'Professor'): ?>
            <div class="dashboard-cards">
                <a href="meus_projetos.php" class="card">
                    <div class="card-icon" style="background: #e1f5fe; color: #0288d1;"><i class="fa-solid fa-folder-open"></i></div>
                    <div class="card-info"><h3>Projetos Ativos</h3><p><?php echo $kpi_projetos; ?></p></div>
                </a>
                <a href="meus_projetos.php" class="card">
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
                    <div class="alerta-info"><h4>Tudo em dia!</h4><p>Você não possui nenhum relatório pendente.</p></div>
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

        <!-- VISÃO DA DIREÇÃO / COORDENAÇÃO -->
        <?php else: ?>
            <div class="dashboard-cards">
                <a href="analisar_solicitacoes.php" class="card" style="<?php echo $kpi_analises > 0 ? 'border-bottom-color: #f39c12;' : 'border-bottom-color: #2ecc71;'; ?>">
                    <div class="card-icon" style="background: <?php echo $kpi_analises > 0 ? '#fffdf5' : '#f4fbf7'; ?>; color: <?php echo $kpi_analises > 0 ? '#f39c12' : '#2ecc71'; ?>;"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div class="card-info"><h3>Aguardando sua Análise</h3><p style="color: <?php echo $kpi_analises > 0 ? '#d68910' : '#27ae60'; ?>;"><?php echo $kpi_analises; ?></p></div>
                </a>
                <a href="acompanhar_relatorios.php" class="card">
                    <div class="card-icon" style="background: #e8f5e9; color: #2e7d32;"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="card-info"><h3>Relatórios Rec. (Mês <?php echo $mes_passado_num; ?>)</h3><p><?php echo $kpi_relatorios_mes; ?></p></div>
                </a>
                <a href="analisar_solicitacoes.php?status_filtro=Aprovados" class="card">
                    <div class="card-icon" style="background: #e1f5fe; color: #0288d1;"><i class="fa-solid fa-diagram-project"></i></div>
                    <div class="card-info"><h3>Projetos Ativos (Fatec)</h3><p><?php echo $kpi_projetos_ativos; ?></p></div>
                </a>
            </div>

            <!-- O PAINEL AGORA FICA LIMPO! MOSTRAMOS APENAS OS ALERTAS DEPENDENDO DO DIA -->
            <?php if ($dia_atual >= 11): ?>
                <?php if (count($inadimplentes_geral) > 0): ?>
                    <div class="alerta-box atrasado">
                        <i class="fa-solid fa-file-invoice"></i>
                        <div class="alerta-info">
                            <h4>Atenção: Relatório de Inadimplentes Disponível</h4>
                            <p>Existem <strong><?php echo count($inadimplentes_geral); ?></strong> ocorrências de professores que ultrapassaram o prazo de envio estipulado pela coordenação.</p>
                            <a href="relatorio_inadimplentes.php" style="display:inline-block; margin-top:8px; color:inherit; font-weight:bold; text-decoration: none; border-bottom: 1px solid currentColor;">Gerar Documento de Cobrança <i class="fa-solid fa-arrow-right"></i></a>
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