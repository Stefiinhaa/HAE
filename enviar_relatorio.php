<?php
session_start();
require 'config/conexao.php';

// Segurança
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] !== 'Professor') {
    header("Location: painel.php");
    exit;
}

$professor_id = $_SESSION['usuario_id'];
$id_projeto = isset($_GET['id_projeto']) ? (int)$_GET['id_projeto'] : 0;

$sucesso = "";
$erro = "";

// Verifica se o projeto existe e está aprovado
$sql_proj = "SELECT * FROM solicitacoes_hae WHERE id = ? AND professor_id = ? AND status_aprovacao = 'Aprovado'";
$stmt = $pdo->prepare($sql_proj);
$stmt->execute([$id_projeto, $professor_id]);
$projeto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$projeto) {
    die("Projeto inválido, não encontrado ou ainda não aprovado.");
}

// Mensagens de sucesso via URL (Evita reenvio de formulário ao atualizar a página)
if (isset($_GET['status_msg']) && $_GET['status_msg'] == 'sucesso') {
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
    $sucesso = "Relatório " . ($tipo == 'Publicado' ? "publicado em definitivo" : "salvo como rascunho") . " com sucesso!";
}

// LÓGICA DE EDIÇÃO (Quando clica no botão Editar da tabela)
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$relatorio_edit = null;

if ($edit_id) {
    $stmt_edit = $pdo->prepare("SELECT * FROM relatorios_hae WHERE id = ? AND solicitacao_id = ? AND status = 'Rascunho'");
    $stmt_edit->execute([$edit_id, $id_projeto]);
    $relatorio_edit = $stmt_edit->fetch(PDO::FETCH_ASSOC);
}

// Define os valores iniciais do formulário (Vazio ou Preenchido se estiver editando)
$form_mes = $relatorio_edit ? $relatorio_edit['mes_referencia'] : date('n');
$form_ano = $relatorio_edit ? $relatorio_edit['ano_referencia'] : date('Y');
$form_acoes = $relatorio_edit ? $relatorio_edit['acoes_realizadas'] : '';

// PROCESSAMENTO DO FORMULÁRIO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mes = (int)$_POST['mes'];
    $ano = (int)$_POST['ano'];
    $acoes = trim($_POST['acoes_realizadas']);
    
    // Solução à prova de falhas para os botões
    $novo_status = isset($_POST['btn_publicar']) ? 'Publicado' : 'Rascunho';

    // Verifica se já existe um relatório para este mês/ano
    $sql_check = "SELECT id, status FROM relatorios_hae WHERE solicitacao_id = ? AND mes_referencia = ? AND ano_referencia = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$id_projeto, $mes, $ano]);
    $relatorio_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($relatorio_existente && $relatorio_existente['status'] == 'Publicado') {
        $erro = "O relatório de " . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/$ano já foi publicado e não pode mais ser alterado.";
    } else {
        try {
            if ($relatorio_existente) {
                // Atualiza o que já existe e renova a data de atualização
                $sql = "UPDATE relatorios_hae SET acoes_realizadas = ?, status = ?, data_envio = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$acoes, $novo_status, $relatorio_existente['id']]);
            } else {
                // Insere um novo
                $sql = "INSERT INTO relatorios_hae (solicitacao_id, mes_referencia, ano_referencia, acoes_realizadas, status) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_projeto, $mes, $ano, $acoes, $novo_status]);
            }
            // Redireciona para limpar o POST e mostrar a mensagem
            header("Location: enviar_relatorio.php?id_projeto=$id_projeto&status_msg=sucesso&tipo=$novo_status");
            exit;
        } catch (PDOException $e) {
            $erro = "Erro ao salvar relatório: " . $e->getMessage();
        }
    }
}

// Busca o histórico de relatórios
$sql_hist = "SELECT * FROM relatorios_hae WHERE solicitacao_id = ? ORDER BY ano_referencia DESC, mes_referencia DESC";
$stmt = $pdo->prepare($sql_hist);
$stmt->execute([$id_projeto]);
$historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

$meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lançar Relatório - HAE Fatec</title>
    
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header { background: #fff; padding: 25px; border-radius: 10px; margin-bottom: 25px; border-left: 5px solid var(--fatec-red); box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .page-header h2 { font-size: 20px; color: #333; margin-bottom: 8px; }
        .page-header p { color: #666; font-size: 14px; }

        .form-card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); margin-bottom: 30px; border-top: 4px solid #ccc; }
        .form-card.edit-mode { border-top-color: #f39c12; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #444; }
        select, textarea { width: 100%; padding: 12px; border: 1.5px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; transition: 0.3s; }
        select:focus, textarea:focus { border-color: var(--fatec-red); }
        
        .aviso-prazo { background: #fff3cd; color: #856404; padding: 12px 15px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; border-left: 4px solid #ffeeba; display: flex; align-items: center; gap: 10px;}
        .aviso-edicao { background: #e1f5fe; color: #0277bd; padding: 12px 15px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; border-left: 4px solid #03a9f4; font-weight: bold; display: flex; justify-content: space-between; align-items: center;}
        
        .btn-group { display: flex; gap: 15px; justify-content: flex-end; }
        .btn { padding: 12px 25px; border-radius: 6px; font-weight: bold; font-size: 14px; cursor: pointer; transition: 0.3s; border: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-rascunho { background: #f8f9fa; color: #333; border: 1px solid #ddd; }
        .btn-rascunho:hover { background: #e9ecef; }
        .btn-publicar { background: #2ecc71; color: #fff; }
        .btn-publicar:hover { background: #27ae60; }
        .btn-cancelar { background: #fff; color: #e74c3c; border: 1px solid #e74c3c; text-decoration: none; padding: 8px 15px; border-radius: 4px; font-size: 12px; }

        /* Tabela de Histórico Profissional */
        .historico-container { border: 1px solid #eee; border-radius: 8px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background-color: #f8f9fa; color: #555; text-transform: uppercase; font-size: 11px; font-weight: 700; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #fcfcfc; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block;}
        .bg-rascunho { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .bg-publicado { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }

        .btn-action-edit { background: #3498db; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; }
        .btn-action-edit:hover { background: #2980b9; }
        .texto-bloqueado { color: #888; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 5px; }
    </style>
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
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Relatório Mensal HAE</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <?php if($sucesso) echo "<div class='alert-success'>✅ $sucesso</div>"; ?>
        <?php if($erro) echo "<div class='alert-success' style='background:#fee2e2; color:#b91c1c; border-color:#b91c1c;'>❌ $erro</div>"; ?>

        <div class="page-header">
            <h2>Projeto: <?php echo htmlspecialchars($projeto['titulo_projeto']); ?></h2>
            <p><i class="fa-solid fa-clock"></i> Horas HAE Aprovadas: <strong><?php echo $projeto['quantidade_horas']; ?>h mensais</strong></p>
        </div>

        <div class="aviso-prazo">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px;"></i> 
            <span><strong>Atenção:</strong> O relatório deve ser enviado impreterivelmente até o dia 10 do mês subsequente. Após ser "Publicado", ele não poderá mais ser editado.</span>
        </div>

        <!-- FORMULÁRIO DE ENVIO / EDIÇÃO -->
        <div class="form-card <?php echo $relatorio_edit ? 'edit-mode' : ''; ?>">
            
            <?php if ($relatorio_edit): ?>
                <div class="aviso-edicao">
                    <span><i class="fa-solid fa-pen-to-square"></i> Você está editando o rascunho de <?php echo $meses[$form_mes] . '/' . $form_ano; ?></span>
                    <a href="enviar_relatorio.php?id_projeto=<?php echo $id_projeto; ?>" class="btn-cancelar">Cancelar Edição</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="enviar_relatorio.php?id_projeto=<?php echo $id_projeto; ?>">
                <div class="grid-2">
                    <div>
                        <label>Mês de Referência</label>
                        <select name="mes" required <?php echo $relatorio_edit ? 'readonly style="background:#e9ecef; pointer-events:none;"' : ''; ?>>
                            <?php foreach($meses as $num => $nome): ?>
                                <option value="<?php echo $num; ?>" <?php echo ($form_mes == $num) ? 'selected' : ''; ?>><?php echo $nome; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Ano de Referência</label>
                        <select name="ano" required <?php echo $relatorio_edit ? 'readonly style="background:#e9ecef; pointer-events:none;"' : ''; ?>>
                            <option value="2024" <?php echo ($form_ano == 2024) ? 'selected' : ''; ?>>2024</option>
                            <option value="2025" <?php echo ($form_ano == 2025) ? 'selected' : ''; ?>>2025</option>
                            <option value="2026" <?php echo ($form_ano == 2026) ? 'selected' : ''; ?>>2026</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <label>Ações Realizadas no Mês</label>
                    <textarea name="acoes_realizadas" rows="6" required placeholder="Descreva detalhadamente as atividades e trabalhos que foram desenvolvidos neste mês..."><?php echo htmlspecialchars($form_acoes); ?></textarea>
                </div>

                <div class="btn-group">
                    <!-- Usando names separados para garantir a lógica no PHP -->
                    <button type="submit" name="btn_rascunho" class="btn btn-rascunho"><i class="fa-regular fa-floppy-disk"></i> Salvar Rascunho</button>
                    <button type="submit" name="btn_publicar" class="btn btn-publicar" onclick="return confirm('Após publicar, não será possível alterar este relatório. Deseja continuar?');"><i class="fa-solid fa-paper-plane"></i> Publicar Relatório</button>
                </div>
            </form>
        </div>

        <!-- HISTÓRICO DE RELATÓRIOS -->
        <h3 style="margin-bottom: 15px; font-size: 18px; color: #333;">Histórico de Envios deste Projeto</h3>
        
        <div class="historico-container" style="background: #fff;">
            <table>
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Data da Última Atualização</th>
                        <th>Status</th>
                        <th style="text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($historico) > 0): ?>
                        <?php foreach ($historico as $rel): ?>
                            <tr>
                                <td><strong><?php echo $meses[$rel['mes_referencia']] . ' / ' . $rel['ano_referencia']; ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($rel['data_envio'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $rel['status'] == 'Publicado' ? 'bg-publicado' : 'bg-rascunho'; ?>">
                                        <?php echo $rel['status']; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($rel['status'] == 'Publicado'): ?>
                                        <span class="texto-bloqueado"><i class="fa-solid fa-lock"></i> Entregue e Bloqueado</span>
                                    <?php else: ?>
                                        <a href="enviar_relatorio.php?id_projeto=<?php echo $id_projeto; ?>&edit_id=<?php echo $rel['id']; ?>" class="btn-action-edit">
                                            <i class="fa-solid fa-pen-to-square"></i> Editar Rascunho
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 40px; color: #888;">Nenhum relatório lançado ainda para este projeto.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <script src="assets/js/painel.js"></script>
</body>
</html>