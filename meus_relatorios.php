<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Professor acessa esta tela
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] !== 'Professor') {
    header("Location: painel.php");
    exit;
}

$professor_id = $_SESSION['usuario_id'];
$sucesso = "";

if (isset($_GET['status_msg']) && $_GET['status_msg'] == 'sucesso') {
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
    $sucesso = "Relatório " . ($tipo == 'Publicado' ? "publicado em definitivo" : "salvo como rascunho") . " com sucesso!";
}

// Busca todos os relatórios do professor (juntando com o nome do projeto)
$sql = "SELECT r.*, s.titulo_projeto 
        FROM relatorios_hae r 
        JOIN solicitacoes_hae s ON r.solicitacao_id = s.id 
        WHERE s.professor_id = ? 
        ORDER BY r.ano_referencia DESC, r.mes_referencia DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$professor_id]);
$relatorios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];
$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Relatórios - HAE Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-table { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow: hidden; border-top: 4px solid var(--fatec-red); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: middle; }
        th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #fcfcfc; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; white-space: nowrap; }
        .bg-rascunho { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .bg-publicado { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }

        .btn-action { padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-editar { background: #3498db; color: #fff; }
        .btn-editar:hover { background: #2980b9; }
        .texto-bloqueado { color: #888; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 5px; }
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
                
                <!-- O NOVO MENU AQUI -->
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
                <h1>Histórico de Relatórios</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <?php if($sucesso) echo "<div class='alert-success'>✅ $sucesso</div>"; ?>

        <p style="color: #666; margin-bottom: 20px;">Acompanhe aqui todos os relatórios mensais enviados e continue a edição dos seus rascunhos.</p>

        <div class="card-table">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Mês / Ano</th>
                            <th>Projeto Relacionado</th>
                            <th>Última Atualização</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($relatorios) > 0): ?>
                            <?php foreach ($relatorios as $rel): ?>
                                <tr>
                                    <td><strong><?php echo $meses[$rel['mes_referencia']] . ' / ' . $rel['ano_referencia']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($rel['titulo_projeto']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($rel['data_envio'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $rel['status'] == 'Publicado' ? 'bg-publicado' : 'bg-rascunho'; ?>">
                                            <?php echo $rel['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($rel['status'] == 'Rascunho'): ?>
                                            <a href="enviar_relatorio.php?id_projeto=<?php echo $rel['solicitacao_id']; ?>&edit_id=<?php echo $rel['id']; ?>" class="btn-action btn-editar">
                                                <i class="fa-solid fa-pen-to-square"></i> Continuar Edição
                                            </a>
                                        <?php else: ?>
                                            <span class="texto-bloqueado"><i class="fa-solid fa-lock"></i> Entregue Definitivo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 40px; color: #888;">
                                    <i class="fa-solid fa-file-excel" style="font-size: 30px; margin-bottom: 10px; color: #ddd; display: block;"></i>
                                    Nenhum relatório foi salvo ou enviado ainda.
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