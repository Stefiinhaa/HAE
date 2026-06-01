<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Coordenador ou Diretor acessam
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_funcao'], ['Coordenador', 'Diretor'])) {
    header("Location: painel.php");
    exit;
}

$mensagem = "";

// Ação de Aprovar ou Rejeitar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    $solicitacao_id = $_POST['solicitacao_id'];
    $novo_status = $_POST['acao'] == 'aprovar' ? 'Aprovado' : 'Rejeitado';
    $horas_aprovadas = $_POST['horas_aprovadas'] ?? 0;
    $parecer = trim($_POST['parecer']);

    try {
        // Agora salvamos também o parecer_direcao no banco!
        $sql = "UPDATE solicitacoes_hae SET status_aprovacao = ?, quantidade_horas = ?, parecer_direcao = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$novo_status, $horas_aprovadas, $parecer, $solicitacao_id]);
        
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

// LÓGICA DE FILTROS AVANÇADOS
$filtro_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_status = isset($_GET['status_filtro']) ? trim($_GET['status_filtro']) : 'Pendente'; 
$filtro_semestre = isset($_GET['semestre']) ? trim($_GET['semestre']) : '';

$where = ["1=1"];
$params = [];

if ($filtro_status !== 'Todos') {
    $where[] = "s.status_aprovacao = ?";
    $params[] = $filtro_status;
}
if (!empty($filtro_semestre)) {
    $where[] = "s.semestre = ?";
    $params[] = $filtro_semestre;
}
if (!empty($filtro_busca)) {
    $where[] = "(u.nome LIKE ? OR s.titulo_projeto LIKE ? OR s.categoria LIKE ?)";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
}

if ($visualizando_id) {
    $sql = "SELECT s.*, u.nome AS professor_nome FROM solicitacoes_hae s 
            JOIN usuarios u ON s.professor_id = u.id WHERE s.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$visualizando_id]);
    $detalhes = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $sql = "SELECT s.*, u.nome AS professor_nome FROM solicitacoes_hae s 
            JOIN usuarios u ON s.professor_id = u.id 
            WHERE " . implode(" AND ", $where) . " 
            ORDER BY s.data_criacao ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .card-table { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow: hidden; border-top: 4px solid var(--fatec-red); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #fcfcfc; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-pendente { background: #fff3cd; color: #856404; }
        .badge-aprovado { background: #d1e7dd; color: #0f5132; }
        .badge-rejeitado { background: #f8d7da; color: #842029; }

        .btn-action { background: #1e1e2d; color: #fff; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 12px; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px;}
        .btn-action:hover { background: var(--fatec-red); }
        .btn-voltar { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: #666; text-decoration: none; font-weight: bold; font-size: 14px; }
        .btn-voltar:hover { color: var(--fatec-red); }

        .filter-bar { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; border-left: 4px solid #3498db; }
        .filter-group { display: flex; flex-direction: column; flex: 1; min-width: 200px; }
        .filter-group label { font-size: 12px; font-weight: bold; color: #555; margin-bottom: 5px; text-transform: uppercase; }
        .filter-group input, .filter-group select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; outline: none; font-size: 14px; transition: 0.3s; }
        .filter-group input:focus, .filter-group select:focus { border-color: var(--fatec-red); }
        .btn-filtrar { background: #3498db; color: white; border: none; padding: 11px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;}
        .btn-filtrar:hover { background: #2980b9; }
        .btn-limpar { background: #f1f3f5; color: #444; border: 1px solid #ddd; padding: 10px 15px; border-radius: 5px; font-weight: bold; cursor: pointer; text-decoration: none; transition: 0.3s; }
        .btn-limpar:hover { background: #e9ecef; }

        .split-view { display: flex; gap: 25px; align-items: flex-start; }
        .doc-preview { flex: 6.5; height: 85vh; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 2px solid #ddd; background: #525659; }
        .doc-preview iframe { width: 100%; height: 100%; border: none; }
        .form-parecer { flex: 3.5; background: #fff; padding: 30px; border-radius: 10px; border-top: 4px solid var(--fatec-red); box-shadow: 0 4px 10px rgba(0,0,0,0.05); position: sticky; top: 20px; }
        .form-parecer label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 14px; color: #444; }
        .form-parecer input, .form-parecer textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .botoes-acao { display: flex; flex-direction: column; gap: 10px; }
        .btn-aprovar { background: #2ecc71; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; transition: 0.3s;}
        .btn-aprovar:hover { background: #27ae60; }
        .btn-rejeitar { background: #e74c3c; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; transition: 0.3s;}
        .btn-rejeitar:hover { background: #c0392b; }

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
                <h2>HAE <span>FATEC</span></h2>
            </a>
            <button class="collapse-btn" id="collapse-btn"><i class="fa-solid fa-bars"></i></button>
        </div>
        
        <nav class="menu">
            <div class="menu-title">Navegação</div>
            <ul>
                <li><a href="painel.php" class="<?php echo ($pagina_atual == 'painel.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></a></li>
                <li><a href="analisar_solicitacoes.php" class="<?php echo ($pagina_atual == 'analisar_solicitacoes.php') ? 'active' : ''; ?>"><i class="fa-solid fa-clipboard-check"></i> <span>Analisar Solicitações</span></a></li>
                <li><a href="acompanhar_relatorios.php" class="<?php echo ($pagina_atual == 'acompanhar_relatorios.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> <span>Acompanhar Relatórios</span></a></li>
                <li><a href="cadastrar_professor.php" class="<?php echo ($pagina_atual == 'cadastrar_professor.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-plus"></i> <span>Cadastrar Professor</span></a></li>
                <li><a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-gear"></i> <span>Meu Perfil</span></a></li>
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span>Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1><?php echo $visualizando_id ? "Análise de Projeto" : "Gestão de Solicitações"; ?></h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo $_SESSION['usuario_nome']; ?></strong></div>
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
                    <h3 style="margin-bottom: 20px; color: var(--fatec-red); font-size: 18px;">Parecer da Direção</h3>
                    <a href="documento_hae.php?id=<?php echo $visualizando_id; ?>" target="_blank" class="btn-ver-pdf-mobile">📄 Abrir Documento em Tela Cheia</a>
                    <p style="font-size: 13px; color: #666; margin-bottom: 20px;">
                        Verifique o documento ao lado. Caso esteja tudo correto, defina as horas aprovadas e emita seu parecer.
                    </p>
                    <form method="POST" action="analisar_solicitacoes.php?id=<?php echo $visualizando_id; ?>" id="formAnalise">
                        <input type="hidden" name="solicitacao_id" value="<?php echo $visualizando_id; ?>">
                        
                        <label>Horas HAE Aprovadas</label>
                        <input type="number" name="horas_aprovadas" value="<?php echo $detalhes['quantidade_horas']; ?>" required min="0">
                        
                        <label>Observações / Parecer Final</label>
                        <textarea name="parecer" id="campo_parecer" rows="5" placeholder="Obrigatório em caso de rejeição. Descreva o motivo ou ajustes necessários..."></textarea>
                        
                        <div class="botoes-acao">
                            <!-- Inserimos a validação JS nos botões -->
                            <button type="submit" name="acao" value="aprovar" class="btn-aprovar" onclick="return validarParecer('aprovar');">✓ Aprovar Projeto HAE</button>
                            <button type="submit" name="acao" value="rejeitar" class="btn-rejeitar" onclick="return validarParecer('rejeitar');">✕ Rejeitar Projeto</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <form method="GET" class="filter-bar">
                <div class="filter-group" style="flex: 2;">
                    <label>Buscar (Professor, Título, Categoria)</label>
                    <input type="text" name="busca" placeholder="Digite uma palavra-chave..." value="<?php echo htmlspecialchars($filtro_busca); ?>">
                </div>
                
                <div class="filter-group">
                    <label>Semestre / Ano</label>
                    <input type="text" name="semestre" placeholder="Ex: 1/2026" value="<?php echo htmlspecialchars($filtro_semestre); ?>">
                </div>

                <div class="filter-group">
                    <label>Status</label>
                    <select name="status_filtro">
                        <option value="Todos" <?php echo $filtro_status == 'Todos' ? 'selected' : ''; ?>>Todos os Status</option>
                        <option value="Pendente" <?php echo $filtro_status == 'Pendente' ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="Aprovado" <?php echo $filtro_status == 'Aprovado' ? 'selected' : ''; ?>>Aprovados</option>
                        <option value="Rejeitado" <?php echo $filtro_status == 'Rejeitado' ? 'selected' : ''; ?>>Rejeitados</option>
                    </select>
                </div>

                <button type="submit" class="btn-filtrar"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
                <a href="analisar_solicitacoes.php" class="btn-limpar">Limpar</a>
            </form>

            <div class="card-table">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Professor(a)</th>
                                <th>Projeto</th>
                                <th>Semestre</th>
                                <th>HAE Req.</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($solicitacoes) > 0): ?>
                                <?php foreach ($solicitacoes as $row): ?>
                                    <?php 
                                        $badge_class = 'badge-pendente';
                                        if($row['status_aprovacao'] == 'Aprovado') $badge_class = 'badge-aprovado';
                                        if($row['status_aprovacao'] == 'Rejeitado') $badge_class = 'badge-rejeitado';
                                    ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($row['data_criacao'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['professor_nome']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['titulo_projeto']); ?></td>
                                        <td><?php echo htmlspecialchars($row['semestre']); ?></td>
                                        <td><?php echo $row['quantidade_horas']; ?>h</td>
                                        <td><span class="badge <?php echo $badge_class; ?>"><?php echo $row['status_aprovacao']; ?></span></td>
                                        <td>
                                            <a href="analisar_solicitacoes.php?id=<?php echo $row['id']; ?>" class="btn-action">
                                                <i class="fa-solid fa-folder-open"></i> Analisar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align:center; padding: 40px; color: #888;">Nenhuma solicitação encontrada com os filtros selecionados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script src="assets/js/painel.js"></script>
    <script>
        function validarParecer(acao) {
            var campoParecer = document.getElementById('campo_parecer').value.trim();
            
            // Se estiver rejeitando, o motivo é obrigatório
            if (acao === 'rejeitar' && campoParecer === '') {
                alert('Atenção: É obrigatório informar o motivo da rejeição no campo de Parecer!');
                document.getElementById('campo_parecer').focus();
                return false; // Bloqueia o envio do form
            }
            
            if (acao === 'rejeitar') {
                return confirm('Tem certeza que deseja REJEITAR este projeto HAE?');
            } else {
                return confirm('Confirmar a APROVAÇÃO deste projeto com a carga horária informada?');
            }
        }
    </script>
</body>
</html>