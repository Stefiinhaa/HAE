<?php
session_start();
require 'config/conexao.php';

// =========================================================================
// BUSCA AS CONFIGURAÇÕES GLOBAIS NO BANCO DE DADOS
// =========================================================================
$stmt_conf = $pdo->query("SELECT chave, valor FROM configuracoes");
$config_db = $stmt_conf->fetchAll(PDO::FETCH_KEY_PAIR);

$ano_eleitoral = ($config_db['ano_eleitoral'] === '1'); 
$logo_institucional = $config_db['logo_institucional'] ?? 'img/header-cps-documento.jpeg';
// =========================================================================

// Segurança: Apenas logados podem acessar
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

$id_relatorio = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$usuario_logado_id = $_SESSION['usuario_id'];
$funcao_logada = $_SESSION['usuario_funcao'];

// Busca todos os dados do relatório, cruzando com o projeto e o professor
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
        /* Reset básico para evitar vazamento de bordas na largura 100% */
        * { box-sizing: border-box; }

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
            padding: 15mm; /* Espaçamento interno da folha na visualização */
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            font-size: 15px; 
            line-height: 1.6;
            color: #000;
            margin: 0 auto; 
            position: relative;
        }

        /* Cabeçalho com Logo e Título */
        .header-doc { text-align: center; margin-bottom: 20px; }
        .header-doc img { max-height: 70px; }
        .header-doc hr { margin: 5px 0; border: 0; border-top: 1px solid #ccc; }
        .titulo-fatec { margin-bottom: 15px; font-size: 14px; margin-top: 5px; font-weight: bold; }
        .header-doc h2 { margin: 0; font-size: 20px; font-weight: bold; text-transform: uppercase; }

        /* Tabela e Caixas de Texto (Garantindo que fiquem dentro do limite da folha) */
        .info-table { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .info-table td { padding: 6px 10px; vertical-align: top; border: 1px solid #000; }

        .campo-texto { margin-bottom: 10px; text-align: justify; border: 1px solid #000; padding: 6px 10px; width: 100%; }
        
        .acoes-realizadas { margin-top: 15px; text-align: justify; line-height: 1.7; min-height: 200px; border: 1px solid #000; padding: 10px; width: 100%; }

        .assinatura-box { text-align: center; margin-top: 60px; }
        .assinatura-img { max-height: 120px; max-width: 300px; margin-bottom: -10px; }
        .linha-assinatura { display: inline-block; width: 350px; border-top: 1px solid #000; padding-top: 5px; font-size: 15px; }
        
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

        /* REGRAS OFICIAIS DE IMPRESSÃO */
        @media print {
            @page {
                margin: 15mm; 
                size: A4 portrait;
            }
            body { background: white; padding: 0; margin: 0; }
            .page { 
                box-shadow: none; 
                width: 100%; 
                max-width: none; 
                padding: 0; 
                margin: 0; 
                border: none;
                min-height: auto;
            }
            .btn-imprimir { display: none !important; }
        }
    </style>
<!-- INTEGRAÇÃO ONESIGNAL (PUSH NOTIFICATIONS) -->
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
  window.OneSignalDeferred = window.OneSignalDeferred || [];
  OneSignalDeferred.push(async function(OneSignal) {
    await OneSignal.init({
      appId: "f3a9b7ad-ba4b-420c-8290-99f87501f1a3",
      safari_web_id: "web.onesignal.auto.sua_chave_safari_se_houver",
      notifyButton: {
        enable: true,
      },
    });
    OneSignal.login("<?php echo $_SESSION['usuario_id']; ?>");
  });
</script>
</head>
<body>

    <button class="btn-imprimir" onclick="window.print()">🖨️ Gerar / Salvar PDF</button>

    <div class="page">
        
    <div class="header-doc">
            <?php if (!$ano_eleitoral): ?>
                <img src="<?php echo htmlspecialchars($logo_institucional); ?>?v=<?php echo time(); ?>" alt="Logo CPS Fatec">
            <?php else: ?>
                <!-- Bloco vazio para manter a estrutura e o espaçamento -->
                <div style="height: 70px; width: 100%;"></div> 
            <?php endif; ?>
            <hr>
            <div class="titulo-fatec">Faculdade de Tecnologia de Garça “Deputado Júlio Julinho Marcondes de Moura”</div>
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

        <div class="assinatura-box">
            <?php if (!empty($caminho_assinatura) && file_exists($caminho_assinatura) && $dados['status'] == 'Publicado'): ?>
                <img src="<?php echo $caminho_assinatura; ?>" alt="Assinatura Professor" class="assinatura-img">
            <?php else: ?>
                <div style="height: 120px;"></div>
            <?php endif; ?>
            <br>
            <span class="linha-assinatura">
                <?php echo htmlspecialchars($dados['professor_nome']); ?>
            </span>
        </div>

    </div>
</body>
</html>