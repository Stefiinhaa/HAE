<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Coordenador ou Diretor acessam
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_funcao'], ['Coordenador', 'Diretor'])) {
    header("Location: painel.php");
    exit;
}

$funcao_logada = $_SESSION['usuario_funcao'];
$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

// ==============================================================================
// 1. PROCESSAMENTO DE APROVAÇÃO / REJEIÇÃO
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    $solicitacao_id = $_POST['solicitacao_id'];
    $novo_status_individual = $_POST['acao'] == 'aprovar' ? 'Aprovado' : 'Rejeitado';
    $horas_aprovadas = $_POST['horas_aprovadas'] ?? 0;
    $parecer = trim($_POST['parecer']);
    $data_hoje = ($novo_status_individual == 'Aprovado') ? date('Y-m-d') : null;

    try {
        $stmt_current = $pdo->prepare("SELECT status_coordenador, status_diretor FROM solicitacoes_hae WHERE id = ?");
        $stmt_current->execute([$solicitacao_id]);
        $current = $stmt_current->fetch(PDO::FETCH_ASSOC);

        $status_coord = $current['status_coordenador'];
        $status_dir = $current['status_diretor'];

        if ($funcao_logada == 'Coordenador') {
            $status_coord = $novo_status_individual;
            $sql = "UPDATE solicitacoes_hae SET status_coordenador = ?, parecer_coordenador = ?, data_aprovacao_coordenador = ?, coordenador_id = ?, quantidade_horas = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$status_coord, $parecer, $data_hoje, $usuario_id, $horas_aprovadas, $solicitacao_id]);
        } else if ($funcao_logada == 'Diretor') {
            $status_dir = $novo_status_individual;
            $sql = "UPDATE solicitacoes_hae SET status_diretor = ?, parecer_diretor = ?, data_aprovacao_diretor = ?, diretor_id = ?, quantidade_horas = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$status_dir, $parecer, $data_hoje, $usuario_id, $horas_aprovadas, $solicitacao_id]);
        }

        $global_status = 'Pendente';
        if ($status_coord == 'Rejeitado' || $status_dir == 'Rejeitado') {
            $global_status = 'Rejeitado';
        } else if ($status_coord == 'Aprovado' && $status_dir == 'Aprovado') {
            $global_status = 'Aprovado';
        }

        $pdo->prepare("UPDATE solicitacoes_hae SET status_aprovacao = ? WHERE id = ?")->execute([$global_status, $solicitacao_id]);
        
        header("Location: analisar_solicitacoes.php?status=sucesso");
        exit;
    } catch (PDOException $e) {
        $mensagem = "Erro ao processar: " . $e->getMessage();
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'sucesso') {
    $mensagem = "Parecer registrado com sucesso!";
}

$visualizando_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$detalhes = null;

if ($visualizando_id) {
    // ==============================================================================
    // TELA 2: VISÃO DETALHADA E PARECER (SPLIT VIEW)
    // ==============================================================================
    $sql = "SELECT s.*, u.nome AS professor_nome FROM solicitacoes_hae s JOIN usuarios u ON s.professor_id = u.id WHERE s.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$visualizando_id]);
    $detalhes = $stmt->fetch(PDO::FETCH_ASSOC);

} else {
    // ==============================================================================
    // TELA 1: GRID INTELIGENTE (ACORDEÃO) COM FILTROS E PAGINAÇÃO
    // ==============================================================================
    $filtro_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
    $filtro_status = isset($_GET['status_filtro']) ? trim($_GET['status_filtro']) : 'Aguardando'; 
    $filtro_semestre = isset($_GET['semestre']) ? trim($_GET['semestre']) : '';

    $where = ["1=1"];
    $params = [];

    // Filtro de Texto
    if (!empty($filtro_busca)) {
        $where[] = "(u.nome LIKE ? OR s.titulo_projeto LIKE ? OR s.categoria LIKE ?)";
        $params[] = "%$filtro_busca%";
        $params[] = "%$filtro_busca%";
        $params[] = "%$filtro_busca%";
    }

    // Filtro de Semestre
    if (!empty($filtro_semestre) && $filtro_semestre != 'Todos') {
        $where[] = "s.semestre = ?";
        $params[] = $filtro_semestre;
    }

    // Filtro de Status Inteligente (CORRIGIDO: Oculta se o outro já rejeitou)
    if ($filtro_status == 'Aguardando') {
        if ($funcao_logada == 'Coordenador') {
            $where[] = "s.status_coordenador = 'Pendente' AND s.status_aprovacao != 'Rejeitado'";
        } else {
            $where[] = "s.status_diretor = 'Pendente' AND s.status_aprovacao != 'Rejeitado'";
        }
    } elseif ($filtro_status == 'Aprovados') {
        $where[] = "s.status_aprovacao = 'Aprovado'";
    } elseif ($filtro_status == 'Rejeitados') {
        $where[] = "s.status_aprovacao = 'Rejeitado'";
    }

    $stmt_sem = $pdo->query("SELECT DISTINCT semestre FROM solicitacoes_hae ORDER BY semestre DESC");
    $semestres_disponiveis = $stmt_sem->fetchAll(PDO::FETCH_COLUMN);

    $sql = "SELECT s.*, u.nome AS professor_nome 
            FROM solicitacoes_hae s 
            JOIN usuarios u ON s.professor_id = u.id 
            WHERE " . implode(" AND ", $where) . " 
            ORDER BY u.nome ASC, s.data_criacao DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $projetos_agrupados_total = [];
    foreach ($solicitacoes as $proj) {
        $projetos_agrupados_total[$proj['professor_nome']][] = $proj;
    }

    $limite_por_pagina = 10;
    $total_professores = count($projetos_agrupados_total);
    $total_paginas = ceil($total_professores / $limite_por_pagina);
    
    $pagina_atual_pag = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_atual_pag < 1) $pagina_atual_pag = 1;
    if ($pagina_atual_pag > $total_paginas && $total_paginas > 0) $pagina_atual_pag = $total_paginas;

    $offset = ($pagina_atual_pag - 1) * $limite_por_pagina;
    $projetos_agrupados = array_slice($projetos_agrupados_total, $offset, $limite_por_pagina, true);

    $query_params = $_GET;
    unset($query_params['pagina']); 
    $query_string = http_build_query($query_params);
    $url_base = "analisar_solicitacoes.php?" . ($query_string ? $query_string . "&" : "");
}

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisar Solicitações - Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-bar { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; border-left: 4px solid #3498db; }
        .filter-group { display: flex; flex-direction: column; flex: 1; min-width: 150px; }
        .filter-group label { font-size: 12px; font-weight: bold; color: #555; margin-bottom: 5px; text-transform: uppercase; }
        .filter-group input, .filter-group select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; outline: none; font-size: 14px; transition: 0.3s; }
        .filter-group input:focus, .filter-group select:focus { border-color: var(--fatec-red); }
        
        .btn-filtrar { background: var(--fatec-red); color: white; border: none; padding: 11px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-filtrar:hover { background: #8a0000; }
        .btn-limpar { background: #f1f3f5; color: #444; border: 1px solid #ddd; padding: 10px 15px; border-radius: 5px; font-weight: bold; cursor: pointer; text-decoration: none; transition: 0.3s; }
        .btn-limpar:hover { background: #e9ecef; }
        
        .card-table { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow: hidden; margin-bottom: 30px; border-top: 4px solid var(--fatec-red); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; font-size: 14px; border-bottom: 1px solid #eee; }
        th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        
        /* ESTILOS DO ACORDEÃO (Gaveta) */
        .linha-mestra { cursor: pointer; transition: 0.2s; }
        .linha-mestra:hover { background-color: #f4f6f9; }
        .linha-mestra td { border-bottom: 1px solid #e0e0e0; }
        .icone-expandir { color: #888; transition: transform 0.3s; margin-right: 8px; font-size: 12px; }
        .linha-mestra.aberta .icone-expandir { transform: rotate(180deg); color: var(--fatec-red); }
        .linha-mestra.aberta td { background-color: #f9f0f0; border-bottom: none; }
        
        .gaveta-detalhes { display: none; background-color: #fafbfc; }
        .gaveta-aberta { display: table-row; }
        .tabela-interna { width: 100%; border-left: 4px solid var(--fatec-red); margin: 0; background: #fff; box-shadow: inset 0 2px 5px rgba(0,0,0,0.02); }
        .tabela-interna th { background: #fff; font-size: 11px; border-bottom: 2px solid #eee; padding: 10px 20px; }
        .tabela-interna td { padding: 12px 20px; font-size: 13.5px; vertical-align: middle; }

        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; white-space: nowrap; }
        .badge-pendente { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-aprovado { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .badge-rejeitado { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .badge-espera { background: #e1f5fe; color: #0288d1; border: 1px solid #81d4fa;}

        .btn-action { background: #1e1e2d; color: #fff; padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; font-weight: bold;}
        .btn-action:hover { background: var(--fatec-red); }
        .btn-voltar { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: #666; text-decoration: none; font-weight: bold; font-size: 14px; }

        .paginacao { display: flex; justify-content: center; gap: 8px; margin-bottom: 40px; }
        .paginacao a { display: inline-block; padding: 10px 15px; background: #fff; border: 1px solid #ddd; color: #444; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 13px; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .paginacao a:hover { background: #f8f9fa; border-color: #ccc; transform: translateY(-1px); }
        .paginacao a.active { background: var(--fatec-red); color: #fff; border-color: var(--fatec-red); }

        .split-view { display: flex; gap: 25px; align-items: flex-start; }
        .doc-preview { flex: 6.5; height: 85vh; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 2px solid #ddd; background: #525659; }
        .doc-preview iframe { width: 100%; height: 100%; border: none; }
        .form-parecer { flex: 3.5; background: #fff; padding: 30px; border-radius: 10px; border-top: 4px solid var(--fatec-red); box-shadow: 0 4px 10px rgba(0,0,0,0.05); position: sticky; top: 20px; }
        .form-parecer label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 13px; color: #444; }
        .form-parecer input, .form-parecer textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 20px; font-size: 14px; outline: none;}
        .form-parecer input:focus, .form-parecer textarea:focus { border-color: var(--fatec-red); }
        .botoes-acao { display: flex; flex-direction: column; gap: 10px; }
        .btn-aprovar { background: #2ecc71; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; transition: 0.3s;}
        .btn-aprovar:hover { background: #27ae60; }
        .btn-rejeitar { background: #e74c3c; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; transition: 0.3s;}
        .btn-rejeitar:hover { background: #c0392b; }
        .historico-box { background: #f8f9fa; border-left: 3px solid #3498db; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; color: #444; }
        .btn-ver-pdf-mobile { display: none; background: #3498db; color: white; padding: 15px; text-align: center; border-radius: 6px; text-decoration: none; font-weight: bold; margin-bottom: 20px; }
        
        @media (max-width: 1024px) {
            .split-view { flex-direction: column; }
            .doc-preview { display: none; }
            .form-parecer { flex: 1; width: 100%; position: relative; top: 0; }
            .btn-ver-pdf-mobile { display: block; }
        }
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
                <h1><?php echo $visualizando_id ? "Análise de Projeto" : "Gestão de Solicitações"; ?></h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <?php if($mensagem): ?>
            <div class="alert-success">✅ <?php echo $mensagem; ?></div>
        <?php endif; ?>

        <?php if ($visualizando_id && $detalhes): ?>
            <a href="analisar_solicitacoes.php" class="btn-voltar"><i class="fa-solid fa-arrow-left"></i> Voltar para a lista</a>
            
            <div class="split-view">
                <div class="doc-preview">
                    <iframe src="documento_hae.php?id=<?php echo $visualizando_id; ?>"></iframe>
                </div>
                <div class="form-parecer">
                    <h3 style="margin-bottom: 20px; color: var(--fatec-red); font-size: 18px;">Emitir Parecer (<?php echo $funcao_logada; ?>)</h3>
                    <a href="documento_hae.php?id=<?php echo $visualizando_id; ?>" target="_blank" class="btn-ver-pdf-mobile">📄 Abrir Documento em Tela Cheia</a>
                    
                    <?php 
                        if ($funcao_logada == 'Diretor' && !empty($detalhes['parecer_coordenador'])) {
                            echo "<div class='historico-box'><strong>Parecer Prévio do Coordenador:</strong><br>".nl2br(htmlspecialchars($detalhes['parecer_coordenador']))."</div>";
                        }
                        if ($funcao_logada == 'Coordenador' && !empty($detalhes['parecer_diretor'])) {
                            echo "<div class='historico-box'><strong>Parecer Prévio do Diretor:</strong><br>".nl2br(htmlspecialchars($detalhes['parecer_diretor']))."</div>";
                        }
                    ?>

                    <?php 
                    // BLOQUEIOS INTELIGENTES DA TELA DE PARECER
                    if ($detalhes['status_aprovacao'] == 'Rejeitado'): 
                    ?>
                        <div class="historico-box" style="border-left-color: #e74c3c; background: #fff9f9;">
                            <strong style="color: #c0392b;"><i class="fa-solid fa-ban"></i> Projeto Devolvido</strong><br>
                            Este projeto já foi avaliado e rejeitado. Ele foi devolvido ao professor para correções e não requer mais ações no momento.
                        </div>
                    <?php 
                    elseif ($detalhes['status_aprovacao'] == 'Aprovado'): 
                    ?>
                        <div class="historico-box" style="border-left-color: #2ecc71; background: #f4fbf7;">
                            <strong style="color: #27ae60;"><i class="fa-solid fa-check-double"></i> Projeto Aprovado</strong><br>
                            Este projeto já foi totalmente aprovado por ambas as instâncias (Direção e Coordenação).
                        </div>
                    <?php 
                    elseif (($funcao_logada == 'Coordenador' && $detalhes['status_coordenador'] != 'Pendente') || ($funcao_logada == 'Diretor' && $detalhes['status_diretor'] != 'Pendente')): 
                    ?>
                        <div class="historico-box" style="border-left-color: #2ecc71; background: #f4fbf7;">
                            <strong style="color: #27ae60;"><i class="fa-solid fa-check"></i> Parecer Concluído</strong><br>
                            Você já emitiu e salvou o seu parecer favorável para este projeto. Agora estamos aguardando apenas a avaliação da outra instância.
                        </div>
                    <?php else: ?>
                        <!-- Se ainda precisa do parecer da pessoa logada, exibe o form -->
                        <form method="POST" action="analisar_solicitacoes.php?id=<?php echo $visualizando_id; ?>" id="formAnalise">
                            <input type="hidden" name="solicitacao_id" value="<?php echo $visualizando_id; ?>">
                            
                            <label>Horas HAE Recomendadas/Aprovadas</label>
                            <input type="number" name="horas_aprovadas" value="<?php echo $detalhes['quantidade_horas']; ?>" required min="0">
                            
                            <label>Seu Parecer Oficial</label>
                            <textarea name="parecer" id="campo_parecer" rows="5" placeholder="Digite sua avaliação sobre o projeto..."><?php echo ($funcao_logada == 'Coordenador') ? htmlspecialchars($detalhes['parecer_coordenador']) : htmlspecialchars($detalhes['parecer_diretor']); ?></textarea>
                            
                            <div class="botoes-acao">
                                <button type="submit" name="acao" value="aprovar" class="btn-aprovar" onclick="return validarParecer('aprovar');">✓ Aprovar Projeto HAE</button>
                                <button type="submit" name="acao" value="rejeitar" class="btn-rejeitar" onclick="return validarParecer('rejeitar');">✕ Rejeitar Projeto</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <form method="GET" class="filter-bar">
                <div class="filter-group" style="flex: 2;">
                    <label>Buscar Professor ou Título</label>
                    <input type="text" name="busca" placeholder="Digite uma palavra-chave..." value="<?php echo htmlspecialchars($filtro_busca); ?>">
                </div>
                
                <div class="filter-group">
                    <label>Semestre</label>
                    <select name="semestre">
                        <option value="Todos">Todos os Semestres</option>
                        <?php foreach($semestres_disponiveis as $sem): ?>
                            <?php if(!empty($sem)): ?>
                                <option value="<?php echo $sem; ?>" <?php echo $filtro_semestre == $sem ? 'selected' : ''; ?>><?php echo $sem; ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Filtro de Status</label>
                    <select name="status_filtro">
                        <option value="Aguardando" <?php echo $filtro_status == 'Aguardando' ? 'selected' : ''; ?>>Aguardando Minha Ação</option>
                        <option value="Todos" <?php echo $filtro_status == 'Todos' ? 'selected' : ''; ?>>Todos os Projetos</option>
                        <option value="Aprovados" <?php echo $filtro_status == 'Aprovados' ? 'selected' : ''; ?>>Aprovados Totalmente</option>
                        <option value="Rejeitados" <?php echo $filtro_status == 'Rejeitados' ? 'selected' : ''; ?>>Rejeitados</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-filtrar"><i class="fa-solid fa-filter"></i> Filtrar</button>
                    <a href="analisar_solicitacoes.php" class="btn-limpar">Limpar</a>
                </div>
            </form>

            <div class="card-table">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40%;">Professor(a)</th>
                                <th>Total de Lançamentos</th>
                                <th>Pendências p/ Mim</th>
                                <th style="text-align: right;">Expandir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($projetos_agrupados) > 0): ?>
                                <?php $id_accordion = 1; ?>
                                <?php foreach ($projetos_agrupados as $nome_prof => $lista_projetos): ?>
                                    
                                    <?php 
                                        $qtd_projetos = count($lista_projetos);
                                        $qtd_minha_acao = 0;
                                        
                                        // CORRIGIDO: O contador só sobe se o projeto não estiver rejeitado
                                        foreach($lista_projetos as $p) {
                                            if ($p['status_aprovacao'] != 'Rejeitado') {
                                                if ($funcao_logada == 'Coordenador' && $p['status_coordenador'] == 'Pendente') $qtd_minha_acao++;
                                                if ($funcao_logada == 'Diretor' && $p['status_diretor'] == 'Pendente') $qtd_minha_acao++;
                                            }
                                        }
                                    ?>
                                    
                                    <tr class="linha-mestra" id="mestra_<?php echo $id_accordion; ?>" onclick="toggleGaveta(<?php echo $id_accordion; ?>)">
                                        <td>
                                            <i class="fa-solid fa-chevron-down icone-expandir"></i>
                                            <strong><i class="fa-solid fa-user-tie" style="color: #888; margin-right: 5px;"></i> <?php echo htmlspecialchars($nome_prof); ?></strong>
                                        </td>
                                        <td><span style="color: #666; font-size: 13px;"><?php echo $qtd_projetos; ?> projeto(s)</span></td>
                                        <td>
                                            <?php if($qtd_minha_acao > 0): ?>
                                                <span class="badge badge-pendente" style="background:#f39c12; color:white; border:none;"><i class="fa-solid fa-bell"></i> <?php echo $qtd_minha_acao; ?> Aguardando Você</span>
                                            <?php else: ?>
                                                <span class="badge" style="background:#eee; color:#888;">Nenhuma pendência</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; color: var(--fatec-red); font-size: 12px; font-weight: bold;">Ver detalhes</td>
                                    </tr>
                                    
                                    <tr class="gaveta-detalhes" id="gaveta_<?php echo $id_accordion; ?>">
                                        <td colspan="4" style="padding: 0;">
                                            <table class="tabela-interna">
                                                <thead>
                                                    <tr>
                                                        <th>Data / Título do Projeto</th>
                                                        <th>Semestre</th>
                                                        <th>Status Geral</th>
                                                        <th>Ação</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($lista_projetos as $proj): ?>
                                                        <?php 
                                                            $texto_status = $proj['status_aprovacao'];
                                                            $badge_class = 'badge-pendente';
                                                            
                                                            if($proj['status_aprovacao'] == 'Aprovado') {
                                                                $badge_class = 'badge-aprovado';
                                                            } else if($proj['status_aprovacao'] == 'Rejeitado') {
                                                                $badge_class = 'badge-rejeitado';
                                                            } else if($proj['status_coordenador'] == 'Aprovado' && $proj['status_diretor'] == 'Pendente') {
                                                                $texto_status = 'Aguardando Diretor';
                                                                $badge_class = 'badge-espera';
                                                            } else if($proj['status_diretor'] == 'Aprovado' && $proj['status_coordenador'] == 'Pendente') {
                                                                $texto_status = 'Aguardando Coord.';
                                                                $badge_class = 'badge-espera';
                                                            } else {
                                                                $texto_status = 'Pendente (Ambos)';
                                                            }
                                                        ?>
                                                        <tr>
                                                            <td style="width: 50%; color: #444;">
                                                                <span style="font-size:11px; color:#888;"><?php echo date('d/m/Y', strtotime($proj['data_criacao'])); ?></span><br>
                                                                <strong><?php echo htmlspecialchars($proj['titulo_projeto']); ?></strong>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($proj['semestre']); ?></td>
                                                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $texto_status; ?></span></td>
                                                            <td>
                                                                <a href="analisar_solicitacoes.php?id=<?php echo $proj['id']; ?>" class="btn-action">
                                                                    <i class="fa-solid fa-folder-open"></i> Avaliar
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>

                                    <?php $id_accordion++; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; padding: 40px; color: #888;">Nenhuma solicitação encontrada com os filtros selecionados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CONTROLES DE PAGINAÇÃO PROFISSIONAL -->
            <?php if ($total_paginas > 1): ?>
                <div class="paginacao">
                    <?php if ($pagina_atual_pag > 1): ?>
                        <a href="<?php echo $url_base . 'pagina=' . ($pagina_atual_pag - 1); ?>"><i class="fa-solid fa-angle-left"></i> Anterior</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="<?php echo $url_base . 'pagina=' . $i; ?>" class="<?php echo $i == $pagina_atual_pag ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($pagina_atual_pag < $total_paginas): ?>
                        <a href="<?php echo $url_base . 'pagina=' . ($pagina_atual_pag + 1); ?>">Próxima <i class="fa-solid fa-angle-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </main>

    <script src="assets/js/painel.js"></script>
    <script>
        function toggleGaveta(id) {
            var gaveta = document.getElementById('gaveta_' + id);
            var linhaMestra = document.getElementById('mestra_' + id);
            
            if (gaveta.classList.contains('gaveta-aberta')) {
                gaveta.classList.remove('gaveta-aberta');
                linhaMestra.classList.remove('aberta');
            } else {
                gaveta.classList.add('gaveta-aberta');
                linhaMestra.classList.add('aberta');
            }
        }

        function validarParecer(acao) {
            var campoParecer = document.getElementById('campo_parecer').value.trim();
            if (acao === 'rejeitar' && campoParecer === '') {
                alert('Atenção: É obrigatório informar o motivo da rejeição no campo de Parecer!');
                document.getElementById('campo_parecer').focus();
                return false; 
            }
            if (acao === 'rejeitar') {
                return confirm('Tem certeza que deseja REJEITAR este projeto HAE?');
            } else {
                return confirm('Confirmar o seu parecer FAVORÁVEL para este projeto?');
            }
        }
    </script>
</body>
</html>