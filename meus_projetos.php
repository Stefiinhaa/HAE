<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Professor acessa esta tela
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] !== 'Professor') {
    header("Location: painel.php");
    exit;
}

$professor_id = $_SESSION['usuario_id'];

// Busca todos os projetos do professor logado
$sql = "SELECT * FROM solicitacoes_hae WHERE professor_id = ? ORDER BY data_criacao DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$professor_id]);
$projetos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        /* Estilos da Data Grid Profissional */
        .card-table { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow: hidden; border-top: 4px solid var(--fatec-red); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: middle; }
        th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #fcfcfc; }
        
        /* Badges de Status */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; white-space: nowrap; }
        .badge-pendente { background: #fff3cd; color: #856404; }
        .badge-aprovado { background: #d1e7dd; color: #0f5132; }
        .badge-rejeitado { background: #f8d7da; color: #842029; }

        /* Botões de Ação na Tabela */
        .acoes-flex { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-action { padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-visualizar { background: #f8f9fa; color: #444; border: 1px solid #ddd; }
        .btn-visualizar:hover { background: #e9ecef; }
        .btn-relatorio { background: #3498db; color: #fff; }
        .btn-relatorio:hover { background: #2980b9; }

        /* Mensagens de Retorno da Direção */
        .feedback-box { font-size: 12px; padding: 8px; border-radius: 4px; margin-top: 5px; }
        .feedback-rejeitado { background: #fee2e2; color: #b91c1c; border-left: 3px solid #e74c3c; }
        .feedback-aprovado { background: #f8f9fa; color: #555; border-left: 3px solid #2ecc71; }
        
        .horas-destaque { font-weight: bold; color: #27ae60; font-size: 13px; display: block; margin-bottom: 4px; }
    </style>
</head>
<body>

<?php
    $pagina_atual = basename($_SERVER['PHP_SELF']);
?>
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
                    
                    <!-- MENU CORRIGIDO -->
                    <li><a href="enviar_relatorio.php" class="<?php echo ($pagina_atual == 'enviar_relatorio.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-calendar-check"></i> <span>Enviar Relatório</span>
                    </a></li>

                    <li><a href="meus_relatorios.php" class="<?php echo ($pagina_atual == 'meus_relatorios.php') ? 'active' : ''; ?>"><i class="fa-solid fa-list-check"></i> <span>Meus Relatórios</span></a></li>
                    
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
                <h1>Meus Projetos HAE</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <p style="color: #666; margin-bottom: 20px;">Acompanhe o status dos seus projetos e gerencie os relatórios mensais de atividades.</p>

        <div class="card-table">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Data Env.</th>
                            <th>Semestre</th>
                            <th>Título do Projeto</th>
                            <th>Categoria</th>
                            <th>Status & Parecer</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($projetos) > 0): ?>
                            <?php foreach ($projetos as $projeto): ?>
                                <?php 
                                    $badge_class = 'badge-pendente';
                                    if($projeto['status_aprovacao'] == 'Aprovado') $badge_class = 'badge-aprovado';
                                    if($projeto['status_aprovacao'] == 'Rejeitado') $badge_class = 'badge-rejeitado';
                                ?>
                                <tr>
                                    <td style="white-space: nowrap;"><?php echo date('d/m/Y', strtotime($projeto['data_criacao'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($projeto['semestre']); ?></strong></td>
                                    <td style="max-width: 250px; line-height: 1.4;">
                                        <strong><?php echo htmlspecialchars($projeto['titulo_projeto']); ?></strong><br>
                                        <span style="font-size: 11px; color: #888;">Req. Original: <?php echo $projeto['quantidade_horas']; ?>h</span>
                                    </td>
                                    <td><?php echo htmlspecialchars($projeto['categoria']); ?></td>
                                    <td style="max-width: 300px;">
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $projeto['status_aprovacao']; ?></span>
                                        
                                        <?php if ($projeto['status_aprovacao'] == 'Aprovado'): ?>
                                            <div style="margin-top: 8px;">
                                                <span class="horas-destaque"><i class="fa-solid fa-check-circle"></i> Aprovado: <?php echo $projeto['quantidade_horas']; ?>h mensais</span>
                                                <?php if(!empty($projeto['parecer_direcao'])): ?>
                                                    <div class="feedback-box feedback-aprovado">
                                                        <strong>Obs:</strong> <?php echo htmlspecialchars($projeto['parecer_direcao']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($projeto['status_aprovacao'] == 'Rejeitado'): ?>
                                            <div class="feedback-box feedback-rejeitado" style="margin-top: 8px;">
                                                <strong>Motivo:</strong> <?php echo htmlspecialchars($projeto['parecer_direcao']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="acoes-flex">
                                            <a href="documento_hae.php?id=<?php echo $projeto['id']; ?>" target="_blank" class="btn-action btn-visualizar">
                                                <i class="fa-solid fa-file-pdf"></i> PDF
                                            </a>
                                            
                                            <?php if ($projeto['status_aprovacao'] == 'Aprovado'): ?>
                                                <a href="enviar_relatorio.php?id_projeto=<?php echo $projeto['id']; ?>" class="btn-action btn-relatorio">
                                                    <i class="fa-solid fa-pen-to-square"></i> Relatório
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding: 50px; color: #888;">
                                    <i class="fa-solid fa-folder-open" style="font-size: 30px; margin-bottom: 10px; color: #ddd; display: block;"></i>
                                    Você ainda não enviou nenhuma solicitação de projeto HAE.
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