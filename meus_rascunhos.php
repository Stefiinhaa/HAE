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

// CORREÇÃO AQUI: Trocado data_atualizacao por data_envio
$sql = "SELECT r.*, s.titulo_projeto 
        FROM relatorios_hae r 
        JOIN solicitacoes_hae s ON r.solicitacao_id = s.id 
        WHERE s.professor_id = ? AND r.status = 'Rascunho' 
        ORDER BY r.data_envio DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$rascunhos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Rascunhos - HAE Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-table { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow: hidden; border-top: 4px solid #f39c12; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: middle; }
        th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #fcfcfc; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; white-space: nowrap; }
        .badge-rascunho { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }

        .btn-action { background: #3498db; color: #fff; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-action:hover { background: #2980b9; }
        
        .info-vazio { text-align: center; padding: 50px; color: #888; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .info-vazio i { font-size: 40px; color: #ddd; }
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
                    <!-- O LINK ATUALIZADO AQUI -->
                    <li>
                        <a href="meus_rascunhos.php" class="<?php echo ($pagina_atual == 'meus_rascunhos.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-file-pen"></i> <span class="menu-text">Meus Rascunhos</span>
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
                <h1>Meus Rascunhos de Relatório</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <p style="color: #666; margin-bottom: 25px;">Aqui ficam guardados os relatórios que você começou a preencher, mas salvou como rascunho para terminar depois.</p>

        <div class="card-table">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Data do Salvo</th>
                            <th>Mês de Referência</th>
                            <th style="width: 40%;">Projeto Vinculado</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rascunhos) > 0): ?>
                            <?php foreach ($rascunhos as $r): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y \à\s H:i', strtotime($r['data_envio'])); ?></td>
                                    <td><strong><?php echo $meses[$r['mes_referencia']] . ' / ' . $r['ano_referencia']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($r['titulo_projeto']); ?></td>
                                    <td><span class="badge badge-rascunho"><i class="fa-solid fa-pen"></i> Em Edição</span></td>
                                    <td>
                                        <a href="enviar_relatorio.php?id=<?php echo $r['id']; ?>" class="btn-action">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Continuar Editando
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="info-vazio">
                                        <i class="fa-solid fa-box-open"></i>
                                        Você não possui nenhum rascunho salvo no momento.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="assets/js/painel.js"></script>
</body>
</html>