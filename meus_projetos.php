<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Professor acessa
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] != 'Professor') {
    header("Location: painel.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Busca os projetos do professor logado
$sql = "SELECT * FROM solicitacoes_hae WHERE professor_id = ? ORDER BY data_criacao DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$projetos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        .card-table { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow: hidden; border-top: 4px solid var(--fatec-red); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: middle; }
        th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #fcfcfc; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; white-space: nowrap; }
        .badge-pendente { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-aprovado { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .badge-rejeitado { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        .btn-action { padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-pdf { background: #1e1e2d; color: #fff; }
        .btn-pdf:hover { background: var(--fatec-red); }
        .btn-editar { background: #f39c12; color: #fff; }
        .btn-editar:hover { background: #d68910; }

        .motivo-recusa { background: #fff9f9; border-left: 3px solid #e74c3c; padding: 10px; margin-top: 10px; font-size: 12px; color: #333; border-radius: 0 4px 4px 0; }
        .motivo-recusa strong { color: #c0392b; }
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
                <li><a href="nova_solicitacao.php" class="<?php echo ($pagina_atual == 'nova_solicitacao.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-circle-plus"></i> <span>Nova Solicitação</span></a></li>
                <li><a href="meus_projetos.php" class="<?php echo ($pagina_atual == 'meus_projetos.php') ? 'active' : ''; ?>"><i class="fa-solid fa-folder-open"></i> <span>Meus Projetos</span></a></li>
                <li><a href="enviar_relatorio.php" class="<?php echo ($pagina_atual == 'enviar_relatorio.php') ? 'active' : ''; ?>"><i class="fa-solid fa-calendar-check"></i> <span>Enviar Relatório</span></a></li>
                <li><a href="meus_relatorios.php" class="<?php echo ($pagina_atual == 'meus_relatorios.php') ? 'active' : ''; ?>"><i class="fa-solid fa-list-check"></i> <span>Meus Relatórios</span></a></li>
                <li><a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-gear"></i> <span>Meu Perfil</span></a></li>
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span>Sair do Sistema</span></a></li>
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

        <p style="color: #666; margin-bottom: 25px;">Acompanhe o status das suas solicitações de Hora Atividade Específica.</p>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'reenviado'): ?>
            <div class="alert-success" style="margin-bottom: 25px;">✅ Projeto editado e reenviado para análise com sucesso!</div>
        <?php endif; ?>

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
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($proj['data_criacao'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($proj['titulo_projeto']); ?></strong>
                                        
                                        <?php if($proj['status_aprovacao'] == 'Rejeitado'): ?>
                                            <div class="motivo-recusa">
                                                <strong>Motivo da Devolução:</strong><br>
                                                <?php 
                                                    // Mostra o parecer de quem rejeitou
                                                    if ($proj['status_coordenador'] == 'Rejeitado') {
                                                        echo htmlspecialchars($proj['parecer_coordenador']);
                                                    } else if ($proj['status_diretor'] == 'Rejeitado') {
                                                        echo htmlspecialchars($proj['parecer_diretor']);
                                                    }
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($proj['semestre']); ?></td>
                                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $proj['status_aprovacao']; ?></span></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="documento_hae.php?id=<?php echo $proj['id']; ?>" target="_blank" class="btn-action btn-pdf">
                                                <i class="fa-solid fa-file-pdf"></i> Visualizar
                                            </a>
                                            
                                            <?php if($proj['status_aprovacao'] == 'Rejeitado'): ?>
                                                <a href="editar_solicitacao.php?id=<?php echo $proj['id']; ?>" class="btn-action btn-editar">
                                                    <i class="fa-solid fa-pen-to-square"></i> Editar e Reenviar
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding: 40px; color: #888;">Você ainda não solicitou nenhum projeto HAE.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="assets/js/painel.js"></script>
</body>
</html>