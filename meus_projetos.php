<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Professor acessa
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] != 'Professor') {
    header("Location: painel.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// ==============================================================================
// 0. SISTEMA DE EXCLUSÃO DE PROJETOS
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['excluir_projeto_id'])) {
    $id_excluir = (int)$_POST['excluir_projeto_id'];
    try {
        // Regra de Ouro: Só exclui se for do professor logado e NÃO estiver Aprovado
        $stmt_del = $pdo->prepare("DELETE FROM solicitacoes_hae WHERE id = ? AND professor_id = ? AND status_aprovacao != 'Aprovado'");
        $stmt_del->execute([$id_excluir, $usuario_id]);
        
        if ($stmt_del->rowCount() > 0) {
            header("Location: meus_projetos.php?status=excluido");
            exit;
        }
    } catch (PDOException $e) {
        $erro = "Erro ao excluir o projeto: " . $e->getMessage();
    }
}

$meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];

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

$pagina_atual_pag = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
if ($pagina_atual_pag < 1)
    $pagina_atual_pag = 1;
if ($pagina_atual_pag > $total_paginas && $total_paginas > 0)
    $pagina_atual_pag = $total_paginas;

$offset = ($pagina_atual_pag - 1) * $limite_por_pagina;

// Fatiar o array para mostrar apenas os 10 da página atual
$projetos = array_slice($projetos_total, $offset, $limite_por_pagina);

// Construtor de links para paginação manter os filtros aplicados
$query_params = $_GET;
unset($query_params['pagina']);
$query_string = http_build_query($query_params);
$url_base = "meus_projetos.php?" . ($query_string ? $query_string . "&" : "");


// ==============================================================================
// 3. BUSCA DOS RELATÓRIOS VINCULADOS (Para o Modal)
// ==============================================================================
$sql_rel = "SELECT r.* FROM relatorios_hae r 
            JOIN solicitacoes_hae s ON r.solicitacao_id = s.id 
            WHERE s.professor_id = ? AND r.status = 'Publicado' 
            ORDER BY r.ano_referencia DESC, r.mes_referencia DESC";
$stmt_rel = $pdo->prepare($sql_rel);
$stmt_rel->execute([$usuario_id]);
$relatorios_raw = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);

// Agrupa e formata os relatórios dentro do ID de cada projeto correspondente
$relatorios_por_projeto = [];
foreach ($relatorios_raw as $r) {
    $relatorios_por_projeto[$r['solicitacao_id']][] = [
        'id' => $r['id'],
        'periodo' => $meses[(int)$r['mes_referencia']] . '/' . $r['ano_referencia'],
        'data_envio' => date('d/m/Y \à\s H:i', strtotime($r['data_envio']))
    ];
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
        
        .linha-projeto { transition: 0.2s; }
        .linha-projeto:hover { background-color: #f8f9fa; }

        /* BADGES DE STATUS */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; white-space: nowrap; }
        .badge-pendente { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-aprovado { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .badge-devolvido { background: #ffecb3; color: #856404; border: 1px solid #ffeeba; }
        .badge-rejeitado { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        /* BARRA DE BOTÕES DE AÇÃO PROFISSIONAL */
        .actions-wrapper { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .btn-action { padding: 7px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; cursor: pointer; white-space: nowrap; line-height: 1.2; }
        .btn-action:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,0.08); }

        /* Variações de Botões */
        .btn-pdf { background: #2b2d42; color: #fff; }
        .btn-pdf:hover { background: #1a1b28; }

        .btn-relatorios { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .btn-relatorios:hover { background: #16a34a; color: #fff; border-color: #16a34a; }
        .btn-relatorios:hover .count-pill { background: #fff; color: #16a34a; }
        .count-pill { background: #16a34a; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 10px; font-weight: 700; transition: 0.2s; }

        .btn-clone { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .btn-clone:hover { background: #2563eb; color: #fff; border-color: #2563eb; }

        .btn-editar-pendente { background: #f8f9fa; color: #495057; border-color: #dee2e6; }
        .btn-editar-pendente:hover { background: #e9ecef; color: #212529; border-color: #ced4da; }

        .btn-editar { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .btn-editar:hover { background: #f59e0b; color: #fff; border-color: #f59e0b; }

        .btn-motivo { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
        .btn-motivo:hover { background: #ef4444; color: #fff; border-color: #ef4444; }
        
        .btn-excluir { background: #fff; color: #dc2626; border-color: #fecaca; padding: 7px 10px; }
        .btn-excluir:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

        /* PAGINAÇÃO */
        .paginacao { display: flex; justify-content: center; gap: 8px; margin-bottom: 40px; }
        .paginacao a { display: inline-block; padding: 10px 15px; background: #fff; border: 1px solid #ddd; color: #444; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 13px; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .paginacao a:hover { background: #f8f9fa; border-color: #ccc; transform: translateY(-1px); }
        .paginacao a.active { background: var(--fatec-red); color: #fff; border-color: var(--fatec-red); }

        /* ESTILOS DOS MODAIS (POPUPS) */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.65); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-box { background: #fff; padding: 25px; border-radius: 12px; width: 92%; max-width: 500px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); border-top: 4px solid #e74c3c; position: relative; animation: slideDown 0.25s ease-out; }
        .modal-box.modal-lg { max-width: 650px; border-top-color: #16a34a; }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 12px; }
        .modal-header h3 { color: #c0392b; margin: 0; font-size: 18px; display: flex; align-items: center; gap: 8px; }
        .modal-header.header-relatorios h3 { color: #166534; }
        .modal-subtitle { font-size: 12.5px; color: #666; margin-top: 4px; font-weight: normal; }
        
        .btn-close-modal { background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 6px; font-size: 16px; cursor: pointer; color: #64748b; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
        .btn-close-modal:hover { background: #e2e8f0; color: #0f172a; }
        
        .modal-content-text { font-size: 14px; color: #444; line-height: 1.6; max-height: 60vh; overflow-y: auto; padding-right: 5px; }
        .modal-content-text p { background: #fff9f9; padding: 15px; border-radius: 6px; border: 1px solid #f8d7da; margin: 0; }

        /* Tabela dentro do Modal de Relatórios */
        .tabela-modal-rel { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .tabela-modal-rel th { background: #f8fafc; color: #475569; font-size: 11px; padding: 10px 14px; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; }
        .tabela-modal-rel td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; color: #334155; }
        .tabela-modal-rel tr:last-child td { border-bottom: none; }
        
        .empty-state-rel { text-align: center; padding: 30px 15px; color: #64748b; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1; }
        .empty-state-rel i { font-size: 32px; color: #94a3b8; margin-bottom: 10px; display: block; }
        
        .modal-footer { margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    </style>

<!-- FIREBASE PUSH NOTIFICATIONS -->
<script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js"></script>
<script>
  const firebaseConfig = {
      apiKey: "AIzaSyCXkLWCZD3vKkybvp41YyyU_G2vaeZRcs0",
      authDomain: "hae-fatec.firebaseapp.com",
      projectId: "hae-fatec",
      storageBucket: "hae-fatec.firebasestorage.app",
      messagingSenderId: "732325516207",
      appId: "1:732325516207:web:93cdd26e78656ec2ee156a"
  };
  firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();

  function solicitarPermissaoPush() {
      Notification.requestPermission().then((permission) => {
          if (permission === 'granted') {
              navigator.serviceWorker.register('./firebase-messaging-sw.js')
              .then(function(registration) {
                  return messaging.getToken({ 
                      vapidKey: "BEgkKtj6Eq-ttKtvBL3xOoIoyAAdwiWxOLLygWTlwBSEqWx8AY5oZsvFRY033g71NhAhDKg_kcYEErTiE0cbmoE",
                      serviceWorkerRegistration: registration
                  });
              })
              .then((currentToken) => {
                  if (currentToken) {
                      fetch('salvar_token.php', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({ token: currentToken })
                      });
                  }
              }).catch((err) => console.log('Erro ao pegar token:', err));
          }
      });
  }

  document.addEventListener("DOMContentLoaded", function() {
      solicitarPermissaoPush();
  });
</script>
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
           <div class="user-info" style="display:flex; align-items:center; flex-wrap: wrap; justify-content: flex-end;">
    <span style="margin-right: 5px;">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></span>
    
    <!-- PUXA O BOTÃO E OS VÍDEOS -->
    <?php include 'botao_ajuda.php'; ?>
</div>
        </header>

        <?php if (isset($erro)): ?>
            <div class="alert-success" style="margin-bottom: 25px; background: #fee2e2; color: #b91c1c; border-left: 4px solid #b91c1c;">❌ <?php echo $erro; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sucesso'): ?>
            <div class="alert-success" style="margin-bottom: 25px;">✅ Solicitação de projeto enviada para análise com sucesso!</div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'reenviado'): ?>
            <div class="alert-success" style="margin-bottom: 25px;">✅ Projeto corrigido e reenviado para análise com sucesso!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'excluido'): ?>
            <div class="alert-success" style="margin-bottom: 25px; background: #fee2e2; color: #b91c1c; border-color: #b91c1c;">🗑️ Projeto excluído permanentemente com sucesso!</div>
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
                    <?php foreach ($semestres_disponiveis as $sem): ?>
                        <?php if (!empty($sem)): ?>
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
                    <option value="Devolvido" <?php echo $filtro_status == 'Devolvido' ? 'selected' : ''; ?>>Devolvidos p/ Ajuste</option>
                    <option value="Rejeitado" <?php echo $filtro_status == 'Rejeitado' ? 'selected' : ''; ?>>Rejeitados (Bloqueados)</option>
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
                            <th style="width: 36%;">Título do Projeto</th>
                            <th>Semestre</th>
                            <th>Status Global</th>
                            <th>Central de Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($projetos) > 0): ?>
                            <?php foreach ($projetos as $proj): ?>
                                <?php
                                $badge_class = 'badge-pendente';
                                if ($proj['status_aprovacao'] == 'Aprovado') $badge_class = 'badge-aprovado';
                                if ($proj['status_aprovacao'] == 'Rejeitado') $badge_class = 'badge-rejeitado';
                                if ($proj['status_aprovacao'] == 'Devolvido') $badge_class = 'badge-devolvido';

                                $motivo_recusa = "";
                                if (in_array($proj['status_aprovacao'], ['Rejeitado', 'Devolvido'])) {
                                    if ($proj['status_coordenador'] == 'Rejeitado' || $proj['status_coordenador'] == 'Devolvido')
                                        $motivo_recusa = $proj['parecer_coordenador'];
                                    else if ($proj['status_diretor'] == 'Rejeitado' || $proj['status_diretor'] == 'Devolvido')
                                        $motivo_recusa = $proj['parecer_diretor'];
                                }
                                
                                $titulo_exibicao = preg_replace('/\s*-\s*v\d+\.\d+\s*$/i', '', $proj['titulo_projeto']);
                                $qtd_relatorios = isset($relatorios_por_projeto[$proj['id']]) ? count($relatorios_por_projeto[$proj['id']]) : 0;
                                ?>
                                <tr class="linha-projeto">
                                    <td><span style="color: #64748b; font-weight: 500;"><?php echo date('d/m/Y', strtotime($proj['data_criacao'])); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($titulo_exibicao); ?></strong></td>
                                    <td><?php echo htmlspecialchars($proj['semestre']); ?></td>
                                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $proj['status_aprovacao']; ?></span></td>
                    
                                    <td>
                                        <div class="actions-wrapper">
                                            <!-- 1. Visualizar PDF do Projeto -->
                                            <a href="documento_hae.php?id=<?php echo $proj['id']; ?>" target="_blank" class="btn-action btn-pdf" title="Abrir PDF da Solicitação">
                                                <i class="fa-solid fa-file-pdf"></i> Projeto
                                            </a>

                                            <!-- 2. Novo Botão: Ver Relatórios no Modal -->
                                            <button type="button" class="btn-action btn-relatorios" 
                                                data-id="<?php echo $proj['id']; ?>" 
                                                data-titulo="<?php echo htmlspecialchars($titulo_exibicao, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-semestre="<?php echo htmlspecialchars($proj['semestre'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-status="<?php echo $proj['status_aprovacao']; ?>"
                                                onclick="abrirModalRelatorios(this)" title="Consultar relatórios mensais deste projeto">
                                                <i class="fa-solid fa-folder-open"></i> Relatórios
                                                <span class="count-pill"><?php echo $qtd_relatorios; ?></span>
                                            </button>
                            
                                            <!-- 3. Ações Condicionais por Status -->
                                            <?php if ($proj['status_aprovacao'] == 'Pendente'): ?>
                                                <a href="nova_solicitacao.php?edit_id=<?php echo $proj['id']; ?>" class="btn-action btn-editar-pendente" title="Editar dados da solicitação">
                                                    <i class="fa-solid fa-pen"></i> Editar
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($proj['status_aprovacao'] == 'Aprovado'): ?>
                                                <a href="nova_solicitacao.php?clone_id=<?php echo $proj['id']; ?>" class="btn-action btn-clone" title="Reaproveitar este projeto para um novo semestre">
                                                    <i class="fa-solid fa-copy"></i> Clonar
                                                </a>
                                            <?php endif; ?>
                            
                                            <?php if (in_array($proj['status_aprovacao'], ['Rejeitado', 'Devolvido'])): ?>
                                                <button type="button" class="btn-action btn-motivo" data-motivo="<?php echo htmlspecialchars($motivo_recusa, ENT_QUOTES, 'UTF-8'); ?>" onclick="abrirModalMotivo(this)" title="Ler feedback do avaliador">
                                                    <i class="fa-solid fa-comment-dots"></i> Parecer
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($proj['status_aprovacao'] == 'Devolvido'): ?>
                                                <a href="nova_solicitacao.php?edit_id=<?php echo $proj['id']; ?>" class="btn-action btn-editar" title="Realizar ajustes solicitados">
                                                    <i class="fa-solid fa-pen-to-square"></i> Corrigir
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- 4. Botão Excluir Compacto (Projetos não aprovados) -->
                                            <?php if ($proj['status_aprovacao'] != 'Aprovado'): ?>
                                                <form method="POST" style="display:inline; margin:0; padding:0;" onsubmit="return confirm('ATENÇÃO: Tem certeza que deseja excluir definitivamente este projeto? Esta ação não pode ser desfeita.');">
                                                    <input type="hidden" name="excluir_projeto_id" value="<?php echo $proj['id']; ?>">
                                                    <button type="submit" class="btn-action btn-excluir" title="Excluir Projeto">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
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

    <!-- MODAL 1: PARECER DO AVALIADOR (MOTIVO DE DEVOLUÇÃO / REJEIÇÃO) -->
    <div class="modal-overlay" id="modalMotivo" onclick="fecharModalMotivo()">
        <div class="modal-box" onclick="event.stopPropagation();">
            <div class="modal-header">
                <h3><i class="fa-solid fa-triangle-exclamation"></i> Parecer do Avaliador</h3>
                <button class="btn-close-modal" onclick="fecharModalMotivo()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-content-text">
                <p id="textoMotivo"></p>
            </div>
        </div>
    </div>

    <!-- MODAL 2: RELATÓRIOS ENTREGUES DO PROJETO -->
    <div class="modal-overlay" id="modalRelatorios" onclick="fecharModalRelatorios()">
        <div class="modal-box modal-lg" onclick="event.stopPropagation();">
            <div class="modal-header header-relatorios">
                <div>
                    <h3><i class="fa-solid fa-folder-open"></i> Relatórios Mensais Entregues</h3>
                    <div class="modal-subtitle" id="subtituloModalRel"></div>
                </div>
                <button class="btn-close-modal" onclick="fecharModalRelatorios()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="modal-content-text" id="corpoModalRelatorios">
                <!-- Preenchido dinamicamente via JavaScript -->
            </div>

            <div class="modal-footer">
                <span style="font-size: 12px; color: #64748b;"><i class="fa-solid fa-circle-info"></i> Apenas relatórios publicados constam nesta lista.</span>
                <div style="display: flex; gap: 8px;">
                    <a href="enviar_relatorio.php" id="btnNovoRelatorioModal" class="btn-action btn-relatorios" style="display: none;">
                        <i class="fa-solid fa-plus"></i> Enviar Relatório
                    </a>
                    
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/painel.js"></script>
    <script>
        // Mapa de relatórios carregado do PHP para abertura instantânea no Modal
        const relatoriosPorProjeto = <?php echo json_encode($relatorios_por_projeto); ?>;

        function abrirModalMotivo(btn) {
            let texto = btn.getAttribute('data-motivo') || 'Nenhum parecer detalhado foi registrado.';
            document.getElementById('textoMotivo').innerHTML = texto.replace(/\n/g, '<br>');
            document.getElementById('modalMotivo').style.display = 'flex';
        }

        function fecharModalMotivo() {
            document.getElementById('modalMotivo').style.display = 'none';
        }

        function abrirModalRelatorios(btn) {
            const idProjeto = btn.getAttribute('data-id');
            const titulo = btn.getAttribute('data-titulo');
            const semestre = btn.getAttribute('data-semestre');
            const status = btn.getAttribute('data-status');

            document.getElementById('subtituloModalRel').innerHTML = `<strong>Projeto:</strong> ${titulo} &bull; <strong>Semestre:</strong> ${semestre}`;
            
            const btnNovo = document.getElementById('btnNovoRelatorioModal');
            btnNovo.style.display = (status === 'Aprovado') ? 'inline-flex' : 'none';

            const lista = relatoriosPorProjeto[idProjeto] || [];
            const container = document.getElementById('corpoModalRelatorios');

            if (lista.length > 0) {
                let html = `
                    <table class="tabela-modal-rel">
                        <thead>
                            <tr>
                                <th>Mês de Referência</th>
                                <th>Data de Envio</th>
                                <th style="text-align: right;">Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                lista.forEach(rel => {
                    html += `
                        <tr>
                            <td><strong><i class="fa-regular fa-calendar-check" style="color: #16a34a; margin-right: 6px;"></i>${rel.periodo}</strong></td>
                            <td>${rel.data_envio}</td>
                            <td style="text-align: right;">
                                <a href="pdf_relatorio.php?id=${rel.id}" target="_blank" class="btn-action btn-pdf" style="padding: 5px 10px; font-size: 11.5px;">
                                    <i class="fa-solid fa-file-pdf"></i> Abrir PDF
                                </a>
                            </td>
                        </tr>
                    `;
                });
                html += `</tbody></table>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="empty-state-rel">
                        <i class="fa-regular fa-folder-open"></i>
                        <strong>Nenhum relatório publicado ainda</strong><br>
                        <span style="font-size: 12.5px;">Os relatórios mensais enviados e finalizados para este projeto aparecerão aqui.</span>
                    </div>
                `;
            }

            document.getElementById('modalRelatorios').style.display = 'flex';
        }

        function fecharModalRelatorios() {
            document.getElementById('modalRelatorios').style.display = 'none';
        }
    </script>
</body>
</html>