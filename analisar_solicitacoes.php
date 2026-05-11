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
        $sql = "UPDATE solicitacoes_hae SET status_aprovacao = ?, quantidade_horas = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$novo_status, $horas_aprovadas, $solicitacao_id]);
        
        // CORREÇÃO: Redireciona de volta para a lista principal com uma mensagem de sucesso na URL
        header("Location: analisar_solicitacoes.php?status=sucesso");
        exit;
    } catch (PDOException $e) {
        $mensagem = "Erro ao processar: " . $e->getMessage();
    }
}

// Captura a mensagem de sucesso vinda do redirecionamento
if (isset($_GET['status']) && $_GET['status'] == 'sucesso') {
    $mensagem = "Parecer registrado com sucesso!";
}


// Verifica se estamos visualizando uma solicitação
$visualizando_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$detalhes = null;

if ($visualizando_id) {
    $sql = "SELECT s.*, u.nome AS professor_nome FROM solicitacoes_hae s 
            JOIN usuarios u ON s.professor_id = u.id WHERE s.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$visualizando_id]);
    $detalhes = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $sql = "SELECT s.*, u.nome AS professor_nome FROM solicitacoes_hae s 
            JOIN usuarios u ON s.professor_id = u.id 
            WHERE s.status_aprovacao = 'Pendente' ORDER BY s.data_criacao ASC";
    $stmt = $pdo->query($sql);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisar Solicitações - Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
<!-- Nova biblioteca de ícones profissionais -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-table { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow: hidden; border-top: 4px solid #f39c12; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #fcfcfc; }
        
        .badge-pendente { background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .btn-action { background: #1e1e2d; color: #fff; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 12px; transition: 0.3s; }
        .btn-action:hover { background: var(--fatec-red); }
        .btn-voltar { display: inline-block; margin-bottom: 20px; color: #666; text-decoration: none; font-weight: bold; font-size: 14px; }
        .btn-voltar:hover { color: var(--fatec-red); }

        /* --- ESTILOS DO SPLIT VIEW (VISÃO DIVIDIDA) --- */
        .split-view {
            display: flex;
            gap: 25px;
            align-items: flex-start;
        }

        /* Lado Esquerdo: O PDF Embutido */
        .doc-preview {
            flex: 6.5; /* Ocupa 65% da tela */
            height: 85vh; /* Altura de 85% da tela */
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #ddd;
            background: #525659;
        }
        .doc-preview iframe { width: 100%; height: 100%; border: none; }

        /* Lado Direito: O Formulário de Parecer */
        .form-parecer { 
            flex: 3.5; /* Ocupa 35% da tela */
            background: #fff; 
            padding: 30px; 
            border-radius: 10px; 
            border-top: 4px solid var(--fatec-red); 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            position: sticky; /* Fica fixo enquanto o PDF rola */
            top: 20px;
        }
        .form-parecer label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 14px; color: #444; }
        .form-parecer input, .form-parecer textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        
        .botoes-acao { display: flex; flex-direction: column; gap: 10px; }
        .btn-aprovar { background: #2ecc71; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; transition: 0.3s;}
        .btn-aprovar:hover { background: #27ae60; }
        .btn-rejeitar { background: #e74c3c; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; transition: 0.3s;}
        .btn-rejeitar:hover { background: #c0392b; }

        /* Responsividade para telas menores */
       /* Botão para o Mobile (Oculto no Desktop) */
       .btn-ver-pdf-mobile {
            display: none;
            background: #3498db;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.2);
            transition: 0.3s;
        }
        .btn-ver-pdf-mobile:hover { background: #2980b9; }

        /* Responsividade para telas menores */
        @media (max-width: 1024px) {
            .split-view { flex-direction: column; }
            .doc-preview { display: none; } /* Esconde o iframe bugado no celular */
            .form-parecer { flex: 1; width: 100%; position: relative; top: 0; }
            .btn-ver-pdf-mobile { display: block; } /* Mostra o botão no lugar dele */
        }
    </style>
</head>
<body>

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
                <li><a href="painel.php" class="active"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></a></li>
                
                <?php if ($_SESSION['usuario_funcao'] == 'Professor'): ?>
                    <li><a href="nova_solicitacao.php"><i class="fa-solid fa-file-circle-plus"></i> <span>Nova Solicitação HAE</span></a></li>
                    <li><a href="meus_projetos.php"><i class="fa-solid fa-folder-open"></i> <span>Meus Projetos</span></a></li>
                    <li><a href="enviar_relatorio.php"><i class="fa-solid fa-calendar-check"></i> <span>Enviar Relatório</span></a></li>
                <?php else: ?>
                    <li><a href="analisar_solicitacoes.php"><i class="fa-solid fa-clipboard-check"></i> <span>Analisar Solicitações</span></a></li>
                    <li><a href="acompanhar_relatorio.php"><i class="fa-solid fa-chart-line"></i> <span>Acompanhar Relatórios</span></a></li>
                    <li><a href="cadastrar_professor.php"><i class="fa-solid fa-user-plus"></i> <span>Cadastrar Professor</span></a></li>
                    <li><a href="#"><i class="fa-solid fa-users-viewfinder"></i> <span>Professores Pendentes</span></a></li>
                <?php endif; ?>
                
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span>Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle">☰</button>
                <h1><?php echo $visualizando_id ? "Análise de Projeto" : "Solicitações Pendentes"; ?></h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo $_SESSION['usuario_nome']; ?></strong></div>
        </header>

        <?php if($mensagem): ?>
            <div class="alert-success">✅ <?php echo $mensagem; ?></div>
        <?php endif; ?>

        <?php if ($visualizando_id && $detalhes): ?>
            <a href="analisar_solicitacoes.php" class="btn-voltar">← Voltar para a lista de pendências</a>
            
            <!-- CONTAINER DO SPLIT VIEW -->
            <div class="split-view">
                
                <!-- Lado Esquerdo: O PDF gerado pelo documento_hae.php -->
                <div class="doc-preview">
                    <iframe src="documento_hae.php?id=<?php echo $visualizando_id; ?>"></iframe>
                </div>

                <!-- Lado Direito: Formulário de Ação -->
                <!-- Lado Direito: Formulário de Ação -->
                <div class="form-parecer">
                    <h3 style="margin-bottom: 20px; color: var(--fatec-red); font-size: 18px;">Parecer da Direção</h3>
                    
                    <!-- NOVO BOTÃO MOBILE AQUI -->
                    <a href="documento_hae.php?id=<?php echo $visualizando_id; ?>" target="_blank" class="btn-ver-pdf-mobile">📄 Abrir Documento em Tela Cheia</a>
                    
                    <p style="font-size: 13px; color: #666; margin-bottom: 20px;">
                        Verifique o documento. Caso esteja tudo correto, defina as horas aprovadas e emita seu parecer.
                    </p>

                    <form method="POST" action="analisar_solicitacoes.php?id=<?php echo $visualizando_id; ?>">
                        <input type="hidden" name="solicitacao_id" value="<?php echo $visualizando_id; ?>">
                        
                        <label>Horas HAE Aprovadas</label>
                        <input type="number" name="horas_aprovadas" value="<?php echo $detalhes['quantidade_horas']; ?>" required min="0">
                        
                        <label>Observações / Parecer Final (Opcional)</label>
                        <textarea name="parecer" rows="5" placeholder="Descreva o motivo da aprovação ou os ajustes necessários na rejeição..."></textarea>
                        
                        <div class="botoes-acao">
                            <button type="submit" name="acao" value="aprovar" class="btn-aprovar">✓ Aprovar Projeto HAE</button>
                            <button type="submit" name="acao" value="rejeitar" class="btn-rejeitar" onclick="return confirm('Tem certeza que deseja rejeitar este projeto?');">✕ Rejeitar Projeto</button>
                        </div>
                    </form>
                </div>

            </div>

        <?php else: ?>
            <div class="card-table">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Professor(a)</th>
                            <th>Projeto</th>
                            <th>HAE Solicitada</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($solicitacoes) > 0): ?>
                            <?php foreach ($solicitacoes as $row): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['data_criacao'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['professor_nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['titulo_projeto']); ?></td>
                                    <td><?php echo $row['quantidade_horas']; ?>h</td>
                                    <td><span class="badge-pendente">Pendente</span></td>
                                    <td>
                                        <!-- Agora tem apenas o botão Analisar, direto e limpo -->
                                        <a href="analisar_solicitacoes.php?id=<?php echo $row['id']; ?>" class="btn-action">Analisar Projeto</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; padding: 30px; color: #888;">Nenhuma solicitação pendente no momento.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </main>

    <script src="assets/js/painel.js"></script>
</body>
</html>