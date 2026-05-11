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
        .projetos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .projeto-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border-top: 4px solid #ccc; /* Cor padrão, muda via PHP */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: 0.3s;
        }
        .projeto-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }

        .projeto-header { margin-bottom: 15px; }
        .projeto-header h3 { font-size: 18px; color: #333; margin-bottom: 5px; }
        .projeto-header .semestre { font-size: 12px; color: #888; font-weight: bold; text-transform: uppercase; }

        .projeto-body { margin-bottom: 20px; font-size: 14px; color: #555; }
        .projeto-body p { margin-bottom: 8px; }
        .projeto-body strong { color: #333; }

        /* Badges de Status */
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 15px; }
        .status-pendente { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-aprovado { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .status-rejeitado { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        .projeto-actions { display: flex; gap: 10px; margin-top: auto; border-top: 1px solid #eee; padding-top: 20px; }
        
        .btn { flex: 1; text-align: center; padding: 10px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; transition: 0.3s; border: none; cursor: pointer; }
        .btn-relatorio { background: #3498db; color: #fff; }
        .btn-relatorio:hover { background: #2980b9; }
        .btn-visualizar { background: #f8f9fa; color: #444; border: 1px solid #ddd; }
        .btn-visualizar:hover { background: #e9ecef; }
    </style>
</head>
<body>

    <!-- Sidebar Atualizada com FontAwesome -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
            <a href="painel.php" class="brand">
                <img src="Img/cps_fatecgarca_logo.jfif" alt="Logo Fatec" >
                <h2>HAE </h2>
            </a>
            <button class="collapse-btn" id="collapse-btn"><i class="fa-solid fa-bars"></i></button>
        </div>
        
        <nav class="menu">
            <div class="menu-title">Navegação</div>
            <ul>
                <li><a href="painel.php"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></a></li>
                <li><a href="nova_solicitacao.php"><i class="fa-solid fa-file-circle-plus"></i> <span>Nova Solicitação HAE</span></a></li>
                <li><a href="meus_projetos.php" class="active"><i class="fa-solid fa-folder-open"></i> <span>Meus Projetos</span></a></li>
                <li><a href="enviar_relatorio.php"><i class="fa-solid fa-calendar-check"></i> <span>Enviar Relatório</span></a></li>
                <li><a href="perfil.php"><i class="fa-solid fa-user-gear"></i> <span>Meu Perfil</span></a></li>
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

        <p style="color: #666; margin-bottom: 20px;">Acompanhe o status dos seus projetos e envie os relatórios mensais de horas aprovadas.</p>

        <div class="projetos-grid">
            <?php if (count($projetos) > 0): ?>
                <?php foreach ($projetos as $projeto): ?>
                    
                    <?php 
                        // Definir cores e estilos baseados no status
                        $status_class = '';
                        $border_color = '';
                        if ($projeto['status_aprovacao'] == 'Pendente') {
                            $status_class = 'status-pendente';
                            $border_color = '#f39c12';
                        } elseif ($projeto['status_aprovacao'] == 'Aprovado') {
                            $status_class = 'status-aprovado';
                            $border_color = '#2ecc71';
                        } else {
                            $status_class = 'status-rejeitado';
                            $border_color = '#e74c3c';
                        }
                    ?>

                    <div class="projeto-card" style="border-top-color: <?php echo $border_color; ?>;">
                        <div>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo $projeto['status_aprovacao']; ?>
                            </span>
                            
                            <div class="projeto-header">
                                <h3><?php echo htmlspecialchars($projeto['titulo_projeto']); ?></h3>
                                <span class="semestre">Semestre: <?php echo htmlspecialchars($projeto['semestre']); ?></span>
                            </div>

                            <div class="projeto-body">
                                <p><strong>Categoria:</strong> <?php echo htmlspecialchars($projeto['categoria']); ?></p>
                                <p><strong>HAE Solicitada:</strong> <?php echo $projeto['quantidade_horas']; ?>h</p>
                                
                                <?php if ($projeto['status_aprovacao'] == 'Aprovado'): ?>
                                    <p style="color: #27ae60; font-weight: bold; margin-top: 10px;">
                                        <i class="fa-solid fa-check-circle"></i> HAE Aprovada: <?php echo $projeto['quantidade_horas']; ?>h
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="projeto-actions">
                            <a href="documento_hae.php?id=<?php echo $projeto['id']; ?>" target="_blank" class="btn btn-visualizar">
                                <i class="fa-solid fa-file-pdf"></i> Ver Projeto
                            </a>
                            
                            <!-- Botão de Relatório só aparece se o projeto foi aprovado -->
                            <?php if ($projeto['status_aprovacao'] == 'Aprovado'): ?>
                                <a href="enviar_relatorio.php?id_projeto=<?php echo $projeto['id']; ?>" class="btn btn-relatorio">
                                    <i class="fa-solid fa-pen-to-square"></i> Lançar Relatório
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; background: #fff; padding: 40px; border-radius: 10px; text-align: center; color: #888;">
                    <i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 15px; color: #ddd;"></i>
                    <p>Você ainda não enviou nenhuma solicitação de projeto HAE.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <script src="assets/js/painel.js"></script>
</body>
</html>