<?php
session_start();
require 'config/conexao.php';

// Define o fuso horário para garantir que o dia 10 vire dia 11 na hora certa no Brasil
date_default_timezone_set('America/Sao_Paulo');

// Segurança: Apenas Coordenador ou Diretor
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_funcao'], ['Coordenador', 'Diretor'])) {
    header("Location: painel.php");
    exit;
}

// Lógica de Datas Automática
$dia_atual = (int)date('j');
$mes_atual = (int)date('n');
$ano_atual = (int)date('Y');

// O relatório a ser cobrado é sempre o do mês passado
$mes_cobranca = $mes_atual - 1;
$ano_cobranca = $ano_atual;

if ($mes_cobranca == 0) {
    $mes_cobranca = 12;
    $ano_cobranca--;
}

// Array para exibir o nome do mês na tela
$meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];
$nome_mes_cobranca = $meses[$mes_cobranca];

// A regra de ouro: Passou do dia 10?
$prazo_encerrado = ($dia_atual > 10);

// Busca todos os projetos APROVADOS e faz um LEFT JOIN com os relatórios do mês de cobrança
$sql = "SELECT 
            s.id AS projeto_id, 
            s.titulo_projeto, 
            u.nome AS professor_nome, 
            u.telefone_whatsapp,
            r.status AS status_relatorio,
            r.data_envio
        FROM solicitacoes_hae s
        JOIN usuarios u ON s.professor_id = u.id
        LEFT JOIN relatorios_hae r 
            ON s.id = r.solicitacao_id 
            AND r.mes_referencia = ? 
            AND r.ano_referencia = ?
        WHERE s.status_aprovacao = 'Aprovado'
        ORDER BY u.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$mes_cobranca, $ano_cobranca]);
$lista_projetos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhar Relatórios - HAE Fatec</title>
    
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-header { background: #fff; padding: 25px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; border-left: 4px solid var(--fatec-red); }
        .dashboard-header h2 { font-size: 20px; color: #333; margin-bottom: 5px; }
        .dashboard-header p { color: #666; font-size: 14px; }
        
        .status-box { text-align: right; }
        .status-box .mes-ref { font-size: 24px; font-weight: bold; color: var(--fatec-red); }
        .status-box .prazo { font-size: 12px; color: #888; font-weight: bold; text-transform: uppercase; margin-top: 5px;}

        .card-table { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background-color: #f8f9fa; color: #555; font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        tr:hover { background-color: #fcfcfc; }
        
        /* Badges e Status */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; }
        .badge-ok { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .badge-aguardando { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-atrasado { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .badge-rascunho { background: #e2e3e5; color: #383d41; border: 1px solid #bcc0c4; }

        .btn-whatsapp { background: #25D366; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-whatsapp:hover { background: #128C7E; }
    </style>
</head>
<body>

<?php
    // O PHP descobre magicamente em qual tela o usuário está agora
    $pagina_atual = basename($_SERVER['PHP_SELF']);
    ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="painel.php" class="brand">
                <img src="assets/img/logo.png" alt="Logo Fatec" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/5/52/Fatec_logo.svg'">
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
                    
                    <li><a href="meus_projetos.php" class="<?php echo ($pagina_atual == 'enviar_relatorio.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-calendar-check"></i> <span>Enviar Relatório</span>
                    </a></li>
                    
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
                <h1>Monitoramento de Relatórios</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <div class="dashboard-header">
            <div>
                <h2>Status de Entregas</h2>
                <p>Acompanhamento mensal dos relatórios de professores com HAE aprovada.</p>
            </div>
            <div class="status-box">
                <div class="mes-ref"><?php echo $nome_mes_cobranca . ' / ' . $ano_cobranca; ?></div>
                <div class="prazo">
                    <?php if ($prazo_encerrado): ?>
                        <span style="color: #b20000;"><i class="fa-solid fa-circle-exclamation"></i> Prazo Encerrado (Dia 10)</span>
                    <?php else: ?>
                        <span style="color: #2ecc71;"><i class="fa-solid fa-clock"></i> Dentro do Prazo (Até Dia 10)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card-table">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Professor(a)</th>
                            <th>Projeto</th>
                            <th>Status do Relatório</th>
                            <th>Data de Envio</th>
                            <th>Cobrar / Notificar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($lista_projetos) > 0): ?>
                            <?php foreach ($lista_projetos as $row): ?>
                                <?php
                                    // Lógica visual do Status
                                    $status_html = '';
                                    $precisa_cobrar = false;

                                    if ($row['status_relatorio'] == 'Publicado') {
                                        $status_html = '<span class="badge badge-ok"><i class="fa-solid fa-check"></i> Entregue</span>';
                                    } elseif ($row['status_relatorio'] == 'Rascunho') {
                                        $status_html = '<span class="badge badge-rascunho"><i class="fa-solid fa-pen"></i> Rascunho</span>';
                                        $precisa_cobrar = true;
                                    } else {
                                        // Não existe relatório
                                        if ($prazo_encerrado) {
                                            $status_html = '<span class="badge badge-atrasado"><i class="fa-solid fa-triangle-exclamation"></i> Atrasado</span>';
                                            $precisa_cobrar = true;
                                        } else {
                                            $status_html = '<span class="badge badge-aguardando"><i class="fa-solid fa-hourglass-half"></i> Aguardando</span>';
                                        }
                                    }

                                    // Lógica para o link do WhatsApp
                                    $num_limpo = preg_replace('/\D/', '', $row['telefone_whatsapp']);
                                    if (substr($num_limpo, 0, 2) !== '55') $num_limpo = '55' . $num_limpo;
                                    $msg_wa = "Olá Prof. " . $row['professor_nome'] . ", notamos que o relatório HAE referente a " . $nome_mes_cobranca . "/" . $ano_cobranca . " ainda consta como pendente no sistema. Poderia verificar?";
                                    $link_wa = "https://wa.me/{$num_limpo}?text=" . urlencode($msg_wa);
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['professor_nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['titulo_projeto']); ?></td>
                                    <td><?php echo $status_html; ?></td>
                                    <td>
                                        <?php echo $row['data_envio'] ? date('d/m/Y', strtotime($row['data_envio'])) : '<span style="color:#ccc;">--/--/----</span>'; ?>
                                    </td>
                                    <td>
                                        <?php if ($precisa_cobrar): ?>
                                            <a href="<?php echo $link_wa; ?>" target="_blank" class="btn-whatsapp">
                                                <i class="fa-brands fa-whatsapp" style="font-size: 16px;"></i> Notificar
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#ccc; font-size: 12px;"><i class="fa-solid fa-check-double"></i> Tudo Certo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding: 30px; color: #888;">Nenhum projeto aprovado para monitoramento no momento.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="assets/js/painel.js"></script>
</body>
</html>