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
// ATENÇÃO: Adicionado 's.envolvidos' para puxar do banco de dados e exibir no relatório
$sql = "SELECT r.*, 
               s.titulo_projeto, s.quantidade_horas, s.semestre, s.professor_id, s.envolvidos,
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
            padding: 25mm 20mm; /* Espaçamento interno da folha (margens) */
            box-sizing: border-box; 
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            font-size: 15px; /* Tamanho de fonte ideal para leitura impressa */
            line-height: 1.6;
            color: #000;
            margin: 0 auto; 
            position: relative;
        }

        .header-doc { text-align: center; margin-bottom: 40px; }
        .header-doc img { width: 100%; height: 100vh; max-height: 80px; margin-bottom: 15px; }
        .header-doc h2 { margin: 0; font-size: 20px; font-weight: bold; }

        /* Tabela invisível para deixar Período e Quantidade lado a lado */
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px 0; vertical-align: top; }

        .campo-texto { margin-bottom: 15px; text-align: justify; }
        
        .acoes-realizadas { margin-top: 30px; text-align: justify; line-height: 1.7; min-height: 200px; }

        .assinatura-box { text-align: center; margin-top: 80px; }
        .assinatura-img { max-height: 120px; max-width: 300px; margin-bottom: -10px; }
        .linha-assinatura { display: inline-block; width: 350px; border-top: 1px solid #000; padding-top: 5px; font-size: 15px; }

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
        }
    </style>
</head>
<body>

    <button class="btn-imprimir" onclick="window.print()">🖨️ Gerar / Salvar PDF</button>

    <div class="page">
        

        <div class="header-doc">
            <!-- Puxando a logo do CPS diretamente da pasta Img -->
            <img src="img/header-cps-documento.jpeg" alt="Logo CPS Fatec">
            <h2>Relatório de H.A.E.</h2>
        </div>

        <table class="info-table">
            <tr>
                <td style="width: 50%;"><strong>Período:</strong> <?php echo $mes_nome; ?> de <?php echo $ano; ?></td>
                <td style="width: 50%;"><strong>Quantidade de HAE:</strong> <?php echo $dados['quantidade_horas']; ?></td>
            </tr>
        </table>

        <div class="campo-texto">
            <strong>Título do Projeto:</strong> <?php echo htmlspecialchars($dados['titulo_projeto']); ?>
        </div>

        <div class="campo-texto">
            <strong>Professor Responsável:</strong> <?php echo mb_strtoupper(htmlspecialchars($dados['professor_nome']), 'UTF-8'); ?>
        </div>

        <div class="campo-texto">
            <strong>Envolvidos no Projeto:</strong> <?php echo htmlspecialchars($dados['envolvidos']); ?>
        </div>

        <div class="acoes-realizadas">
            <strong>Ações Realizadas:</strong><br>
            <?php echo nl2br(htmlspecialchars($dados['acoes_realizadas'])); ?>
        </div>

        <!-- Bloco de Assinatura Centralizado -->
        <div class="assinatura-box">
            <?php if (!empty($caminho_assinatura) && file_exists($caminho_assinatura) && $dados['status'] == 'Publicado'): ?>
                <img src="<?php echo $caminho_assinatura; ?>" alt="Assinatura Professor" class="assinatura-img">
            <?php else: ?>
                <div style="height: 120px;"></div> <!-- Espaço em branco se não tiver assinatura ou for rascunho -->
            <?php endif; ?>
            <br>
            <span class="linha-assinatura">
                <?php echo htmlspecialchars($dados['professor_nome']); ?>
            </span>
        </div>

    </div>
</body>
</html>