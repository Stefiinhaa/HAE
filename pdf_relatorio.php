<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas logados podem acessar
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

$id_relatorio = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$usuario_logado_id = $_SESSION['usuario_id'];
$funcao_logada = $_SESSION['usuario_funcao'];

// Busca todos os dados do relatório, cruzando com o projeto e o professor
$sql = "SELECT r.*, 
               s.titulo_projeto, s.quantidade_horas, s.semestre, s.professor_id,
               u.nome AS professor_nome, u.assinatura_path 
        FROM relatorios_hae r
        JOIN solicitacoes_hae s ON r.solicitacao_id = s.id
        JOIN usuarios u ON s.professor_id = u.id
        WHERE r.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_relatorio]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dados) {
    die("Relatório não encontrado.");
}

// Segurança extra: Se for professor, só pode ver o próprio relatório
if ($funcao_logada == 'Professor' && $dados['professor_id'] != $usuario_logado_id) {
    die("Você não tem permissão para visualizar este relatório.");
}

$meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];

$mes_nome = $meses[$dados['mes_referencia']];
$ano = $dados['ano_referencia'];
$data_envio = date('d/m/Y', strtotime($dados['data_envio']));

// Caminho da assinatura do professor
$caminho_assinatura = $dados['assinatura_path'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório HAE - <?php echo htmlspecialchars($dados['professor_nome']); ?></title>
    <style>
        /* Estilos base para a folha A4 */
        body { 
            background: #525659; 
            padding: 20px; 
            font-family: 'Arial', sans-serif; 
            margin: 0; 
        }
        
        .page { 
            background: white; 
            max-width: 210mm; /* Proporção A4 */
            width: 100%;
            min-height: 297mm; 
            padding: 20mm; 
            box-sizing: border-box; 
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            font-size: 14px;
            line-height: 1.6;
            color: #000;
            margin: 0 auto; 
            position: relative;
        }

        .header-doc { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .header-doc h2 { margin: 0 0 10px 0; font-size: 18px; text-transform: uppercase; }
        .header-doc h3 { margin: 0; font-size: 16px; font-weight: normal; }

        .info-box { border: 1px solid #000; padding: 15px; margin-bottom: 25px; background: #fafafa; }
        .info-box p { margin: 5px 0; }
        
        .section-title { font-weight: bold; font-size: 15px; text-transform: uppercase; margin-bottom: 15px; margin-top: 30px; background: #eee; padding: 5px 10px; border-left: 4px solid #b20000; }
        
        .conteudo-acoes { text-align: justify; padding: 10px; border: 1px solid #ddd; min-height: 300px; }

        .assinaturas-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 80px; text-align: center; }
        .assinatura-box { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; }
        .assinatura-img { max-height: 80px; max-width: 200px; margin-bottom: 5px; }
        .linha { width: 100%; border-top: 1px solid #000; padding-top: 5px; font-size: 12px; font-weight: bold; }

        /* Badge de status no topo (escondido na impressão) */
        .status-badge { position: absolute; top: 20mm; right: 20mm; padding: 5px 15px; font-weight: bold; border-radius: 4px; font-size: 12px; border: 1px solid #000; }
        
        .btn-imprimir {
            position: fixed; 
            bottom: 20px; 
            right: 20px; 
            background: #b20000; 
            color: white; 
            border: none; 
            padding: 15px 25px;
            border-radius: 5px; 
            font-weight: bold; 
            cursor: pointer; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            z-index: 1000;
            transition: 0.3s;
        }
        .btn-imprimir:hover { background: #8a0000; }

        @media print {
            body { background: white; padding: 0; }
            .page { box-shadow: none; max-width: 100%; padding: 0; margin: 0; border: none; }
            .btn-imprimir, .status-badge { display: none !important; }
            .info-box { background: transparent; }
            .section-title { border-left-color: #000; } /* Ajusta para P&B caso imprimam monocromático */
        }
    </style>
</head>
<body>

    <button class="btn-imprimir" onclick="window.print()">🖨️ Gerar / Salvar PDF</button>

    <div class="page">
        
        <!-- Indicador de Status só para visualização na tela -->
        <div class="status-badge" style="<?php echo $dados['status'] == 'Publicado' ? 'color:#0f5132; border-color:#0f5132;' : 'color:#856404; border-color:#856404;'; ?>">
            <?php echo strtoupper($dados['status']); ?>
        </div>

        <div class="header-doc">
            <h2>Relatório Mensal - Hora Atividade Específica (HAE)</h2>
            <h3>Fatec - Faculdade de Tecnologia</h3>
        </div>

        <div class="info-box">
            <p><strong>Professor(a):</strong> <?php echo htmlspecialchars($dados['professor_nome']); ?></p>
            <p><strong>Projeto / Atividade:</strong> <?php echo htmlspecialchars($dados['titulo_projeto']); ?></p>
            <p><strong>Semestre / Ano do Projeto:</strong> <?php echo htmlspecialchars($dados['semestre']); ?></p>
            <p><strong>Carga Horária Aprovada (Mensal):</strong> <?php echo $dados['quantidade_horas']; ?> horas</p>
            <p><strong>Mês/Ano de Referência do Relatório:</strong> <?php echo mb_strtoupper($mes_nome . ' / ' . $ano, 'UTF-8'); ?></p>
        </div>

        <div class="section-title">Ações e Atividades Realizadas no Período</div>
        
        <div class="conteudo-acoes">
            <?php echo nl2br(htmlspecialchars($dados['acoes_realizadas'])); ?>
        </div>

        <p style="margin-top: 20px; text-align: right; font-size: 13px;">
            Documento gerado e enviado ao sistema em: <strong><?php echo $data_envio; ?></strong>
        </p>

        <!-- Bloco de Assinaturas (Padrão Oficial) -->
        <div class="assinaturas-grid">
            
            <div class="assinatura-box">
                <?php if (!empty($caminho_assinatura) && file_exists($caminho_assinatura) && $dados['status'] == 'Publicado'): ?>
                    <img src="<?php echo $caminho_assinatura; ?>" alt="Assinatura Professor" class="assinatura-img">
                <?php else: ?>
                    <div style="height: 80px;"></div> <!-- Espaço em branco se não tiver assinatura ou for rascunho -->
                <?php endif; ?>
                <div class="linha">Assinatura do(a) Professor(a)</div>
            </div>

            <div class="assinatura-box">
                <div style="height: 80px;"></div>
                <div class="linha">Visto da Coordenação</div>
            </div>

            <div class="assinatura-box">
                <div style="height: 80px;"></div>
                <div class="linha">Visto da Direção</div>
            </div>

        </div>

    </div>
</body>
</html>