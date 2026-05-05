<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

$id_solicitacao = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Busca todos os dados da solicitação + dados do professor
$sql = "SELECT s.*, u.nome, u.data_admissao, u.tipo_contrato, u.formacao_academica, u.assinatura_path 
        FROM solicitacoes_hae s 
        JOIN usuarios u ON s.professor_id = u.id 
        WHERE s.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_solicitacao]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dados) {
    die("Solicitação não encontrada.");
}

// Formatação de datas e extração do Ano
$data_envio = date('d/m/Y', strtotime($dados['data_criacao']));
$ano_projeto = explode('/', $dados['semestre'])[1] ?? date('Y');
$data_admissao = date('d/m/Y', strtotime($dados['data_admissao']));

// Verificação dos checkboxes de contrato[cite: 4]
$check_determinado = $dados['tipo_contrato'] == 'Determinado' ? '( X )' : '(   )';
$check_indeterminado = $dados['tipo_contrato'] == 'Indeterminado' ? '( X )' : '(   )';

// Verificação do projeto anterior[cite: 4]
$check_ant_sim = $dados['projeto_anterior'] ? '( X )' : '(   )';
$check_ant_nao = !$dados['projeto_anterior'] ? '( X )' : '(   )';

// Caminho absoluto para a imagem da assinatura
$caminho_assinatura = $dados['assinatura_path'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>HAE - <?php echo $dados['nome']; ?></title>
    <style>
        /* Estilos base para renderizar como uma folha A4 responsiva */
        body { 
            background: #525659; 
            padding: 20px; 
            font-family: 'Arial', sans-serif; 
            margin: 0; 
            /* Removido o display:flex que estava quebrando o layout */
        }
        
        .page { 
            background: white; 
            max-width: 210mm; /* Mantém a proporção exata do A4 */
            width: 100%;
            min-height: 297mm; 
            padding: 20mm; 
            box-sizing: border-box; 
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            font-size: 14px;
            line-height: 1.5;
            color: #000;
            margin: 0 auto; /* Isso garante que a folha fique sempre centralizada */
            overflow-x: hidden;
        }

        h2, h3, h4 { text-align: center; margin-bottom: 15px; }
        p { margin-bottom: 10px; }
        
        .table-responsive { width: 100%; overflow-x: auto; margin-bottom: 20px; }
        .tabela-horas { width: 100%; border-collapse: collapse; min-width: 500px; }
        .tabela-horas th, .tabela-horas td { border: 1px solid #000; padding: 8px; text-align: left; }
        .tabela-horas th { background-color: #f2f2f2; }

        .assinatura-box { margin-top: 40px; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .assinatura-img { max-height: 100px; max-width: 250px; margin-bottom: -15px; }
        .linha-assinatura { width: 300px; max-width: 100%; border-top: 1px solid #000; margin-top: 20px; padding-top: 5px; }

        .parecer-box { border: 1px solid #000; padding: 15px; margin-top: 20px; margin-bottom: 20px; }
        .grid-parecer { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px; text-align: center; }

        /* Botão flutuante corrigido para nunca quebrar o layout */
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

        /* Responsividade focada no mobile */
        @media (max-width: 768px) {
            body { padding: 10px; }
            .page { padding: 15px; min-height: auto; }
            .grid-parecer { grid-template-columns: 1fr; gap: 40px; } /* Empilha assinaturas */
            
            /* Mantém o botão fixo, mas pega a largura toda no celular */
            .btn-imprimir {
                bottom: 10px; right: 10px; left: 10px; 
                width: calc(100% - 20px); 
                text-align: center;
            }
        }

        @media print {
            body { background: white; padding: 0; }
            .page { box-shadow: none; max-width: 100%; padding: 0; margin: 0; }
            .btn-imprimir { display: none; }
        }
    </style>
</head>
<body>

    <button class="btn-imprimir" onclick="window.print()">🖨️ Gerar / Salvar PDF</button>

    <div class="page">
        <h3>FORMULÁRIO PARA SOLICITAÇÃO DE PROJETO COM<br>HORA ATIVIDADE ESPECÍFICA – <?php echo $ano_projeto; ?></h3>
        
        <p><strong>Professor Responsável:</strong> <?php echo htmlspecialchars($dados['nome']); ?></p>
        <p>
            <strong>Data de admissão:</strong> <?php echo $data_admissao; ?> &nbsp;&nbsp;&nbsp; 
            <?php echo $check_determinado; ?> determinado &nbsp;&nbsp;&nbsp; 
            <?php echo $check_indeterminado; ?> indeterminado
        </p>
        <p><strong>Formação Acadêmica:</strong> <?php echo htmlspecialchars($dados['formacao_academica']); ?></p>
        
        <table class="tabela-horas" style="width: 50%;">
            <tr><th>Nº HAE Solicitada</th></tr>
            <tr><td style="text-align: center; font-weight: bold;"><?php echo $dados['quantidade_horas']; ?></td></tr>
        </table>

        <p><strong>Título do Projeto:</strong> <?php echo htmlspecialchars($dados['titulo_projeto']); ?></p>
        
        <p>
            <strong>Está relacionado com outro projeto desenvolvido em <?php echo $ano_projeto; ?>?</strong> 
            &nbsp;&nbsp; <?php echo $check_ant_nao; ?> Não &nbsp;&nbsp; <?php echo $check_ant_sim; ?> Sim
        </p>
        <?php if($dados['projeto_anterior']): ?>
            <p><strong>Se afirmativo, qual?</strong> <?php echo htmlspecialchars($dados['nome_projeto_anterior']); ?></p>
        <?php endif; ?>

        <p><strong>Objetivos/Meta(s) da Escola à(s) qual(is) o projeto está vinculado:</strong><br>
        <?php echo nl2br(htmlspecialchars($dados['objetivos_escola'])); ?></p>

        <table class="tabela-horas">
            <tr>
                <th style="width: 70%;">Carga Horária Semanal</th>
                <th><?php echo htmlspecialchars($dados['semestre']); ?></th>
            </tr>
            <tr><td>Horas aula</td><td><?php echo $dados['horas_aula']; ?></td></tr>
            <tr><td>Hora Atividade (50% das Horas-aula)</td><td><?php echo $dados['horas_atividade']; ?></td></tr>
            <tr><td>Hora – Atividade Específica do Projeto -</td><td><?php echo $dados['horas_especificas']; ?></td></tr>
            <tr><td><strong>Total Semanal</strong></td><td><strong><?php echo $dados['total_semanal']; ?></strong></td></tr>
            <tr><td><strong>Total Mensal (Total-Semanal x 4,5 Semanas)</strong></td><td><strong><?php echo $dados['total_mensal']; ?></strong></td></tr>
        </table>
        <p style="text-align: center; font-size: 12px; font-weight: bold;">OBS: O TOTAL NÃO PODERÁ ULTRAPASSAR ÀS 200 (duzentas) HORAS MENSAIS</p>

        <div class="parecer-box">
            <p><strong>Parecer do(a) coordenador(a):</strong></p>
            <div class="grid-parecer">
                <div>Data: ____/____/_______</div>
                <div>_________________________________________<br>Assinatura</div>
            </div>
            
            <p style="margin-top: 20px;"><strong>Parecer do(a) diretor(a):</strong></p>
            <p>Número de HAE(s) concedida(s) para desenvolvimento do Projeto: ______ HAE(s)</p>
            <div class="grid-parecer">
                <div>Data: ____/____/_______</div>
                <div>_________________________________________<br>Assinatura</div>
            </div>
        </div>

        <h4 style="margin-top: 30px;">APRESENTAÇÃO DO PROJETO</h4>
        
        <p><strong>1.- Título do Projeto:</strong> <?php echo htmlspecialchars($dados['titulo_projeto']); ?></p>
        <p><strong>2.- Professor Responsável:</strong> <?php echo htmlspecialchars($dados['nome']); ?></p>
        <p><strong>3.- Categoria:</strong> <?php echo $dados['categoria']; ?></p>
        <p><strong>4.- Justificativa:</strong><br><?php echo nl2br(htmlspecialchars($dados['justificativa'])); ?></p>
        <p><strong>5.- Objetivo:</strong><br><?php echo nl2br(htmlspecialchars($dados['objetivo'])); ?></p>
        <p><strong>6.- Metodologia:</strong><br><?php echo nl2br(htmlspecialchars($dados['metodologia'])); ?></p>
        <p><strong>7.- Envolvidos no Projeto:</strong><br><?php echo nl2br(htmlspecialchars($dados['envolvidos'])); ?></p>
        <p><strong>8.- Recurso(s) Necessário(s):</strong><br>
           <?php echo $dados['recursos_necessarios']; ?><br>
           <em>Detalhamento:</em> <?php echo nl2br(htmlspecialchars($dados['detalhamento_recursos'])); ?>
        </p>
        <p><strong>9.- Cronograma de execução:</strong><br><?php echo nl2br(htmlspecialchars($dados['cronograma'])); ?></p>
        <p><strong>10.- Resultados esperados:</strong><br><?php echo nl2br(htmlspecialchars($dados['resultados_esperados'])); ?></p>

        <!-- A Assinatura Dinâmica -->
        <div class="assinatura-box">
            <?php if (!empty($caminho_assinatura) && file_exists($caminho_assinatura)): ?>
                <img src="<?php echo $caminho_assinatura; ?>" alt="Assinatura do Professor" class="assinatura-img">
            <?php else: ?>
                <br><br><br>
            <?php endif; ?>
            <div class="linha-assinatura">Assinatura do Professor</div>
            <p><strong>Data:</strong> <?php echo $data_envio; ?></p>
        </div>

    </div>
</body>
</html>