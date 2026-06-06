<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Professor acessa
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] != 'Professor') {
    header("Location: painel.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];

// ==============================================================================
// 1. SISTEMA DE FILTRAGEM
// ==============================================================================
$filtro_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_semestre = isset($_GET['semestre']) ? trim($_GET['semestre']) : 'Todos';
$filtro_status = isset($_GET['status_filtro']) ? trim($_GET['status_filtro']) : 'Todos';

$where = ["professor_id = ?"];
$params = [$usuario_id];

if (!empty($filtro_busca)) {
    $where[] = "(titulo_projeto LIKE ? OR categoria LIKE ?)";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
}
if ($filtro_semestre != 'Todos') {
    $where[] = "semestre = ?";
    $params[] = $filtro_semestre;
}
if ($filtro_status != 'Todos') {
    $where[] = "status_aprovacao = ?";
    $params[] = $filtro_status;
}

// Busca os semestres disponíveis APENAS deste professor para preencher o filtro
$stmt_sem = $pdo->prepare("SELECT DISTINCT semestre FROM solicitacoes_hae WHERE professor_id = ? ORDER BY semestre DESC");
$stmt_sem->execute([$usuario_id]);
$semestres_disponiveis = $stmt_sem->fetchAll(PDO::FETCH_COLUMN);

// Executa a busca de projetos
$sql_proj = "SELECT * FROM solicitacoes_hae WHERE " . implode(" AND ", $where) . " ORDER BY data_criacao DESC";
$stmt_proj = $pdo->prepare($sql_proj);
$stmt_proj->execute($params);
$projetos_total = $stmt_proj->fetchAll(PDO::FETCH_ASSOC);

// ==============================================================================
// 2. PAGINAÇÃO PROFISSIONAL
// ==============================================================================
$limite_por_pagina = 10;
$total_projetos = count($projetos_total);
$total_paginas = ceil($total_projetos / $limite_por_pagina);

$pagina_atual_pag = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual_pag < 1) $pagina_atual_pag = 1;
if ($pagina_atual_pag > $total_paginas && $total_paginas > 0) $pagina_atual_pag = $total_paginas;

$offset = ($pagina_atual_pag - 1) * $limite_por_pagina;

// Fatiar o array para mostrar apenas os 10 da página atual
$projetos = array_slice($projetos_total, $offset, $limite_por_pagina);

// Construtor de links para paginação manter os filtros aplicados
$query_params = $_GET;
unset($query_params['pagina']);
$query_string = http_build_query($query_params);
$url_base = "meus_projetos.php?" . ($query_string ? $query_string . "&" : "");


// ==============================================================================
// 3. BUSCA DOS RELATÓRIOS VINCULADOS (Para a Gaveta)
// ==============================================================================
$sql_rel = "SELECT r.* FROM relatorios_hae r 
            JOIN solicitacoes_hae s ON r.solicitacao_id = s.id 
            WHERE s.professor_id = ? AND r.status = 'Publicado' 
            ORDER BY r.ano_referencia DESC, r.mes_referencia DESC";
$stmt_rel = $pdo->prepare($sql_rel);
$stmt_rel->execute([$usuario_id]);
$relatorios_raw = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);

// Agrupa os relatórios dentro do ID de cada projeto correspondente
$relatorios_por_projeto = [];
foreach($relatorios_raw as $r) {
    $relatorios_por_projeto[$r['solicitacao_id']][] = $r;
}

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Projetos - HAE Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ESTILOS DOS FILTROS */
        .filter-bar { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; border-left: 4px solid var(--fatec-red); }
        .filter-group { display: flex; flex-direction: column; flex: 1; min-width: 150px; }
        .filter-group label { font-size: 12px; font-weight: bold; color: #555; margin-bottom: 5px; text-transform: uppercase; }
        .filter-group input, .filter-group select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; outline: none; font-size: 14px; transition: 0.3s; }
        .filter-group input:focus, .filter-group select:focus { border-color: var(--fatec-red); }
        
        .btn-filtrar { background: var(--fatec-red); color: white; border: none; padding: 11px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-filtrar:hover { background: #8a0000; }
        .btn-limpar { background: #f1f3f5; color: #444; border: 1px solid #ddd; padding: 10px 15px; border-radius: 5px; font-weight: bold; cursor: pointer; text-decoration: none; transition: 0.3s; }
        
        /* ESTILOS DA TABELA */
        .card-table { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow: hidden; border-top: 4px solid var(--fatec-red); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: middle; }
        th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        
        /* ESTILOS DO ACORDEÃO E GAVETA */
        .linha-mestra { cursor: pointer; transition: 0.2s; }
        .linha-mestra:hover { background-color: #f4f6f9; }
        .icone-expandir { color: #888; transition: transform 0.3s; margin-right: 8px; font-size: 12px; }
        .linha-mestra.aberta .icone-expandir { transform: rotate(180deg); color: var(--fatec-red); }
        .linha-mestra.aberta td { background-color: #f9f0f0; border-bottom: none; }
        
        .gaveta-detalhes { display: none; background-color: #fafbfc; }
        .gaveta-aberta { display: table-row; }
        .tabela-interna th { background: #fff; font-size: 11px; border-bottom: 2px solid #eee; padding: 10px 20px; }
        .tabela-interna td { padding: 12px 20px; font-size: 13.5px; }

        /* BADGES E BOTÕES */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; white-space: nowrap; }
        .badge-pendente { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-aprovado { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .badge-rejeitado { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        .btn-action { padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-pdf { background: #1e1e2d; color: #fff; }
        .btn-pdf:hover { background: var(--fatec-red); }
        .btn-editar { background: #f39c12; color: #fff; }
        .btn-editar:hover { background: #d68910; }
        .btn-clone { background: #3498db; color: #fff; }
        .btn-clone:hover { background: #2980b9; }
        .btn-motivo { background: #e74c3c; color: #fff; }
        .btn-motivo:hover { background: #c0392b; }

        /* PAGINAÇÃO */
        .paginacao { display: flex; justify-content: center; gap: 8px; margin-bottom: 40px; }
        .paginacao a { display: inline-block; padding: 10px 15px; background: #fff; border: 1px solid #ddd; color: #444; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 13px; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .paginacao a:hover { background: #f8f9fa; border-color: #ccc; transform: translateY(-1px); }
        .paginacao a.active { background: var(--fatec-red); color: #fff; border-color: var(--fatec-red); }

        /* ESTILOS DO MODAL (POPUP) */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
        .modal-box { background: #fff; padding: 25px; border-radius: 10px; width: 90%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border-top: 4px solid #e74c3c; position: relative; animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .modal-header h3 { color: #c0392b; margin: 0; font-size: 18px; display: flex; align-items: center; gap: 8px; }
        .btn-close-modal { background: none; border: none; font-size: 20px; cursor: pointer; color: #888; transition: 0.3s; }
        .btn-close-modal:hover { color: #333; }
        .modal-content-text { font-size: 14px; color: #444; line-height: 1.6; max-height: 60vh; overflow-y: auto; padding-right: 5px; }
        .modal-content-text p { background: #fff9f9; padding: 15px; border-radius: 6px; border: 1px solid #f8d7da; }
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
                <li><a href="painel.php"><i class="fa-solid fa-chart-pie"></i> <span class="menu-text">Dashboard</span></a></li>
                <li><a href="nova_solicitacao.php"><i class="fa-solid fa-file-circle-plus"></i> <span class="menu-text">Nova Solicitação</span></a></li>
                <li><a href="meus_projetos.php" class="active"><i class="fa-solid fa-folder-open"></i> <span class="menu-text">Meus Projetos</span></a></li>
                <li><a href="enviar_relatorio.php"><i class="fa-solid fa-calendar-check"></i> <span class="menu-text">Enviar Relatório</span></a></li>
                <li><a href="meus_rascunhos.php"><i class="fa-solid fa-file-pen"></i> <span class="menu-text">Meus Rascunhos</span></a></li>
                <li><a href="perfil.php"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span class="menu-text">Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Meus Projetos HAE</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'reenviado'): ?>
            <div class="alert-success" style="margin-bottom: 25px;">✅ Projeto editado e reenviado para análise com sucesso!</div>
        <?php endif; ?>

        <form method="GET" class="filter-bar">
            <div class="filter-group" style="flex: 2;">
                <label>Buscar Título do Projeto</label>
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
                <label>Status do Projeto</label>
                <select name="status_filtro">
                    <option value="Todos" <?php echo $filtro_status == 'Todos' ? 'selected' : ''; ?>>Todos os Status</option>
                    <option value="Pendente" <?php echo $filtro_status == 'Pendente' ? 'selected' : ''; ?>>Aguardando Análise</option>
                    <option value="Aprovado" <?php echo $filtro_status == 'Aprovado' ? 'selected' : ''; ?>>Aprovados</option>
                    <option value="Rejeitado" <?php echo $filtro_status == 'Rejeitado' ? 'selected' : ''; ?>>Devolvidos/Rejeitados</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-filtrar"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
                <a href="meus_projetos.php" class="btn-limpar">Limpar</a>
            </div>
        </form>

        <div class="card-table">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Data de Envio</th>
                            <th style="width: 40%;">Título do Projeto</th>
                            <th>Semestre</th>
                            <th>Status Global</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($projetos) > 0): ?>
                            <?php foreach ($projetos as $proj): ?>
                                <?php 
                                    $badge_class = 'badge-pendente';
                                    if($proj['status_aprovacao'] == 'Aprovado') $badge_class = 'badge-aprovado';
                                    if($proj['status_aprovacao'] == 'Rejeitado') $badge_class = 'badge-rejeitado';

                                    $motivo_recusa = "";
                                    if ($proj['status_aprovacao'] == 'Rejeitado') {
                                        if ($proj['status_coordenador'] == 'Rejeitado') $motivo_recusa = $proj['parecer_coordenador'];
                                        else if ($proj['status_diretor'] == 'Rejeitado') $motivo_recusa = $proj['parecer_diretor'];
                                    }
                                ?>
                                <tr class="linha-mestra" id="mestra_<?php echo $proj['id']; ?>" onclick="toggleGaveta(<?php echo $proj['id']; ?>)">
                                    <td><i class="fa-solid fa-chevron-down icone-expandir"></i> <?php echo date('d/m/Y', strtotime($proj['data_criacao'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($proj['titulo_projeto']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($proj['semestre']); ?></td>
                                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $proj['status_aprovacao']; ?></span></td>
                                    
                                    <td onclick="event.stopPropagation();">
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <a href="documento_hae.php?id=<?php echo $proj['id']; ?>" target="_blank" class="btn-action btn-pdf"><i class="fa-solid fa-file-pdf"></i> Visualizar</a>
                                            
                                            <?php if($proj['status_aprovacao'] == 'Aprovado'): ?>
                                                <a href="nova_solicitacao.php?clone_id=<?php echo $proj['id']; ?>" class="btn-action btn-clone" title="Copiar este projeto para um novo semestre"><i class="fa-solid fa-copy"></i> Clonar</a>
                                            <?php endif; ?>
                                            
                                            <?php if($proj['status_aprovacao'] == 'Rejeitado'): ?>
                                                <button type="button" class="btn-action btn-motivo" data-motivo="<?php echo htmlspecialchars($motivo_recusa, ENT_QUOTES, 'UTF-8'); ?>" onclick="abrirModalMotivo(this, event)">
                                                    <i class="fa-solid fa-circle-exclamation"></i> Ver Motivo
                                                </button>
                                                
                                                <a href="editar_solicitacao.php?id=<?php echo $proj['id']; ?>" class="btn-action btn-editar"><i class="fa-solid fa-pen-to-square"></i> Corrigir</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr class="gaveta-detalhes" id="gaveta_<?php echo $proj['id']; ?>">
                                    <td colspan="5" style="padding: 0;">
                                        <div style="padding: 15px 20px; background: #fafbfc; border-left: 4px solid var(--fatec-red);">
                                            <h4 style="margin-bottom: 10px; font-size: 13px; color: #555; text-transform: uppercase;"><i class="fa-solid fa-folder-open" style="color: var(--fatec-red);"></i> Relatórios Entregues para este Projeto</h4>
                                            
                                            <?php if(isset($relatorios_por_projeto[$proj['id']]) && count($relatorios_por_projeto[$proj['id']]) > 0): ?>
                                                <table class="tabela-interna" style="width: 100%; border-collapse: collapse;">
                                                    <thead>
                                                        <tr>
                                                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Mês de Referência</th>
                                                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Data de Envio</th>
                                                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Documento</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach($relatorios_por_projeto[$proj['id']] as $rel): ?>
                                                            <tr>
                                                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong><?php echo $meses[$rel['mes_referencia']] . '/' . $rel['ano_referencia']; ?></strong></td>
                                                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo date('d/m/Y H:i', strtotime($rel['data_envio'])); ?></td>
                                                                <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                                                    <a href="pdf_relatorio.php?id=<?php echo $rel['id']; ?>" target="_blank" style="color: var(--fatec-red); font-weight: bold; text-decoration: none;"><i class="fa-solid fa-file-pdf"></i> Abrir Relatório</a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                <p style="font-size: 13px; color: #888; margin: 0;">Nenhum relatório publicado para este projeto ainda.</p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding: 40px; color: #888;">Nenhum projeto encontrado com estes filtros.</td></tr>
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

    </main>

    <div class="modal-overlay" id="modalMotivo" onclick="fecharModalMotivo(event)">
        <div class="modal-box" onclick="event.stopPropagation();">
            <div class="modal-header">
                <h3><i class="fa-solid fa-triangle-exclamation"></i> Motivo da Devolução</h3>
                <button class="btn-close-modal" onclick="fecharModalMotivo()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-content-text">
                <p id="textoMotivo"></p>
            </div>
        </div>
    </div>

    <script src="assets/js/painel.js"></script>
    <script>
        // Funcionalidade de Abrir e Fechar a Gaveta
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

        // Lógica do Modal de Motivo
        function abrirModalMotivo(btn, event) {
            event.stopPropagation(); 
            let texto = btn.getAttribute('data-motivo');
            document.getElementById('textoMotivo').innerHTML = texto.replace(/\n/g, '<br>');
            document.getElementById('modalMotivo').style.display = 'flex';
        }

        function fecharModalMotivo() {
            document.getElementById('modalMotivo').style.display = 'none';
        }
    </script>
</body>
</html>