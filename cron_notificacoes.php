<?php
// ==============================================================================
// NOVA TRAVA DE SEGURANÇA: Token Secreto via URL
// Só roda se acessar: sistemahae.page.gd/cron_notificacoes.php?token=HaeFatec2026
// ==============================================================================
$token_secreto = 'HaeFatec2026'; // Você pode mudar essa senha se quiser

if (!isset($_GET['token']) || $_GET['token'] !== $token_secreto) {
    // Se tentarem acessar sem o token, o sistema bloqueia
    http_response_code(403);
    exit('Acesso restrito.');
}

// Define o fuso horário para garantir que o dia mude na hora certa
date_default_timezone_set('America/Sao_Paulo');

require __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/enviar_email.php';
require_once __DIR__ . '/enviar_push.php'; // ADICIONADO: Motor de notificações Push

$dia_hoje = (int)date('d');

// Se passou do dia 11, o robô não precisa fazer nada o resto do mês
if ($dia_hoje > 11) {
    exit('Fora do periodo de cobranca.');
}

// Descobre qual é o mês e o ano passado (o mês que está sendo cobrado)
$mes_passado = date('m', strtotime('first day of last month'));
$ano_passado = date('Y', strtotime('first day of last month'));

$meses_ptbr = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
$nome_mes_passado = $meses_ptbr[$mes_passado];

try {
    // ADICIONADO: 's.professor_id' na query para sabermos para quem enviar o Push
    $sql_pendentes = "
        SELECT s.id as projeto_id, s.professor_id, s.titulo_projeto, u.nome as professor_nome, u.email as professor_email
        FROM solicitacoes_hae s
        JOIN usuarios u ON s.professor_id = u.id
        WHERE s.status_aprovacao = 'Aprovado'
        AND NOT EXISTS (
            SELECT 1 FROM relatorios_hae r 
            WHERE r.solicitacao_id = s.id 
            AND r.mes_referencia = ? 
            AND r.ano_referencia = ?
        )
    ";
    
    $stmt = $pdo->prepare($sql_pendentes);
    $stmt->execute([$mes_passado, $ano_passado]);
    $projetos_atrasados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($projetos_atrasados)) {
        exit('Nenhum relatorio pendente.');
    }

    // REGRA 1: Do dia 1 ao 10 - Cobrar os Professores diariamente
    if ($dia_hoje >= 1 && $dia_hoje <= 10) {
        
        $assunto = "Aviso Automático: Relatório HAE de $nome_mes_passado Pendente";
        
        foreach ($projetos_atrasados as $proj) {
            $nome = $proj['professor_nome'];
            $email = $proj['professor_email'];
            $titulo = $proj['titulo_projeto'];

            $corpo_email = "
                <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px;'>
                    <div style='background-color: #f39c12; padding: 20px; text-align: center; color: white;'>
                        <h2 style='margin: 0;'>Aviso de Pendência HAE</h2>
                    </div>
                    <div style='padding: 20px;'>
                        <h3 style='color: #f39c12; margin-top: 0;'>Olá, Prof(a). $nome.</h3>
                        <p>Consta em nosso sistema que o relatório mensal HAE referente a <strong>$nome_mes_passado/$ano_passado</strong> ainda não foi enviado.</p>
                        
                        <div style='background: #fffdf5; padding: 15px; border-left: 4px solid #f39c12; margin: 15px 0;'>
                            <strong>Projeto:</strong> $titulo<br>
                            <strong>Prazo limite:</strong> Dia 10 do mês atual.
                        </div>
                        
                        <p>Por favor, acesse o portal e regularize sua situação o mais rápido possível para evitar o bloqueio de horas.</p>
                        
                        <div style='text-align: center; margin: 20px 0;'>
                            <img src='cid:img_link_portal' alt='Link de Acesso' style='max-width: 250px; border: 1px solid #ccc;'>
                        </div>
                        <p style='font-size: 12px; color: #777; text-align: center;'>Este é um aviso automático ($dia_hojeº aviso).</p>
                    </div>
                </div>
            ";
            
            $lista_imagens = [['path' => __DIR__ . '/img/link_acesso.jpeg', 'cid' => 'img_link_portal']];
            dispararEmailSistema($email, $nome, $assunto, $corpo_email, $lista_imagens);
            
            // =========================================================================
            // NOVO: DISPARO DA NOTIFICAÇÃO PUSH DIRETO PRO CELULAR/NAVEGADOR DO PROFESSOR
            // =========================================================================
            $titulo_push = "Relatório Atrasado ⚠️";
            $msg_push = "O relatório de $nome_mes_passado do projeto HAE está pendente. Envie até o dia 10!";
            $link_destino = "https://sistemahae.page.gd/enviar_relatorio.php";
            
            dispararPush($proj['professor_id'], $titulo_push, $msg_push, $link_destino);
            // =========================================================================
        }
        
        echo "Cobrancas enviadas para os professores com sucesso.";
    }

    // REGRA 2: Dia 11 - Enviar o relatório de inadimplentes para Direção/Coordenação
    if ($dia_hoje == 11) {
        
        $stmt_gestores = $pdo->query("SELECT nome, email FROM usuarios WHERE funcao IN ('Diretor', 'Coordenador')");
        $gestores = $stmt_gestores->fetchAll(PDO::FETCH_ASSOC);

        $lista_html = "<ul>";
        foreach ($projetos_atrasados as $proj) {
            $lista_html .= "<li><strong>" . $proj['professor_nome'] . "</strong> - " . $proj['titulo_projeto'] . "</li>";
        }
        $lista_html .= "</ul>";

        $assunto_direcao = "Relatório Atrasados HAE - $nome_mes_passado/$ano_passado";

        foreach ($gestores as $gestor) {
            $corpo_direcao = "
                <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px;'>
                    <div style='background-color: #c0392b; padding: 20px; text-align: center; color: white;'>
                        <h2 style='margin: 0;'>Relatório de Pendências HAE</h2>
                    </div>
                    <div style='padding: 20px;'>
                        <h3 style='color: #c0392b; margin-top: 0;'>Olá, " . $gestor['nome'] . ".</h3>
                        <p>O prazo para envio dos relatórios mensais referentes a <strong>$nome_mes_passado/$ano_passado</strong> foi encerrado no dia 10.</p>
                        
                        <p>Abaixo está a lista dos professores que <strong>NÃO</strong> enviaram seus relatórios, mesmo após as notificações automáticas diárias:</p>
                        
                        <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #c0392b; margin: 15px 0;'>
                            $lista_html
                        </div>
                        
                        <p>Para visualizar no sistema, acesse a aba 'Relatórios Atrasados'.</p>
                    </div>
                </div>
            ";
            
            dispararEmailSistema($gestor['email'], $gestor['nome'], $assunto_direcao, $corpo_direcao);
        }
        
        echo "Relatorio de inadimplencia enviado a direcao.";
    }

} catch (PDOException $e) {
    exit("Erro no cron: " . $e->getMessage());
}
?>