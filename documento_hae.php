<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

$id_solicitacao = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Busca os dados da solicitação
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

// -------------------------------------------------------------------------
// BUSCA AS ASSINATURAS REAIS DE QUEM APROVOU NO SISTEMA
// -------------------------------------------------------------------------
$assinatura_coordenador = '';
if (!empty($dados['coordenador_id'])) {
    $stmt_coord = $pdo->prepare("SELECT assinatura_path FROM usuarios WHERE id = ?");
    $stmt_coord->execute([$dados['coordenador_id']]);
    $coord_data = $stmt_coord->fetch(PDO::FETCH_ASSOC);
    $assinatura_coordenador = $coord_data ? $coord_data['assinatura_path'] : '';
}

$assinatura_diretor = '';
if (!empty($dados['diretor_id'])) {
    $stmt_dir = $pdo->prepare("SELECT assinatura_path FROM usuarios WHERE id = ?");
    $stmt_dir->execute([$dados['diretor_id']]);
    $dir_data = $stmt_dir->fetch(PDO::FETCH_ASSOC);
    $assinatura_diretor = $dir_data ? $dir_data['assinatura_path'] : '';
}
// -------------------------------------------------------------------------

$data_envio = date('d/m/Y', strtotime($dados['data_criacao']));
$ano_projeto = explode('/', $dados['semestre'])[1] ?? date('Y');
$data_admissao = date('d/m/Y', strtotime($dados['data_admissao']));

// Formatação das Datas Individuais
$data_coord = ($dados['status_coordenador'] == 'Aprovado' && !empty($dados['data_aprovacao_coordenador'])) ? date('d/m/Y', strtotime($dados['data_aprovacao_coordenador'])) : '____/____/_______';
$data_dir = ($dados['status_diretor'] == 'Aprovado' && !empty($dados['data_aprovacao_diretor'])) ? date('d/m/Y', strtotime($dados['data_aprovacao_diretor'])) : '____/____/_______';

$check_determinado = $dados['tipo_contrato'] == 'Determinado' ? '( X )' : '(   )';
$check_indeterminado = $dados['tipo_contrato'] == 'Indeterminado' ? '( X )' : '(   )';
$check_ant_sim = $dados['projeto_anterior'] ? '( X )' : '(   )';
$check_ant_nao = !$dados['projeto_anterior'] ? '( X )' : '(   )';

$caminho_assinatura = $dados['assinatura_path'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>HAE - <?php echo $dados['nome']; ?></title>
    <style>
        body { background: #525659; padding: 20px; font-family: 'Arial', sans-serif; margin: 0; }
        
        /* Adicionado margin-bottom para separar as folhas na visualização da tela */
        .page { background: white; max-width: 210mm; width: 100%; min-height: 297mm; padding: 20mm; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.5); font-size: 14px; line-height: 1.5; color: #000; margin: 0 auto 30px auto; overflow-x: hidden; }
        
        h2, h3, h4 { text-align: center; margin-bottom: 15px; }
        p { margin-bottom: 10px; }
        .table-responsive { width: 100%; overflow-x: auto; margin-bottom: 20px; }
        .tabela-horas { width: 100%; border-collapse: collapse; min-width: 500px; }
        .tabela-horas th, .tabela-horas td { border: 1px solid #000; padding: 8px; text-align: left; }
        .tabela-horas th { background-color: #f2f2f2; }
        
        /* NOVO LAYOUT DO CABEÇALHO LADO A LADO */
        .info-topo { display: flex; justify-content: space-between; align-items: stretch; margin-bottom: 20px; }
        .dados-professor { flex: 1; padding-right: 20px; }
        .dados-professor p { margin-top: 0; margin-bottom: 10px; }
        .quadrado-hae { width: 160px; border: 1px solid #000; display: flex; flex-direction: column; text-align: center; }
        .titulo-quadrado { background-color: #f2f2f2; border-bottom: 1px solid #000; padding: 8px 5px; font-weight: bold; font-size: 11px; text-transform: uppercase; }
        .valor-quadrado { padding: 15px; font-size: 24px; font-weight: bold; flex: 1; display: flex; align-items: center; justify-content: center; }

        .parecer-box { border: 1px solid #000; padding: 15px;  }
        .grid-parecer { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; text-align: center; }
        .assinatura-responsavel { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 70px; }
        .assinatura-responsavel img { max-height: 55px; max-width: 200px; margin-bottom: -10px; }
        .linha-assinatura-pequena { width: 100%; border-top: 1px solid #000; padding-top: 5px; }
        #td_centralizado{ text-align: center; }
        .assinatura-box { margin-top: 40px; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .assinatura-img { max-height: 100px; max-width: 250px; margin-bottom: -15px; }
        .linha-assinatura { width: 300px; max-width: 100%; border-top: 1px solid #000; margin-top: 20px; padding-top: 5px; }

        .texto-parecer { color: #333; margin-left: 5px; font-style: italic; }

        .btn-imprimir { position: fixed; bottom: 20px; right: 20px; background: #b20000; color: white; border: none; padding: 15px 25px; border-radius: 5px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.3); z-index: 1000; transition: 0.3s; }
        .btn-imprimir:hover { background: #8a0000; }
        
        /* Configuração de Impressão */
        @media print { 
            body { background: white; padding: 0; } 
            /* Retira sombras e margens, e força a quebra de página automática entre os blocos .page */
            .page { box-shadow: none; max-width: 100%; padding: 0; margin: 0; min-height: auto; page-break-after: always; } 
            .page:last-child { page-break-after: auto; }
            .btn-imprimir { display: none; } 
        }

         .header-doc { text-align: center; margin-bottom: 10px; }
        .header-doc img { max-height: 70px; }
        .header-doc h2 { margin: 0; font-size: 20px; font-weight: bold; }

         .titulo-fatec{
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

    <button class="btn-imprimir" onclick="window.print()">🖨️ Gerar / Salvar PDF</button>

    <div class="page">
 <div class="header-doc">
            <!-- Puxando a logo do CPS diretamente da pasta Img -->
            <img src="img/header-cps-documento.jpeg" alt="Logo CPS Fatec"> <hr>
            <h4 class="titulo-fatec">Faculdade de Tecnologia de Garça “Deputado Júlio Julinho Marcondes de Moura”</h4>
    
        </div>

        <h3>FORMULÁRIO PARA SOLICITAÇÃO DE PROJETO COM<br>HORA ATIVIDADE ESPECÍFICA – <?php echo $ano_projeto; ?></h3>
        
        <div class="info-topo">
            <div class="dados-professor">
                <p><strong>Professor Responsável:</strong> <?php echo htmlspecialchars($dados['nome']); ?></p>
                <p>
                    <strong>Data de admissão:</strong> <?php echo $data_admissao; ?> &nbsp;&nbsp;&nbsp; 
                    <?php echo $check_determinado; ?> determinado &nbsp;&nbsp;&nbsp; 
                    <?php echo $check_indeterminado; ?> indeterminado
                </p>
                <p><strong>Formação Acadêmica:</strong> <?php echo htmlspecialchars($dados['formacao_academica']); ?></p>
            </div>
            
            <div class="quadrado-hae">
                <div class="titulo-quadrado">Nº HAE Solicitada </div>
                <div class="valor-quadrado"><?php echo $dados['quantidade_horas']; ?></div>
            </div>
        </div>

        <p><strong>Título do Projeto:</strong> <?php echo htmlspecialchars($dados['titulo_projeto']); ?></p>
        <p><strong>Está relacionado com outro projeto desenvolvido em <?php echo $ano_projeto; ?>?</strong> &nbsp;&nbsp; <?php echo $check_ant_nao; ?> Não &nbsp;&nbsp; <?php echo $check_ant_sim; ?> Sim</p>
        <?php if($dados['projeto_anterior']): ?>
            <p><strong>Se afirmativo, qual?</strong> <?php echo htmlspecialchars($dados['nome_projeto_anterior']); ?></p>
        <?php endif; ?>

        <p><strong>Objetivos/Meta(s) da Escola à(s) qual(is) o projeto está vinculado:</strong><br>
        <?php echo nl2br(htmlspecialchars($dados['objetivos_escola'])); ?></p>

        <div class="table-responsive">
            <table class="tabela-horas">
                <tr><th style="width: 70%;">Carga Horária Semanal</th><th id="td_centralizado"><?php echo htmlspecialchars($dados['semestre']); ?></th></tr>
                <tr><td>Horas aula</td><td id="td_centralizado"><?php echo $dados['horas_aula']; ?></td></tr>
                <tr><td>Hora Atividade (50% das Horas-aula)</td><td id="td_centralizado"><?php echo $dados['horas_atividade']; ?></td></tr>
                <tr><td>Hora – Atividade Específica do Projeto -</td><td id="td_centralizado"><?php echo $dados['horas_especificas']; ?></td></tr>
                <tr><td><strong>Total Semanal</strong></td><td id="td_centralizado"><strong><?php echo $dados['total_semanal']; ?></strong></td></tr>
                <tr><td><strong>Total Mensal (Total-Semanal x 4,5 Semanas)</strong></td><td id="td_centralizado"><strong><?php echo $dados['total_mensal']; ?></strong></td></tr>
            </table>
        </div>

        <div class="parecer-box">
            <p>
                <strong>Parecer do(a) coordenador(a):</strong> 
                <span class="texto-parecer"><?php echo !empty($dados['parecer_coordenador']) ? htmlspecialchars($dados['parecer_coordenador']) : '__________________________________________________________________'; ?></span>
            </p>
            <div class="grid-parecer">
                <div>Data: <?php echo $data_coord; ?></div>
                <div class="assinatura-responsavel">
                    <?php if ($dados['status_coordenador'] == 'Aprovado' && !empty($assinatura_coordenador) && file_exists($assinatura_coordenador)): ?>
                        <img src="<?php echo $assinatura_coordenador; ?>" alt="Assinatura Coordenador">
                    <?php else: ?><br><?php endif; ?>
                    <div class="linha-assinatura-pequena">Assinatura</div>
                </div>
            </div>
            
            <p style="margin-top: 25px;">
                <strong>Parecer do(a) diretor(a):</strong>
                <span class="texto-parecer"><?php echo !empty($dados['parecer_diretor']) ? htmlspecialchars($dados['parecer_diretor']) : '__________________________________________________________________'; ?></span>
            </p>
            <p>Número de HAE(s) concedida(s) para desenvolvimento do Projeto: <strong><?php echo ($dados['status_diretor'] == 'Aprovado') ? $dados['quantidade_horas'] : '______'; ?></strong> HAE(s)</p>
            <div class="grid-parecer">
                <div>Data: <?php echo $data_dir; ?></div>
                <div class="assinatura-responsavel">
                    <?php if ($dados['status_diretor'] == 'Aprovado' && !empty($assinatura_diretor) && file_exists($assinatura_diretor)): ?>
                        <img src="<?php echo $assinatura_diretor; ?>" alt="Assinatura Diretor">
                    <?php else: ?><br><?php endif; ?>
                    <div class="linha-assinatura-pequena">Assinatura</div>
                </div>
            </div>
        </div>
    </div> <div class="page" style="page-break-before: always;">

     <div class="header-doc">
            <!-- Puxando a logo do CPS diretamente da pasta Img -->
            <img src="img/header-cps-documento.jpeg" alt="Logo CPS Fatec"> <hr>
            <h4 class="titulo-fatec">Faculdade de Tecnologia de Garça “Deputado Júlio Julinho Marcondes de Moura”</h4>
            <h2>APRESENTAÇÃO DO PROJETO</h2>
        </div>
      
        <p><strong>1.- Título do Projeto:</strong> <?php echo htmlspecialchars($dados['titulo_projeto']); ?></p>
        <p><strong>2.- Professor Responsável:</strong> <?php echo htmlspecialchars($dados['nome']); ?></p>
        <p><strong>3.- Categoria:</strong> <?php echo htmlspecialchars($dados['categoria']); ?></p>
        <p><strong>4.- Justificativa:</strong><br><?php echo nl2br(htmlspecialchars($dados['justificativa'])); ?></p>
        <p><strong>5.- Objetivo:</strong><br><?php echo nl2br(htmlspecialchars($dados['objetivo'])); ?></p>
        <p><strong>6.- Metodologia:</strong><br><?php echo nl2br(htmlspecialchars($dados['metodologia'])); ?></p>
        <p><strong>7.- Envolvidos no Projeto:</strong><br><?php echo nl2br(htmlspecialchars($dados['envolvidos'])); ?></p>
        <p><strong>8.- Recurso(s) Necessário(s):</strong><br><?php echo $dados['recursos_necessarios']; ?><br><em>Detalhamento:</em> <?php echo nl2br(htmlspecialchars($dados['detalhamento_recursos'])); ?></p>
        <p><strong>9.- Cronograma de execução:</strong><br><?php echo nl2br(htmlspecialchars($dados['cronograma'])); ?></p>
        <p><strong>10.- Resultados esperados:</strong><br><?php echo nl2br(htmlspecialchars($dados['resultados_esperados'])); ?></p>

        <div class="assinatura-box">
            <?php if (!empty($caminho_assinatura) && file_exists($caminho_assinatura)): ?>
                <img src="<?php echo $caminho_assinatura; ?>" alt="Assinatura do Professor" class="assinatura-img">
            <?php else: ?><br><br><br><?php endif; ?>
            <div class="linha-assinatura">Assinatura do Professor</div>
            <p><strong>Data:</strong> <?php echo $data_envio; ?></p>
        </div>
    </div> </body>
</html>