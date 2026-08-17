<?php
session_start();
require 'config/conexao.php';

// Segurança Rigorosa: Apenas DIRETOR tem acesso a este relatório consolidado
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] !== 'Diretor') {
    header("Location: painel.php");
    exit;
}

$pagina_atual = basename($_SERVER['PHP_SELF']);

// Definição do semestre atual (Inteligência Temporal)
$mes_atual_calc = (int)date('m');
$ano_atual_calc = (int)date('Y');
$semestre_padrao = ($mes_atual_calc <= 6) ? "1/$ano_atual_calc" : "2/$ano_atual_calc";

$filtro_semestre = isset($_GET['semestre']) ? trim($_GET['semestre']) : $semestre_padrao;
$filtro_status = isset($_GET['status']) ? trim($_GET['status']) : 'Todos'; 

// Busca os semestres disponíveis no banco
$stmt_sem = $pdo->query("SELECT DISTINCT semestre FROM solicitacoes_hae ORDER BY semestre DESC");
$semestres_disponiveis = $stmt_sem->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($semestre_padrao, $semestres_disponiveis)) {
    array_unshift($semestres_disponiveis, $semestre_padrao);
}

// Monta a consulta SQL
$where = ["1=1"];
$params = [];

if ($filtro_semestre != 'Todos') {
    $where[] = "s.semestre = ?";
    $params[] = $filtro_semestre;
}

if ($filtro_status != 'Todos') {
    $where[] = "s.status_aprovacao = ?";
    $params[] = $filtro_status;
}

$sql = "SELECT s.id, s.titulo_projeto, s.quantidade_horas, s.status_aprovacao, u.nome AS professor_nome 
        FROM solicitacoes_hae s 
        JOIN usuarios u ON s.professor_id = u.id 
        WHERE " . implode(" AND ", $where) . " 
        ORDER BY u.nome ASC, s.titulo_projeto ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupa os projetos por Professor
$projetos_agrupados = [];
$total_concedido = 0;
$total_solicitado = 0;

foreach ($solicitacoes as $proj) {
    $projetos_agrupados[$proj['professor_nome']][] = $proj;
    
    // Cálculos de Totalizadores
    $total_solicitado += $proj['quantidade_horas'];
    if ($proj['status_aprovacao'] == 'Aprovado') {
        $total_concedido += $proj['quantidade_horas'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Projetos HAE - Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-bar { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; border-left: 4px solid #8e44ad; }
        .filter-group { display: flex; flex-direction: column; flex: 1; min-width: 200px; }
        .filter-group label { font-size: 12px; font-weight: bold; color: #555; margin-bottom: 5px; text-transform: uppercase; }
        .filter-group select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; outline: none; font-size: 14px; transition: 0.3s; }
        .filter-group select:focus { border-color: #8e44ad; }
        
        .btn-filtrar { background: #8e44ad; color: white; border: none; padding: 11px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-filtrar:hover { background: #732d91; }
        
        .btn-imprimir { background: #333; color: white; border: none; padding: 11px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; margin-left: auto;}
        .btn-imprimir:hover { background: #000; }

        .relatorio-container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow-x: auto; border-top: 4px solid var(--fatec-red); }
        
        .header-relatorio { text-align: center; margin-bottom: 30px; display: none; }
        .header-relatorio h2 { margin: 0; font-size: 18px; text-transform: uppercase; color: #000; }
        
        .tabela-relatorio { width: 100%; border-collapse: collapse; min-width: 800px; }
        .tabela-relatorio th { background-color: #f8f9fa; color: #333; font-weight: bold; text-transform: uppercase; font-size: 12px; padding: 12px 15px; border-bottom: 2px solid #333; text-align: left; }
        .tabela-relatorio td { padding: 12px 15px; font-size: 13.5px; border-bottom: 1px solid #eee; color: #444; vertical-align: middle; }
        
        .col-prof { font-weight: bold; color: #2c3e50; border-right: 1px solid #ccc; width: 25%; }
        .col-titulo { width: 45%; }
        .col-num { text-align: center !important; width: 15%; font-size: 15px; }
        .col-destaque { font-weight: bold; color: var(--fatec-red); }
        
        /* LINHA DE SEPARAÇÃO NÍTIDA ENTRE PROFESSORES */
        .linha-separadora td {
            padding: 0 !important;
            border-top: 2px solid #bdc3c7 !important;
            border-bottom: none !important;
        }
        
        .row-total { background-color: #f8f9fa; font-weight: bold; }
        .row-total td { border-top: 2px solid #333; border-bottom: none; font-size: 15px; color: #000; padding: 15px; }

        /* ESTILOS PARA QUANDO O DIRETOR CLICAR EM IMPRIMIR/SALVAR PDF */
        @media print {
            body { background: #fff; margin: 0; padding: 0; }
            .sidebar, .header-top, .filter-bar, .btn-imprimir { display: none !important; }
            .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .relatorio-container { box-shadow: none; border: none; padding: 0; }
            .header-relatorio { display: block; }
            .tabela-relatorio th { border-bottom: 2px solid #000; background-color: #eee !important; -webkit-print-color-adjust: exact; }
            .tabela-relatorio td { border-bottom: 1px solid #ccc; color: #000; }
            .col-prof { border-right: 1px solid #999; }
            .col-destaque { color: #000 !important; }
            .linha-separadora td { border-top: 2px solid #666 !important; } /* Mais forte no papel */
            .row-total td { border-top: 2px solid #000; background-color: #eee !important; -webkit-print-color-adjust: exact; }
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
            <button class="collapse-btn" id="collapse-btn" title="Minimizar Menu"><i class="fa-solid fa-bars-staggered"></i></button>
        </div>
        
        <nav class="menu">
            <div class="menu-title">Navegação</div>
            <ul>
                <li><a href="painel.php"><i class="fa-solid fa-chart-pie"></i> <span class="menu-text">Dashboard</span></a></li>
                <li><a href="analisar_solicitacoes.php"><i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">Analisar Solicitações</span></a></li>
                <li><a href="acompanhar_relatorios.php"><i class="fa-solid fa-chart-line"></i> <span class="menu-text">Acompanhar Relatórios</span></a></li>
                <li><a href="relatorios_atrasados.php"><i class="fa-solid fa-file-invoice"></i> <span class="menu-text">Relatórios Atrasados</span></a></li>
                
                <!-- MENU EXCLUSIVO DO DIRETOR -->
                <li><a href="projetos_hae.php" class="active"><i class="fa-solid fa-list-check"></i> <span class="menu-text">Projetos HAE</span></a></li>
                <li><a href="cadastrar_professor.php"><i class="fa-solid fa-user-plus"></i> <span class="menu-text">Cadastrar Usuário</span></a></li>
                <li><a href="listar_usuarios.php"><i class="fa-solid fa-users"></i> <span class="menu-text">Lista de Usuários</span></a></li>
                
                <li><a href="perfil.php"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>
                <li><a href="configuracoes.php"><i class="fa-solid fa-cogs"></i> <span class="menu-text">Configurações</span></a></li>
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span class="menu-text">Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Consolidação de Projetos HAE</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <form method="GET" class="filter-bar">
            <div class="filter-group">
                <label>Semestre / Período</label>
                <select name="semestre">
                    <option value="Todos" <?php echo $filtro_semestre == 'Todos' ? 'selected' : ''; ?>>Histórico Completo</option>
                    <?php foreach($semestres_disponiveis as $sem): ?>
                        <option value="<?php echo htmlspecialchars($sem); ?>" <?php echo $filtro_semestre == $sem ? 'selected' : ''; ?>>Semestre <?php echo htmlspecialchars($sem); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Status do Projeto</label>
                <select name="status">
                    <option value="Todos" <?php echo $filtro_status == 'Todos' ? 'selected' : ''; ?>>Todos os Projetos</option>
                    <option value="Aprovado" <?php echo $filtro_status == 'Aprovado' ? 'selected' : ''; ?>>Apenas Concedidos (Aprovados)</option>
                    <option value="Pendente" <?php echo $filtro_status == 'Pendente' ? 'selected' : ''; ?>>Apenas Pendentes</option>
                    <option value="Devolvido" <?php echo $filtro_status == 'Devolvido' ? 'selected' : ''; ?>>Apenas Devolvidos</option>
                    <option value="Rejeitado" <?php echo $filtro_status == 'Rejeitado' ? 'selected' : ''; ?>>Apenas Rejeitados</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-filtrar"><i class="fa-solid fa-filter"></i> Gerar Relatório</button>
            </div>
            
            <button type="button" class="btn-imprimir" onclick="window.print()"><i class="fa-solid fa-print"></i> Salvar PDF / Imprimir</button>
        </form>

        <div class="relatorio-container">
            <div class="header-relatorio">
                <h2>Projetos HAE - <?php echo $filtro_semestre == 'Todos' ? 'Histórico Completo' : htmlspecialchars($filtro_semestre); ?></h2>
                <p style="margin-top: 5px; color: #555; font-size: 12px;">Posição gerada em: <?php echo date('d/m/Y \à\s H:i'); ?></p>
            </div>

            <table class="tabela-relatorio">
                <thead>
                    <tr>
                        <th class="col-prof">Professor(a)</th>
                        <th class="col-titulo">Título do Projeto</th>
                        <th class="col-num">Nº HAEs SOLICITADOS</th>
                        <th class="col-num">Nº HAEs CONCEDIDOS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($projetos_agrupados) > 0): ?>
                        <?php foreach ($projetos_agrupados as $nome_prof => $projetos): ?>
                            <?php 
                                $qtd_projetos = count($projetos);
                                $is_first = true;
                                
                                foreach ($projetos as $p):
                                    // Limpa a versão do nome do projeto para o relatório final
                                    $titulo_limpo = preg_replace('/\s*-\s*v\d+\.\d+\s*$/i', '', $p['titulo_projeto']);
                                    
                                    // Regra de exibição: Solicitado sempre aparece. Concedido só aparece se foi aprovado.
                                    $solicitado = str_pad($p['quantidade_horas'], 2, '0', STR_PAD_LEFT);
                                    $concedido = ($p['status_aprovacao'] == 'Aprovado') ? str_pad($p['quantidade_horas'], 2, '0', STR_PAD_LEFT) : '-';
                                    
                                    // Adiciona um aviso visual discreto se o projeto não estiver aprovado
                                    $aviso_status = "";
                                    if ($p['status_aprovacao'] != 'Aprovado') {
                                        $cor = ($p['status_aprovacao'] == 'Pendente') ? '#3498db' : (($p['status_aprovacao'] == 'Devolvido') ? '#f39c12' : '#e74c3c');
                                        $aviso_status = " <span style='font-size:10px; color:$cor; border:1px solid $cor; padding:2px 5px; border-radius:3px; margin-left:5px;'>" . strtoupper($p['status_aprovacao']) . "</span>";
                                    }
                            ?>
                            <tr>
                                <?php if ($is_first): ?>
                                    <td class="col-prof" rowspan="<?php echo $qtd_projetos; ?>"><?php echo htmlspecialchars($nome_prof); ?></td>
                                    <?php $is_first = false; ?>
                                <?php endif; ?>
                                
                                <td class="col-titulo"><?php echo htmlspecialchars($titulo_limpo) . $aviso_status; ?></td>
                                <td class="col-num"><?php echo $solicitado; ?></td>
                                <td class="col-num col-destaque"><?php echo $concedido; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Linha divisória nítida entre professores -->
                            <tr class="linha-separadora"><td colspan="4"></td></tr>
                            
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: #888;">Nenhum projeto encontrado para este período com o status selecionado.</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if (count($projetos_agrupados) > 0): ?>
                <tfoot>
                    <tr class="row-total">
                        <td colspan="2" style="text-align: right;">TOTAL DE HAEs:</td>
                        <td class="col-num"><?php echo str_pad($total_solicitado, 2, '0', STR_PAD_LEFT); ?></td>
                        <td class="col-num" style="color: var(--fatec-red);"><?php echo str_pad($total_concedido, 2, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </main>

    <script src="assets/js/painel.js"></script>
</body>
</html>