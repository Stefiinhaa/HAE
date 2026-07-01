<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_funcao'], ['Coordenador', 'Diretor'])) {
    header("Location: painel.php");
    exit;
}

$meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];

// MUNDO REAL ATIVADO
$hoje = new DateTime(); 

$mes_atual = (int)$hoje->format('m');
$ano_atual = (int)$hoje->format('Y');

// O relatório sempre lista os inadimplentes referentes ao MÊS PASSADO
$data_limite = new DateTime("$ano_atual-$mes_atual-01");
$mes_passado_obj = clone $data_limite;
$mes_passado_obj->modify('-1 month');
$mes_alvo = (int)$mes_passado_obj->format('m');
$ano_alvo = (int)$mes_passado_obj->format('Y');

// Filtro do SQL para puxar quem NÃO enviou o relatório daquele mês exato
$ultimo_dia_mes_alvo = date('Y-m-t 23:59:59', strtotime(sprintf('%04d-%02d-01', $ano_alvo, $mes_alvo)));

$sql = "SELECT s.titulo_projeto, u.nome as professor_nome, u.telefone_whatsapp 
        FROM solicitacoes_hae s 
        JOIN usuarios u ON s.professor_id = u.id 
        LEFT JOIN relatorios_hae r ON (r.solicitacao_id = s.id AND r.mes_referencia = ? AND r.ano_referencia = ? AND r.status = 'Publicado')
        WHERE s.status_aprovacao = 'Aprovado' 
        AND COALESCE(s.data_aprovacao_diretor, s.data_aprovacao_coordenador, s.data_criacao) <= ?
        AND r.id IS NULL
        ORDER BY u.nome ASC, s.titulo_projeto ASC";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$mes_alvo, $ano_alvo, $ultimo_dia_mes_alvo]);
$inadimplentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Atrasados - HAE</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .print-header { display: none; text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .print-header img { max-width: 150px; margin-bottom: 10px; }
        .print-header h1 { font-size: 20px; text-transform: uppercase; color: #333; margin: 0; }
        .print-header p { font-size: 14px; color: #666; margin: 5px 0 0 0; }

        .btn-imprimir { background: #333; color: #fff; padding: 12px 25px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-imprimir:hover { background: #000; }

        .tabela-inadimplentes { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid #ddd; margin-top: 20px; }
        .tabela-inadimplentes th { background: var(--fatec-red); color: #fff; padding: 15px 20px; text-align: left; font-size: 13px; text-transform: uppercase; }
        .tabela-inadimplentes td { padding: 15px 20px; border-bottom: 1px solid #eee; font-size: 14px; color: #444; }
        .tabela-inadimplentes tr:last-child td { border-bottom: none; }

        .assinatura-box { display: none; margin-top: 60px; text-align: center; }
        .assinatura-linha { width: 300px; border-top: 1px solid #333; margin: 0 auto 10px auto; }
        .assinatura-texto { font-size: 14px; color: #333; font-weight: bold; }

        /* MÁGICA PARA QUANDO APERTAR CTRL+P */
        @media print {
            body { background: #fff !important; margin: 0; padding: 0; }
            .sidebar, .header-top, .user-info, .btn-imprimir, .menu { display: none !important; }
            .main-content { margin: 0 !important; width: 100% !important; padding: 20px !important; }
            .print-header { display: block; }
            .assinatura-box { display: block; }
            .tabela-inadimplentes { box-shadow: none; border: 1px solid #000; }
            .tabela-inadimplentes th { background: #f0f0f0 !important; color: #000 !important; -webkit-print-color-adjust: exact; }
            .tabela-inadimplentes td { border-bottom: 1px solid #ccc; }
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
                <li><a href="relatorios_atrasados.php" class="active"><i class="fa-solid fa-file-invoice"></i> <span class="menu-text">Relatórios Atrasados</span></a></li>
                <li><a href="cadastrar_professor.php"><i class="fa-solid fa-user-plus"></i> <span class="menu-text">Cadastrar Usuário</span></a></li>
                <li><a href="perfil.php"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span class="menu-text">Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Documento Oficial de Cobrança</h1>
            </div>
            <div class="user-info">
                Data base: <strong><?php echo $hoje->format('d/m/Y'); ?></strong>
            </div>
        </header>

        <div class="print-header">
            <img src="img/cps_fatecgarca_logo.jfif" alt="Logo Fatec">
            <h1>Relatório de Inadimplência HAE</h1>
            <p>Professores com relatórios pendentes referentes ao período de: <strong><?php echo $meses[$mes_alvo] . ' / ' . $ano_alvo; ?></strong></p>
            <p>Data de geração do documento: <?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <div style="background: #fff; padding: 30px; border-radius: 10px; border-top: 5px solid var(--fatec-red); box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2 style="font-size: 20px; color: #333; margin-bottom: 5px;">Relatório de Pendências: <?php echo $meses[$mes_alvo] . ' / ' . $ano_alvo; ?></h2>
                    <p style="color: #666; font-size: 14px;">Lista de docentes que não submeteram os relatórios no portal até a data limite (Dia 10).</p>
                </div>
                <button onclick="window.print()" class="btn-imprimir">
                    <i class="fa-solid fa-print"></i> Imprimir PDF Oficial
                </button>
            </div>

            <?php if (count($inadimplentes) > 0): ?>
                <table class="tabela-inadimplentes">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Professor(a)</th>
                            <th style="width: 45%;">Projeto Vinculado</th>
                            <th style="width: 20%;">Telefone (Sistema)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inadimplentes as $ind): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ind['professor_nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($ind['titulo_projeto']); ?></td>
                                <td><?php echo !empty($ind['telefone_whatsapp']) ? htmlspecialchars($ind['telefone_whatsapp']) : 'Não informado'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="background: #f4fbf7; padding: 30px; text-align: center; border-radius: 8px; border: 1px solid #d1e7dd; margin-top: 20px;">
                    <i class="fa-solid fa-circle-check" style="font-size: 40px; color: #2ecc71; margin-bottom: 15px;"></i>
                    <h3 style="color: #27ae60;">Excelente! Sem pendências.</h3>
                    <p style="color: #666; font-size: 14px;">Todos os relatórios referentes a <?php echo $meses[$mes_alvo] . ' / ' . $ano_alvo; ?> foram entregues dentro do prazo.</p>
                </div>
            <?php endif; ?>

            <div class="assinatura-box">
                <div class="assinatura-linha"></div>
                <div class="assinatura-texto">Coordenação / Direção Fatec</div>
            </div>
        </div>

    </main>
    <script src="assets/js/painel.js"></script>
</body>
</html>