<?php
session_start();
require 'config/conexao.php';

// Segurança Rigorosa: Apenas DIRETOR tem acesso à listagem e gestão geral de usuários
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] !== 'Diretor') {
    header("Location: painel.php");
    exit;
}

$sucesso = "";
$erro = "";
$usuario_logado_id = $_SESSION['usuario_id'];

// ==============================================================================
// LÓGICA DE EXCLUSÃO DE USUÁRIO E RESET DE SENHA
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    
    // EXCLUSÃO
    if ($_POST['acao'] == 'excluir') {
        $id_excluir = (int)$_POST['usuario_id'];
        
        if ($id_excluir === $usuario_logado_id) {
            $erro = "Operação bloqueada: Você não pode excluir a sua própria conta.";
        } else {
            try {
                $sql_del = "DELETE FROM usuarios WHERE id = ?";
                $stmt_del = $pdo->prepare($sql_del);
                $stmt_del->execute([$id_excluir]);
                $sucesso = "Usuário excluído com sucesso!";
            } catch (PDOException $e) {
                $erro = "Não foi possível excluir este usuário. Ele possui projetos, relatórios ou pareceres vinculados no sistema.";
            }
        }
    }
    
    // RESET DE SENHA (PARA DATA DE NASCIMENTO)
    if ($_POST['acao'] == 'reset_senha') {
        $id_reset = (int)$_POST['usuario_id'];
        
        try {
            // Busca a data de nascimento do usuário no banco
            $stmt_nasc = $pdo->prepare("SELECT data_nascimento, nome FROM usuarios WHERE id = ?");
            $stmt_nasc->execute([$id_reset]);
            $user_data = $stmt_nasc->fetch(PDO::FETCH_ASSOC);
            
            if ($user_data && !empty($user_data['data_nascimento'])) {
                // Converte a data para o formato ddmmaaaa (Ex: 15051980)
                $nova_senha_plana = date('dmY', strtotime($user_data['data_nascimento']));
                $nova_senha_hash = md5($nova_senha_plana);
                
                // ATUALIZAÇÃO: Usa o status "2" para identificar que é apenas um Reset de Senha
                $sql_reset = "UPDATE usuarios SET senha = ?, primeiro_acesso = 2 WHERE id = ?";
                $stmt_reset = $pdo->prepare($sql_reset);
                $stmt_reset->execute([$nova_senha_hash, $id_reset]);
                
                $sucesso = "Senha de <strong>" . htmlspecialchars($user_data['nome']) . "</strong> resetada com sucesso! A nova senha temporária é a data de nascimento (DDMMAAAA): <strong>$nova_senha_plana</strong>";
            } else {
                $erro = "Não foi possível resetar a senha: O usuário não possui data de nascimento cadastrada no sistema.";
            }
        } catch (PDOException $e) {
            $erro = "Erro ao resetar a senha: " . $e->getMessage();
        }
    }
}

// ==============================================================================
// LÓGICA DE EDIÇÃO RÁPIDA DE USUÁRIO (VIA MODAL)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'editar') {
    $id_edit = (int)$_POST['edit_id'];
    $nome = trim($_POST['edit_nome']);
    $email = trim($_POST['edit_email']);
    $whatsapp = trim($_POST['edit_whatsapp']);
    $funcao = $_POST['edit_funcao'];
    $data_admissao = $_POST['edit_data_admissao'];
    $tipo_contrato = $_POST['edit_tipo_contrato'];

    if ($id_edit === $usuario_logado_id && $funcao !== 'Diretor') {
        $erro = "Operação bloqueada: Você não pode alterar sua própria função para não perder o acesso ao painel.";
    } else {
        $stmt_chk = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt_chk->execute([$email, $id_edit]);
        
        if ($stmt_chk->rowCount() > 0) {
            $erro = "O E-mail '$email' já está em uso por outro usuário no sistema.";
        } else {
            try {
                $sql_upd = "UPDATE usuarios SET nome = ?, email = ?, telefone_whatsapp = ?, funcao = ?, data_admissao = ?, tipo_contrato = ? WHERE id = ?";
                $stmt_upd = $pdo->prepare($sql_upd);
                $stmt_upd->execute([$nome, $email, $whatsapp, $funcao, $data_admissao, $tipo_contrato, $id_edit]);
                
                if ($id_edit === $usuario_logado_id) {
                    $_SESSION['usuario_nome'] = $nome;
                }
                
                $sucesso = "Dados do usuário atualizados com sucesso!";
            } catch (PDOException $e) {
                $erro = "Erro ao atualizar usuário: " . $e->getMessage();
            }
        }
    }
}

// ==============================================================================
// LÓGICA DE FILTRAGEM, BUSCA E PAGINAÇÃO
// ==============================================================================
$filtro_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_funcao = isset($_GET['funcao']) ? trim($_GET['funcao']) : 'Todos';

$where = ["1=1"];
$params = [];

if (!empty($filtro_busca)) {
    $where[] = "(nome LIKE ? OR email LIKE ?)";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
}

if ($filtro_funcao != 'Todos') {
    $where[] = "funcao = ?";
    $params[] = $filtro_funcao;
}

$sql = "SELECT id, nome, email, telefone_whatsapp, funcao, data_admissao, tipo_contrato, primeiro_acesso, data_nascimento 
        FROM usuarios 
        WHERE " . implode(" AND ", $where) . " 
        ORDER BY funcao ASC, nome ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios_totais = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- INÍCIO DA PAGINAÇÃO ----
$limite_por_pagina = 10;
$total_usuarios = count($usuarios_totais);
$total_paginas = ceil($total_usuarios / $limite_por_pagina);

$pagina_atual_pag = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual_pag < 1) $pagina_atual_pag = 1;
if ($pagina_atual_pag > $total_paginas && $total_paginas > 0) $pagina_atual_pag = $total_paginas;

$offset = ($pagina_atual_pag - 1) * $limite_por_pagina;

$usuarios = array_slice($usuarios_totais, $offset, $limite_por_pagina);

$query_params = $_GET;
unset($query_params['pagina']); 
$query_string = http_build_query($query_params);
$url_base = "listar_usuarios.php?" . ($query_string ? $query_string . "&" : "");
// ---- FIM DA PAGINAÇÃO ----

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuários - HAE Fatec</title>
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
        td { vertical-align: middle; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; white-space: nowrap; }
        .badge-diretor { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .badge-coord { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-prof { background: #e1f5fe; color: #0288d1; border: 1px solid #81d4fa; }

        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #f4f6f9; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; font-size: 16px; color: var(--fatec-red); font-weight: bold; }
        .flex-col { display: flex; align-items: center; gap: 12px; }
        .info-sub { font-size: 11px; color: #888; margin-top: 3px; display: block; }

        .acoes-flex { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-action { padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-historico { background: #f8f9fa; color: #333; border: 1px solid #ddd; }
        .btn-historico:hover { background: #e9ecef; }
        .btn-pdf { background: #b20000; color: #fff; }
        .btn-pdf:hover { background: #8a0000; }
        .btn-whatsapp { background: #25D366; color: #fff; }
        .btn-whatsapp:hover { background: #128C7E; }
        
        .alert-success { background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 6px; border-left: 4px solid #198754; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 6px; border-left: 4px solid #b91c1c; margin-bottom: 20px; font-size: 14px; }

        /* Paginação Profissional */
        .paginacao { display: flex; justify-content: center; gap: 8px; margin-bottom: 40px; }
        .paginacao a { display: inline-block; padding: 10px 15px; background: #fff; border: 1px solid #ddd; color: #444; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 13px; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .paginacao a:hover { background: #f8f9fa; border-color: #ccc; transform: translateY(-1px); }
        .paginacao a.active { background: var(--fatec-red); color: #fff; border-color: var(--fatec-red); }

        /* Modal de Edição */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
        .modal-box { background: #fff; width: 90%; max-width: 600px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .modal-header { background: var(--fatec-red); color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; font-size: 16px; }
        .btn-close { background: none; border: none; color: white; font-size: 18px; cursor: pointer; transition: 0.2s; }
        .btn-close:hover { color: #ccc; transform: scale(1.1); }
        
        .modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
        .modal-footer { padding: 15px 20px; background: #f8f9fa; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        
        .modal-body .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .modal-body .full-width { grid-column: 1 / -1; }
        .modal-body label { font-size: 12px; color: #555; font-weight: bold; margin-bottom: 5px; display: block; text-transform: uppercase;}
        .modal-body input, .modal-body select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; transition: 0.2s; }
        .modal-body input:focus, .modal-body select:focus { border-color: var(--fatec-red); }

        .btn-cancelar { background: #fff; color: #444; border: 1px solid #ccc; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-cancelar:hover { background: #eee; }
        .btn-salvar { background: var(--fatec-red); color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-salvar:hover { background: #8a0000; }
        
        .btn-reset { background: #f39c12; color: #fff; border: none; padding: 10px 15px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 5px; font-size: 13px;}
        .btn-reset:hover { background: #d68910; }
        
        @media (max-width: 768px) { 
            .modal-body .grid-2 { grid-template-columns: 1fr; } 
            .modal-footer { flex-direction: column-reverse; align-items: stretch;}
        }
    </style>
<!-- INTEGRAÇÃO ONESIGNAL (PUSH NOTIFICATIONS) -->
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
  window.OneSignalDeferred = window.OneSignalDeferred || [];
  OneSignalDeferred.push(async function(OneSignal) {
    await OneSignal.init({
      appId: "f3a9b7ad-ba4b-420c-8290-99f87501f1a3",
      safari_web_id: "web.onesignal.auto.sua_chave_safari_se_houver",
      notifyButton: {
        enable: true,
      },
    });
    OneSignal.login("<?php echo $_SESSION['usuario_id']; ?>");
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
                        <li><a href="projetos_hae.php" class="<?php echo ($pagina_atual == 'projetos_hae.php') ? 'active' : ''; ?>"><i class="fa-solid fa-list-check"></i> <span class="menu-text">Projetos HAE</span></a></li>
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
                <h1>Usuários do Sistema</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <!-- AVISOS DE SUCESSO OU ERRO -->
        <?php if($sucesso): ?>
            <div class="alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $sucesso; ?></div>
        <?php endif; ?>
        <?php if($erro): ?>
            <div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="GET" class="filter-bar">
            <div class="filter-group" style="flex: 2;">
                <label>Buscar por Nome ou E-mail</label>
                <input type="text" name="busca" placeholder="Digite uma palavra-chave..." value="<?php echo htmlspecialchars($filtro_busca); ?>">
            </div>
            
            <div class="filter-group">
                <label>Filtrar por Função</label>
                <select name="funcao">
                    <option value="Todos" <?php echo $filtro_funcao == 'Todos' ? 'selected' : ''; ?>>Todas as Funções</option>
                    <option value="Professor" <?php echo $filtro_funcao == 'Professor' ? 'selected' : ''; ?>>Professores</option>
                    <option value="Coordenador" <?php echo $filtro_funcao == 'Coordenador' ? 'selected' : ''; ?>>Coordenadores</option>
                    <option value="Diretor" <?php echo $filtro_funcao == 'Diretor' ? 'selected' : ''; ?>>Diretores</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-filtrar"><i class="fa-solid fa-filter"></i> Filtrar</button>
                <a href="listar_usuarios.php" class="btn-limpar">Limpar</a>
            </div>
        </form>

        <div class="card-table">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30%;">Usuário</th>
                            <th style="width: 25%;">Contato</th>
                            <th style="width: 15%;">Função / Vínculo</th>
                            <th style="width: 15%; text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($usuarios) > 0): ?>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td>
                                        <div class="flex-col">
                                            <div class="user-avatar"><?php echo strtoupper(substr($u['nome'], 0, 1)); ?></div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($u['nome']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="color: #444;"><i class="fa-regular fa-envelope" style="color: #888; width: 15px;"></i> <?php echo htmlspecialchars($u['email']); ?></div>
                                        <div style="margin-top: 4px;">
                                            <?php if(!empty($u['telefone_whatsapp'])): ?>
                                                <i class="fa-brands fa-whatsapp" style="color: #25D366; width: 15px;"></i> <?php echo htmlspecialchars($u['telefone_whatsapp']); ?>
                                            <?php else: ?>
                                                <span class="info-sub"><i class="fa-solid fa-phone-slash" style="width: 15px;"></i> Sem telefone</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="margin-bottom: 5px;">
                                            <?php 
                                                if($u['funcao'] == 'Diretor') echo '<span class="badge badge-diretor">Diretor</span>';
                                                elseif($u['funcao'] == 'Coordenador') echo '<span class="badge badge-coord">Coordenador</span>';
                                                else echo '<span class="badge badge-prof">Professor</span>';
                                            ?>
                                        </div>
                                        <?php if(!empty($u['tipo_contrato'])): ?>
                                            <span class="info-sub" style="margin-top: 5px;"><?php echo htmlspecialchars($u['tipo_contrato']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="acoes-flex" style="justify-content: center;">
                                            
                                            <!-- REENVIO DE ACESSO VIA WHATSAPP (Aparece se for primeiro_acesso 1 ou 2) -->
                                            <?php 
                                            if (in_array($u['primeiro_acesso'], [1, 2])) {
                                                if (!empty($u['telefone_whatsapp'])) {
                                                    $num_whats = preg_replace('/[^0-9]/', '', $u['telefone_whatsapp']);
                                                    if (strlen($num_whats) >= 10) {
                                                        $senha_prov = date('dmY', strtotime($u['data_nascimento']));
                                                        $msg_reenvio = urlencode("Olá " . $u['nome'] . "! Sua senha de acesso ao portal HAE Fatec foi resetada.\n\n*Seus dados de acesso:*\nLogin: " . $u['email'] . "\nSenha provisória: " . $senha_prov . "\n\nPor favor, acesse o sistema para definir sua nova senha definitiva.\n\n https://sistemahae.page.gd");
                                                        
                                                        echo '<a href="https://wa.me/55'.$num_whats.'?text='.$msg_reenvio.'" target="_blank" class="btn-action btn-whatsapp" title="Reenviar Acesso (WhatsApp)">
                                                                <i class="fa-brands fa-whatsapp"></i>
                                                              </a>';
                                                    }
                                                } else {
                                                    echo '<button type="button" class="btn-action" style="background:#eee; color:#aaa; cursor:not-allowed;" title="Sem número de WhatsApp cadastrado">
                                                            <i class="fa-brands fa-whatsapp"></i>
                                                          </button>';
                                                }
                                            }
                                            ?>

                                            <button type="button" class="btn-action btn-historico" title="Editar Rápido" 
                                                onclick="abrirModalEdicao(
                                                    <?php echo $u['id']; ?>, 
                                                    '<?php echo addslashes(htmlspecialchars($u['nome'])); ?>', 
                                                    '<?php echo addslashes(htmlspecialchars($u['email'])); ?>', 
                                                    '<?php echo addslashes(htmlspecialchars($u['telefone_whatsapp'] ?? '')); ?>', 
                                                    '<?php echo $u['funcao']; ?>', 
                                                    '<?php echo $u['data_admissao']; ?>', 
                                                    '<?php echo $u['tipo_contrato']; ?>'
                                                )">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            
                                            <form method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirm('Atenção: Tem certeza que deseja EXCLUIR o usuário <?php echo addslashes(htmlspecialchars($u['nome'])); ?>? Esta ação não pode ser desfeita.');">
                                                <input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="acao" value="excluir">
                                                <button type="submit" class="btn-action btn-pdf" title="Excluir Usuário">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding: 40px; color: #888;">Nenhum usuário encontrado com os filtros selecionados.</td></tr>
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

        <!-- MODAL DE EDIÇÃO -->
        <div id="modalEdicao" class="modal-overlay">
            <div class="modal-box">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-user-pen"></i> Editar Conta do Usuário</h3>
                    <button type="button" class="btn-close" onclick="fecharModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="modal-body">
                    <form method="POST" id="formEdicaoGeral">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="edit_id" id="edit_id">
                        
                        <div class="full-width" style="margin-bottom: 15px;">
                            <label>Nome Completo</label>
                            <input type="text" name="edit_nome" id="edit_nome" required>
                        </div>
                        
                        <div class="grid-2">
                            <div>
                                <label>E-mail Institucional</label>
                                <input type="email" name="edit_email" id="edit_email" required>
                            </div>
                            <div>
                                <label>WhatsApp (Telefone)</label>
                                <input type="text" name="edit_whatsapp" id="edit_whatsapp">
                            </div>
                        </div>

                        <div class="grid-2">
                            <div>
                                <label>Função no Sistema</label>
                                <select name="edit_funcao" id="edit_funcao" required>
                                    <option value="Professor">Professor</option>
                                    <option value="Coordenador">Coordenador</option>
                                    <option value="Diretor">Diretor</option>
                                </select>
                            </div>
                            <div>
                                <label>Data de Admissão</label>
                                <input type="date" name="edit_data_admissao" id="edit_data_admissao">
                            </div>
                        </div>

                        <div class="full-width">
                            <label>Tipo de Contrato</label>
                            <select name="edit_tipo_contrato" id="edit_tipo_contrato">
                                <option value="">Não Especificado</option>
                                <option value="Determinado">Determinado</option>
                                <option value="Indeterminado">Indeterminado</option>
                            </select>
                        </div>
                        <p style="font-size: 11px; color: #888; margin-top: 15px;">* Alterações de senha ou assinatura só podem ser feitas pelo próprio usuário através da tela de Perfil.</p>
                    </form>
                </div>
                
                <div class="modal-footer">
                    <div style="flex: 1;">
                        <form method="POST" style="margin: 0;" onsubmit="return confirm('ATENÇÃO: Deseja resetar a senha deste usuário para a DATA DE NASCIMENTO dele? O usuário será forçado a criar uma nova senha no próximo login.');">
                            <input type="hidden" name="acao" value="reset_senha">
                            <input type="hidden" name="usuario_id" id="reset_id">
                            <button type="submit" class="btn-reset"><i class="fa-solid fa-unlock-keyhole"></i> Resetar Senha</button>
                        </form>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
                        <button type="submit" form="formEdicaoGeral" class="btn-salvar">Salvar Alterações</button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script src="assets/js/painel.js"></script>
    <script>
        function abrirModalEdicao(id, nome, email, whatsapp, funcao, admissao, contrato) {
            document.getElementById('edit_id').value = id;
            document.getElementById('reset_id').value = id; 
            
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_whatsapp').value = whatsapp;
            document.getElementById('edit_funcao').value = funcao;
            document.getElementById('edit_data_admissao').value = admissao;
            document.getElementById('edit_tipo_contrato').value = contrato;
            
            document.getElementById('modalEdicao').style.display = 'flex';
        }

        function fecharModal() {
            document.getElementById('modalEdicao').style.display = 'none';
        }

        window.onclick = function(event) {
            let modal = document.getElementById('modalEdicao');
            if (event.target == modal) {
                fecharModal();
            }
        }
    </script>
</body>
</html>