<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Professor acessa
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] != 'Professor') {
    header("Location: painel.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$erro = "";
$sucesso = "";

$meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];

// Busca apenas os projetos APROVADOS deste professor (PUXANDO O SEMESTRE)
$stmt_proj = $pdo->prepare("SELECT id, titulo_projeto, semestre FROM solicitacoes_hae WHERE professor_id = ? AND status_aprovacao = 'Aprovado'");
$stmt_proj->execute([$usuario_id]);
$projetos_aprovados = $stmt_proj->fetchAll(PDO::FETCH_ASSOC);

// ==============================================================================
// 1. LÓGICA DE CARREGAMENTO DO RASCUNHO (SE EXISTIR)
// ==============================================================================
$relatorio_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rascunho = null;

if ($relatorio_id > 0) {
    // Busca o rascunho garantindo que pertence ao professor logado
    $stmt_rasc = $pdo->prepare("SELECT r.* FROM relatorios_hae r JOIN solicitacoes_hae s ON r.solicitacao_id = s.id WHERE r.id = ? AND s.professor_id = ? AND r.status = 'Rascunho'");
    $stmt_rasc->execute([$relatorio_id, $usuario_id]);
    $rascunho = $stmt_rasc->fetch(PDO::FETCH_ASSOC);
    
    if (!$rascunho) {
        $erro = "Rascunho não encontrado ou já foi publicado.";
    }
}

// Preenche as variáveis com os dados do rascunho (ou deixa vazio se for um relatório novo)
$r_solicitacao_id = $rascunho['solicitacao_id'] ?? '';
$r_mes = $rascunho['mes_referencia'] ?? date('n');
$r_ano = $rascunho['ano_referencia'] ?? date('Y');

// CORREÇÃO: Puxando da coluna exata do banco de dados (acoes_realizadas)
$r_acoes = $rascunho['acoes_realizadas'] ?? '';

// ==============================================================================
// 2. PROCESSAMENTO DO ENVIO (NOVO OU ATUALIZAÇÃO)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    $solicitacao_id = $_POST['solicitacao_id'];
    $mes_referencia = $_POST['mes_referencia'];
    $ano_referencia = $_POST['ano_referencia'];
    
    // CORREÇÃO: Pegando o valor do textarea correto
    $acoes_realizadas = trim($_POST['acoes_realizadas']);
    
    $id_edicao = !empty($_POST['relatorio_id']) ? (int)$_POST['relatorio_id'] : 0;
    
    // Descobre qual botão o professor clicou
    $status_final = ($_POST['acao'] == 'publicar') ? 'Publicado' : 'Rascunho';

    try {
        if ($id_edicao > 0) {
            // ATUALIZA O RASCUNHO EXISTENTE
            // CORREÇÃO: Usando a coluna acoes_realizadas
            $sql = "UPDATE relatorios_hae SET solicitacao_id = ?, mes_referencia = ?, ano_referencia = ?, acoes_realizadas = ?, status = ?, data_envio = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$solicitacao_id, $mes_referencia, $ano_referencia, $acoes_realizadas, $status_final, $id_edicao]);
            
            if ($status_final == 'Publicado') {
                $sucesso = "Relatório definitivo enviado com sucesso para a coordenação!";
            } else {
                $sucesso = "Rascunho atualizado e salvo com sucesso!";
            }
        } else {
            // CRIA UM NOVO RELATÓRIO
            // CORREÇÃO: Usando a coluna acoes_realizadas
            $sql = "INSERT INTO relatorios_hae (solicitacao_id, mes_referencia, ano_referencia, acoes_realizadas, status, data_envio) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$solicitacao_id, $mes_referencia, $ano_referencia, $acoes_realizadas, $status_final]);
            
            if ($status_final == 'Publicado') {
                $sucesso = "Relatório mensal enviado com sucesso!";
            } else {
                $sucesso = "Relatório salvo na sua pasta de Rascunhos!";
            }
        }
        
        // Limpa a tela após o sucesso para não reenviar os dados sem querer
        if ($status_final == 'Publicado') {
            $rascunho = null; $r_acoes = ''; $r_solicitacao_id = ''; $relatorio_id = 0;
        }
        
    } catch (PDOException $e) {
        $erro = "Erro ao salvar o relatório: " . $e->getMessage();
    }
}

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Relatório HAE - Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border-top: 4px solid var(--fatec-red); }
        .form-section { margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .form-section h3 { color: var(--fatec-red); margin-bottom: 15px; font-size: 16px; border-left: 3px solid var(--fatec-red); padding-left: 10px; }
        
        .grid-3 { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; }
        .full-width { grid-column: 1 / -1; margin-top: 15px; }
        
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #444; }
        select, textarea { width: 100%; padding: 12px; border: 1.5px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; transition: 0.3s; }
        select:focus, textarea:focus { border-color: var(--fatec-red); }
        textarea { resize: vertical; min-height: 150px; }
        
        .botoes-container { display: flex; justify-content: flex-end; gap: 15px; margin-top: 20px; }
        .btn { padding: 12px 25px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-rascunho { background: #f1f3f5; color: #444; border: 1px solid #ddd; }
        .btn-rascunho:hover { background: #e2e6ea; border-color: #ccc; }
        .btn-enviar { background: var(--fatec-red); color: white; }
        .btn-enviar:hover { background: #8a0000; }
        
        .aviso-rascunho { background: #fff3cd; color: #856404; padding: 15px; border-radius: 6px; border-left: 4px solid #f39c12; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .btn-voltar { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: #666; text-decoration: none; font-weight: bold; font-size: 14px; }
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
                <li><a href="nova_solicitacao.php" class="<?php echo ($pagina_atual == 'nova_solicitacao.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-circle-plus"></i> <span class="menu-text">Nova Solicitação</span></a></li>
                <li><a href="meus_projetos.php" class="<?php echo ($pagina_atual == 'meus_projetos.php') ? 'active' : ''; ?>"><i class="fa-solid fa-folder-open"></i> <span class="menu-text">Meus Projetos</span></a></li>
                <li><a href="enviar_relatorio.php" class="<?php echo ($pagina_atual == 'enviar_relatorio.php') ? 'active' : ''; ?>"><i class="fa-solid fa-calendar-check"></i> <span class="menu-text">Enviar Relatório</span></a></li>
                <li><a href="meus_rascunhos.php" class="<?php echo ($pagina_atual == 'meus_rascunhos.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-pen"></i> <span class="menu-text">Meus Rascunhos</span></a></li>
                <li><a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span class="menu-text">Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Envio de Relatório Mensal</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <?php if($rascunho): ?>
            <a href="meus_rascunhos.php" class="btn-voltar"><i class="fa-solid fa-arrow-left"></i> Voltar para Rascunhos</a>
        <?php endif; ?>

        <?php if($sucesso): ?>
            <div class='alert-success'>✅ <?php echo $sucesso; ?></div>
        <?php endif; ?>
        
        <?php if($erro): ?>
            <div class='alert-success' style='background:#fee2e2; color:#b91c1c; border-color:#b91c1c;'>❌ <?php echo $erro; ?></div>
        <?php endif; ?>

        <?php if($rascunho && !$sucesso): ?>
            <div class="aviso-rascunho">
                <i class="fa-solid fa-pen-ruler" style="font-size: 18px;"></i> 
                <div>
                    <strong>Modo de Edição:</strong> Você está continuando o preenchimento de um rascunho salvo anteriormente.
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($projetos_aprovados) == 0): ?>
            <div style="background: #fff; padding: 40px; text-align: center; border-radius: 10px; color: #888;">
                <i class="fa-solid fa-folder-closed" style="font-size: 40px; margin-bottom: 15px; color: #ddd;"></i>
                <p>Você não possui nenhum projeto aprovado no momento.<br>Apenas projetos aprovados exigem envio de relatório mensal.</p>
            </div>
        <?php else: ?>
            <div class="form-card">
                <form method="POST">
                    <input type="hidden" name="relatorio_id" value="<?php echo $relatorio_id; ?>">
                    
                    <div class="form-section">
                        <h3>Informações Gerais</h3>
                        <div class="grid-3">
                            <div>
                                <label>Projeto Vinculado</label>
                                <select name="solicitacao_id" required>
                                    <option value="">-- Selecione o Projeto --</option>
                                    <?php foreach ($projetos_aprovados as $proj): ?>
                                        <option value="<?php echo $proj['id']; ?>" <?php echo ($r_solicitacao_id == $proj['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($proj['titulo_projeto'] . ' (' . $proj['semestre'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label>Mês de Referência</label>
                                <select name="mes_referencia" required>
                                    <?php foreach($meses as $num => $nome): ?>
                                        <option value="<?php echo $num; ?>" <?php echo ($r_mes == $num) ? 'selected' : ''; ?>><?php echo $nome; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label>Ano</label>
                                <select name="ano_referencia" required>
                                    <?php 
                                    $ano_atual_loop = date('Y');
                                    for ($i = $ano_atual_loop - 1; $i <= $ano_atual_loop + 1; $i++): 
                                    ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($r_ano == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                        <h3>Detalhamento do Relatório</h3>
                        <div class="full-width">
                            <label>Ações Realizadas / Resultados Alcançados</label>
                            <textarea name="acoes_realizadas" required placeholder="Descreva de forma clara e objetiva todas as atividades executadas referentes ao projeto durante o mês selecionado..."><?php echo htmlspecialchars($r_acoes); ?></textarea>
                        </div>
                    </div>

                    <div class="botoes-container">
                        <button type="submit" name="acao" value="rascunho" class="btn btn-rascunho" formnovalidate>
                            <i class="fa-regular fa-floppy-disk"></i> Salvar como Rascunho
                        </button>
                        
                        <button type="submit" name="acao" value="publicar" class="btn btn-enviar" onclick="return confirm('Após enviar definitivamente, este relatório será encaminhado para a coordenação. Deseja prosseguir?')">
                            <i class="fa-solid fa-paper-plane"></i> Enviar Relatório Definitivo
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </main>

    <script src="assets/js/painel.js"></script>
</body>
</html>