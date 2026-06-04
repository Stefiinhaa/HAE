<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Professor acessa esta tela
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] !== 'Professor') {
    header("Location: painel.php");
    exit;
}

$professor_id = $_SESSION['usuario_id'];
$id_projeto = isset($_GET['id_projeto']) ? (int)$_GET['id_projeto'] : 0;

$erro = "";
$projeto = null;
$relatorio_edit = null;
$form_mes = date('n');
$form_ano = date('Y');
$form_acoes = '';
$projetos_aprovados = [];

$meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];

if ($id_projeto > 0) {
    // Verifica se o projeto existe e está aprovado
    $sql_proj = "SELECT * FROM solicitacoes_hae WHERE id = ? AND professor_id = ? AND status_aprovacao = 'Aprovado'";
    $stmt = $pdo->prepare($sql_proj);
    $stmt->execute([$id_projeto, $professor_id]);
    $projeto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$projeto) {
        die("Projeto inválido, não encontrado ou ainda não aprovado pela direção.");
    }

    // LÓGICA DE EDIÇÃO
    $edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;

    if ($edit_id) {
        $stmt_edit = $pdo->prepare("SELECT * FROM relatorios_hae WHERE id = ? AND solicitacao_id = ? AND status = 'Rascunho'");
        $stmt_edit->execute([$edit_id, $id_projeto]);
        $relatorio_edit = $stmt_edit->fetch(PDO::FETCH_ASSOC);
        
        if ($relatorio_edit) {
            $form_mes = $relatorio_edit['mes_referencia'];
            $form_ano = $relatorio_edit['ano_referencia'];
            $form_acoes = $relatorio_edit['acoes_realizadas'];
        }
    }

    // PROCESSAMENTO DO FORMULÁRIO (NOVA LÓGICA INTELIGENTE)
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $mes = (int)$_POST['mes'];
        $ano = (int)$_POST['ano'];
        $acoes = trim($_POST['acoes_realizadas']);
        $novo_status = isset($_POST['btn_publicar']) ? 'Publicado' : 'Rascunho';
        $relatorio_id = isset($_POST['relatorio_id']) ? (int)$_POST['relatorio_id'] : 0;

        // Verifica se já existe um relatório para o mês/ano selecionado (ignorando o próprio relatório sendo editado)
        $sql_check = "SELECT id, status FROM relatorios_hae WHERE solicitacao_id = ? AND mes_referencia = ? AND ano_referencia = ? AND id != ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id_projeto, $mes, $ano, $relatorio_id]);
        $conflito = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($conflito) {
            $erro = "Já existe um relatório registrado para " . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/$ano neste projeto. Escolha outro mês.";
        } else {
            try {
                if ($relatorio_id > 0) {
                    // É uma edição: Atualiza TUDO, incluindo o mês e o ano!
                    $sql = "UPDATE relatorios_hae SET mes_referencia = ?, ano_referencia = ?, acoes_realizadas = ?, status = ?, data_envio = CURRENT_TIMESTAMP WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$mes, $ano, $acoes, $novo_status, $relatorio_id]);
                } else {
                    // É um relatório novo
                    $sql = "INSERT INTO relatorios_hae (solicitacao_id, mes_referencia, ano_referencia, acoes_realizadas, status) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$id_projeto, $mes, $ano, $acoes, $novo_status]);
                }
                
                header("Location: meus_relatorios.php?status_msg=sucesso&tipo=$novo_status");
                exit;
            } catch (PDOException $e) {
                $erro = "Erro ao salvar relatório: " . $e->getMessage();
            }
        }
    }

} else {
    // TELA 1: NENHUM PROJETO SELECIONADO (ACESSO PELO MENU LATERAL)
    $stmt_projetos = $pdo->prepare("SELECT id, titulo_projeto, semestre FROM solicitacoes_hae WHERE professor_id = ? AND status_aprovacao = 'Aprovado' ORDER BY id DESC");
    $stmt_projetos->execute([$professor_id]);
    $projetos_aprovados = $stmt_projetos->fetchAll(PDO::FETCH_ASSOC);
}

$pagina_atual = basename($_SERVER['PHP_SELF']);
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
        .btn-publicar { background: #2ecc71; color: #fff; text-decoration: none; }
        .btn-publicar:hover { background: #27ae60; }
        .btn-cancelar { background: #fff; color: #e74c3c; border: 1px solid #e74c3c; text-decoration: none; padding: 8px 15px; border-radius: 4px; font-size: 12px; }
        
        .seletor-container { max-width: 600px; margin: 0 auto; text-align: center; }
        .seletor-container h3 { margin-bottom: 20px; color: #333; }
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
                <h1>Lançamento de Relatórios</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <?php if($erro) echo "<div class='alert-success' style='background:#fee2e2; color:#b91c1c; border-color:#b91c1c;'>❌ $erro</div>"; ?>

        <?php if ($id_projeto == 0): ?>
            <!-- TELA 1: O PROFESSOR ESCOLHE QUAL PROJETO VAI RELATAR -->
            <div class="form-card seletor-container" style="border-top-color: var(--fatec-red);">
                <h3><i class="fa-solid fa-list-check" style="color: var(--fatec-red);"></i> Selecione um Projeto HAE</h3>
                <p style="color: #666; font-size: 14px; margin-bottom: 25px;">Para iniciar ou continuar a edição de um relatório, selecione abaixo o projeto desejado. (Apenas projetos aprovados são listados).</p>
                
                <?php if (count($projetos_aprovados) > 0): ?>
                    <form method="GET" action="enviar_relatorio.php">
                        <div style="margin-bottom: 25px; text-align: left;">
                            <label>Projetos Aprovados</label>
                            <select name="id_projeto" required style="padding: 15px; font-size: 15px;">
                                <option value="">-- Clique para selecionar o projeto --</option>
                                <?php foreach ($projetos_aprovados as $p): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo htmlspecialchars($p['semestre'] . ' - ' . $p['titulo_projeto']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-publicar" style="width: 100%; justify-content: center; padding: 15px;"><i class="fa-solid fa-arrow-right"></i> Continuar para Lançamento</button>
                    </form>
                <?php else: ?>
                    <div class="aviso-prazo" style="background: #fee2e2; color: #b91c1c; border-left-color: #b91c1c; justify-content: center;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px;"></i>
                        Você ainda não possui projetos com status "Aprovado" para enviar relatórios.
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- TELA 2: FORMULÁRIO DO RELATÓRIO -->
            <a href="enviar_relatorio.php" class="btn-cancelar" style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: #666; border: none; background: transparent; padding: 0; font-size: 14px; font-weight: bold;">
                <i class="fa-solid fa-arrow-left"></i> Voltar para a seleção de projetos
            </a>

            <div class="page-header">
                <h2>Projeto: <?php echo htmlspecialchars($projeto['titulo_projeto']); ?></h2>
                <p><i class="fa-solid fa-clock"></i> Horas HAE Aprovadas: <strong><?php echo $projeto['quantidade_horas']; ?>h mensais</strong></p>
            </div>

            <div class="aviso-prazo">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px;"></i> 
                <span><strong>Atenção:</strong> O relatório deve ser enviado impreterivelmente até o dia 10 do mês subsequente. Após ser "Publicado", ele não poderá mais ser editado.</span>
            </div>

            <div class="form-card <?php echo $relatorio_edit ? 'edit-mode' : ''; ?>">
                <?php if ($relatorio_edit): ?>
                    <div class="aviso-edicao">
                        <span><i class="fa-solid fa-pen-to-square"></i> Você está editando o rascunho de <?php echo $meses[$form_mes] . '/' . $form_ano; ?></span>
                        <a href="meus_relatorios.php" class="btn-cancelar">Cancelar Edição</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="enviar_relatorio.php?id_projeto=<?php echo $id_projeto; ?>">
                    <!-- Campo oculto para o sistema saber qual rascunho você está editando -->
                    <input type="hidden" name="relatorio_id" value="<?php echo $relatorio_edit ? $relatorio_edit['id'] : 0; ?>">

                    <div class="grid-2">
                        <div>
                            <!-- Os atributos de bloqueio (readonly e pointer-events) foram removidos daqui! -->
                            <label>Mês de Referência</label>
                            <select name="mes" required>
                                <?php foreach($meses as $num => $nome): ?>
                                    <option value="<?php echo $num; ?>" <?php echo ($form_mes == $num) ? 'selected' : ''; ?>><?php echo $nome; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <!-- Os atributos de bloqueio (readonly e pointer-events) foram removidos daqui também! -->
                            <label>Ano de Referência</label>
                            <select name="ano" required>
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
                        <button type="submit" name="btn_rascunho" class="btn btn-rascunho"><i class="fa-regular fa-floppy-disk"></i> Salvar Rascunho</button>
                        <button type="submit" name="btn_publicar" class="btn btn-publicar" onclick="return confirm('Após publicar, não será possível alterar este relatório. Deseja continuar?');"><i class="fa-solid fa-paper-plane"></i> Publicar Relatório</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </main>

    <script src="assets/js/painel.js"></script>
</body>
</html>