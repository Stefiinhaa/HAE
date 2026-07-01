<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Coordenador ou Diretor acessam
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_funcao'], ['Coordenador', 'Diretor'])) {
    header("Location: painel.php");
    exit;
}

$meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];

$visualizando_projeto_id = isset($_GET['projeto_id']) ? (int)$_GET['projeto_id'] : 0;

if ($visualizando_projeto_id > 0) {
    // ==============================================================================
    // TELA 2: HISTÓRICO COMPLETO DE UM PROJETO ESPECÍFICO
    // ==============================================================================
    $sql_proj = "SELECT s.*, u.nome AS professor_nome FROM solicitacoes_hae s JOIN usuarios u ON s.professor_id = u.id WHERE s.id = ?";
    $stmt_proj = $pdo->prepare($sql_proj);
    $stmt_proj->execute([$visualizando_projeto_id]);
    $projeto_detalhe = $stmt_proj->fetch(PDO::FETCH_ASSOC);

    if (!$projeto_detalhe) die("Projeto não encontrado.");

    $sql_hist = "SELECT * FROM relatorios_hae WHERE solicitacao_id = ? ORDER BY ano_referencia DESC, mes_referencia DESC";
    $stmt_hist = $pdo->prepare($sql_hist);
    $stmt_hist->execute([$visualizando_projeto_id]);
    $historico_relatorios = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

} else {
    // ==============================================================================
    // TELA 1: GRID GERAL COM ACORDEÃO E PAGINAÇÃO
    // ==============================================================================
    
    // MUNDO REAL ATIVADO (Filtro inteligente: padrão é o mês de cobrança, ou seja, o mês passado)
    $hoje_obj = new DateTime();
    $primeiro_dia_mes = new DateTime($hoje_obj->format('Y-m-01'));
    $primeiro_dia_mes->modify('-1 month'); // Volta 1 mês automaticamente
    
    $mes_padrao = (int)$primeiro_dia_mes->format('n'); 
    $ano_padrao = (int)$primeiro_dia_mes->format('Y'); 

    $filtro_mes = isset($_GET['mes']) ? (int)$_GET['mes'] : $mes_padrao;
    $filtro_ano = isset($_GET['ano']) ? (int)$_GET['ano'] : $ano_padrao;
    $filtro_status = isset($_GET['status_filtro']) ? $_GET['status_filtro'] : 'Todos';
    $filtro_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

    $params = [$filtro_mes, $filtro_ano]; 
    $where = ["s.status_aprovacao = 'Aprovado'"];

    $ultimo_dia_mes_filtro = date('Y-m-t 23:59:59', strtotime(sprintf('%04d-%02d-01', $filtro_ano, $filtro_mes)));
    $where[] = "(COALESCE(s.data_aprovacao_diretor, s.data_aprovacao_coordenador, s.data_criacao) <= ? OR r.id IS NOT NULL)";
    $params[] = $ultimo_dia_mes_filtro;

    if (!empty($filtro_busca)) {
        $where[] = "(u.nome LIKE ? OR s.titulo_projeto LIKE ?)";
        $params[] = "%$filtro_busca%";
        $params[] = "%$filtro_busca%";
    }

    if ($filtro_status == 'Pendente') {
        $where[] = "r.id IS NULL";
    } elseif ($filtro_status == 'Entregue') {
        $where[] = "r.id IS NOT NULL";
    }

    $sql = "SELECT s.id, s.titulo_projeto, s.semestre, u.nome as professor_nome, u.telefone_whatsapp,
            r.id as relatorio_entregue_id
            FROM solicitacoes_hae s 
            JOIN usuarios u ON s.professor_id = u.id 
            LEFT JOIN relatorios_hae r ON (r.solicitacao_id = s.id AND r.mes_referencia = ? AND r.ano_referencia = ? AND r.status = 'Publicado')
            WHERE " . implode(" AND ", $where) . " 
            ORDER BY u.nome ASC, s.titulo_projeto ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projetos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $projetos_agrupados_total = [];
    foreach ($projetos as $proj) {
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
    $url_base = "acompanhar_relatorios.php?" . ($query_string ? $query_string . "&" : "");
}

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhar Relatórios - HAE Fatec</title>
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
        
        .card-table { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow: hidden; margin-bottom: 30px; border-top: 4px solid var(--fatec-red); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; font-size: 14px; border-bottom: 1px solid #eee; }
        th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        
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
        .badge-pendente { background: #fee2e2; color: #b91c1c; border: 1px solid #f8d7da; }
        .badge-entregue { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .badge-rascunho { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }

        .acoes-flex { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-action { padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-historico { background: #f8f9fa; color: #333; border: 1px solid #ddd; }
        .btn-historico:hover { background: #e9ecef; }
        .btn-pdf { background: #b20000; color: #fff; }
        .btn-pdf:hover { background: #8a0000; }
        .btn-whatsapp { background: #25D366; color: #fff; }
        .btn-whatsapp:hover { background: #128C7E; }

        .btn-voltar { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: #666; text-decoration: none; font-weight: bold; font-size: 14px; }
        .page-header { background: #fff; padding: 25px; border-radius: 10px; margin-bottom: 25px; border-left: 5px solid var(--fatec-red); box-shadow: 0 4px 10px rgba(0,0,0,0.03); }

        .paginacao { display: flex; justify-content: center; gap: 8px; margin-bottom: 40px; }
        .paginacao a { display: inline-block; padding: 10px 15px; background: #fff; border: 1px solid #ddd; color: #444; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 13px; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .paginacao a:hover { background: #f8f9fa; border-color: #ccc; transform: translateY(-1px); }
        .paginacao a.active { background: var(--fatec-red); color: #fff; border-color: var(--fatec-red); }
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
                <li><a href="analisar_solicitacoes.php" class="<?php echo ($pagina_atual == 'analisar_solicitacoes.php') ? 'active' : ''; ?>"><i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">Analisar Solicitações</span></a></li>
                <li><a href="acompanhar_relatorios.php" class="<?php echo ($pagina_atual == 'acompanhar_relatorios.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> <span class="menu-text">Acompanhar Relatórios</span></a></li>
                <li><a href="relatorios_atrasados.php" class="<?php echo ($pagina_atual == 'relatorios_atrasados.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-invoice"></i> <span class="menu-text">Relatórios Atrasados</span></a></li>
                <li><a href="cadastrar_professor.php" class="<?php echo ($pagina_atual == 'cadastrar_professor.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-plus"></i> <span class="menu-text">Cadastrar Usuário</span></a></li>
                <li><a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span class="menu-text">Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Gestão de Relatórios Mensais</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <?php if ($visualizando_projeto_id > 0): ?>
            <a href="acompanhar_relatorios.php" class="btn-voltar"><i class="fa-solid fa-arrow-left"></i> Voltar para a visão geral</a>
            
            <div class="page-header">
                <h2 style="font-size: 18px; color: #333; margin-bottom: 5px;">Histórico de Relatórios</h2>
                <p style="color: #666; font-size: 14px; margin-bottom: 5px;"><strong>Professor(a):</strong> <?php echo htmlspecialchars($projeto_detalhe['professor_nome']); ?></p>
                <p style="color: #666; font-size: 14px;"><strong>Projeto:</strong> <?php echo htmlspecialchars($projeto_detalhe['titulo_projeto']); ?></p>
            </div>

            <div class="card-table">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Data do Envio</th>
                                <th>Status</th>
                                <th>Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($historico_relatorios) > 0): ?>
                                <?php foreach ($historico_relatorios as $hist): ?>
                                    <tr>
                                        <td><strong><?php echo $meses[$hist['mes_referencia']] . ' / ' . $hist['ano_referencia']; ?></strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($hist['data_envio'])); ?></td>
                                        <td>
                                            <?php if ($hist['status'] == 'Publicado'): ?>
                                                <span class="badge badge-entregue"><i class="fa-solid fa-check"></i> Entregue</span>
                                            <?php else: ?>
                                                <span class="badge badge-rascunho"><i class="fa-solid fa-pen"></i> Rascunho</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hist['status'] == 'Publicado'): ?>
                                                <a href="pdf_relatorio.php?id=<?php echo $hist['id']; ?>" target="_blank" class="btn-action btn-pdf">
                                                    <i class="fa-solid fa-file-pdf"></i> Ver Relatório
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #888; font-size: 12px;">Aguardando submissão final</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; padding: 30px; color: #888;">O professor ainda não iniciou nenhum relatório.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <form method="GET" class="filter-bar" style="margin-bottom: 0; width: 100%;">
                    <div class="filter-group" style="flex: 2;">
                        <label>Buscar Professor ou Projeto</label>
                        <input type="text" name="busca" placeholder="Digite um nome..." value="<?php echo htmlspecialchars($filtro_busca); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Mês de Referência</label>
                        <select name="mes">
                            <?php foreach($meses as $num => $nome): ?>
                                <option value="<?php echo $num; ?>" <?php echo $filtro_mes == $num ? 'selected' : ''; ?>><?php echo $nome; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Ano</label>
                        <select name="ano">
                            <?php 
                                $ano_inicio = 2024;
                                $ano_atual = max((int)date('Y'), $filtro_ano);
                                for ($a = $ano_inicio; $a <= $ano_atual; $a++): 
                            ?>
                                <option value="<?php echo $a; ?>" <?php echo $filtro_ano == $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Filtro Rápido</label>
                        <select name="status_filtro">
                            <option value="Todos" <?php echo $filtro_status == 'Todos' ? 'selected' : ''; ?>>Todos os Professores</option>
                            <option value="Pendente" <?php echo $filtro_status == 'Pendente' ? 'selected' : ''; ?>>Somente Atrasados</option>
                            <option value="Entregue" <?php echo $filtro_status == 'Entregue' ? 'selected' : ''; ?>>Somente Entregues</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn-filtrar"><i class="fa-solid fa-magnifying-glass"></i> Consultar</button>
                        <a href="acompanhar_relatorios.php" class="btn-limpar">Limpar</a>
                    </div>
                </form>
            </div>

            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="color: #333;">
                    Situação no período: <span style="color: var(--fatec-red);"><?php echo $meses[$filtro_mes] . '/' . $filtro_ano; ?></span>
                </h3>
            </div>

            <div class="card-table">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40%;">Professor(a)</th>
                                <th>Total de Projetos</th>
                                <th>Resumo do Mês</th>
                                <th style="text-align: right;">Expandir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($projetos_agrupados) > 0): ?>
                                <?php $id_accordion = 1; ?>
                                <?php foreach ($projetos_agrupados as $nome_prof => $lista_projetos): ?>
                                    
                                    <?php 
                                        $qtd_projetos = count($lista_projetos);
                                        $qtd_entregues = 0;
                                        $qtd_pendentes = 0;
                                        foreach($lista_projetos as $p) {
                                            if(!empty($p['relatorio_entregue_id'])) $qtd_entregues++;
                                            else $qtd_pendentes++;
                                        }
                                    ?>
                                    
                                    <tr class="linha-mestra" id="mestra_<?php echo $id_accordion; ?>" onclick="toggleGaveta(<?php echo $id_accordion; ?>)">
                                        <td>
                                            <i class="fa-solid fa-chevron-down icone-expandir"></i>
                                            <strong><?php echo htmlspecialchars($nome_prof); ?></strong>
                                        </td>
                                        <td><span style="color: #666; font-size: 13px;">Vinculado a <?php echo $qtd_projetos; ?> projeto(s)</span></td>
                                        <td>
                                            <?php if($qtd_pendentes > 0): ?>
                                                <span class="badge badge-pendente"><?php echo $qtd_pendentes; ?> Pendente(s)</span>
                                            <?php endif; ?>
                                            <?php if($qtd_entregues > 0): ?>
                                                <span class="badge badge-entregue"><?php echo $qtd_entregues; ?> Entregue(s)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; color: var(--fatec-red); font-size: 12px; font-weight: bold;">
                                            Ver detalhes
                                        </td>
                                    </tr>
                                    
                                    <tr class="gaveta-detalhes" id="gaveta_<?php echo $id_accordion; ?>">
                                        <td colspan="4" style="padding: 0;">
                                            <table class="tabela-interna">
                                                <thead>
                                                    <tr>
                                                        <th>Título do Projeto</th>
                                                        <th>Status do Relatório</th>
                                                        <th>Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($lista_projetos as $proj): ?>
                                                        <?php $is_entregue = !empty($proj['relatorio_entregue_id']); ?>
                                                        <tr>
                                                            <td style="width: 50%; color: #444;">
                                                                <strong><?php echo htmlspecialchars($proj['titulo_projeto']); ?></strong><br>
                                                                <span style="font-size: 11px; color: #888; font-weight: bold;">Semestre: <?php echo htmlspecialchars($proj['semestre']); ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($is_entregue): ?>
                                                                    <span class="badge badge-entregue"><i class="fa-solid fa-check"></i> Entregue</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-pendente"><i class="fa-solid fa-triangle-exclamation"></i> Pendente</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <div class="acoes-flex">
                                                                    <a href="acompanhar_relatorios.php?projeto_id=<?php echo $proj['id']; ?>" class="btn-action btn-historico">
                                                                        <i class="fa-solid fa-clock-rotate-left"></i> Histórico
                                                                    </a>

                                                                    <?php if ($is_entregue): ?>
                                                                        <a href="pdf_relatorio.php?id=<?php echo $proj['relatorio_entregue_id']; ?>" target="_blank" class="btn-action btn-pdf">
                                                                            <i class="fa-solid fa-file-pdf"></i> Abrir Relatório
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <?php 
                                                                            $num_whats = preg_replace('/[^0-9]/', '', $proj['telefone_whatsapp']);
                                                                            $msg = urlencode("Olá professor(a)! Consta em nosso sistema que o relatório HAE referente ao mês de " . $meses[$filtro_mes] . "/$filtro_ano para o projeto '" . $proj['titulo_projeto'] . "' ainda não foi enviado. Por favor, acesse o portal para regularizar.");
                                                                        ?>
                                                                        <?php if(strlen($num_whats) >= 10): ?>
                                                                            <a href="https://wa.me/55<?php echo $num_whats; ?>?text=<?php echo $msg; ?>" target="_blank" class="btn-action btn-whatsapp">
                                                                                <i class="fa-brands fa-whatsapp"></i> Cobrar Professor
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                </div>
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
                                <tr><td colspan="4" style="text-align:center; padding: 40px; color: #888;">Nenhum registro encontrado para este filtro.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

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
    </script>
</body>
</html>