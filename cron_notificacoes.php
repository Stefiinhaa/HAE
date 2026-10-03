<?php
// ==============================================================================
// TRAVA DE SEGURANÇA: Token Secreto via URL
// Só roda se acessar: sistemahae.page.gd/cron_notificacoes.php?token=HaeFatec2026
// ==============================================================================
$token_secreto = 'HaeFatec2026'; 

if (!isset($_GET['token']) || $_GET['token'] !== $token_secreto) {
    http_response_code(403);
    exit('Acesso restrito.');
}

date_default_timezone_set('America/Sao_Paulo');
$dia_hoje = (int)date('d');
$hoje_str = date('Y-m-d');

// Se passou do dia 11, o robô não precisa fazer nada o resto do mês
if ($dia_hoje > 11) {
    exit('Fora do periodo de cobranca.');
}

// ==============================================================================
// SISTEMA ANTI-SPAM (TRAVA DIÁRIA)
// Impede que o sistema mande vários e-mails se a página for acessada 2x no mesmo dia
// ==============================================================================
$arquivo_log = __DIR__ . '/ultimo_cron.txt';

if (file_exists($arquivo_log)) {
    $ultimo_disparo = trim(file_get_contents($arquivo_log));
    if ($ultimo_disparo === $hoje_str) {
        exit('OK: As notificações de hoje já foram enviadas mais cedo.');
    }
}

require __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/enviar_email.php';
require_once __DIR__ . '/enviar_push.php'; 

$mes_passado = date('m', strtotime('first day of last month'));
$ano_passado = date('Y', strtotime('first day of last month'));

$meses_ptbr = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
$nome_mes_passado = $meses_ptbr[$mes_passado];

try {
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
        // Salva a memória para o robô não procurar mais no banco hoje
        file_put_contents($arquivo_log, $hoje_str);
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
                <div style='font-family: Arial, sans-serif; color: #2c3e50; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
                    <h2 style='color: #f39c12; margin-top: 0;'>Aviso de Pendência HAE</h2>
                    <p>Olá, Prof(a). <strong>" . htmlspecialchars($nome) . "</strong>,</p>
                    <p>Consta em nosso sistema que o relatório mensal HAE referente a <strong>$nome_mes_passado/$ano_passado</strong> ainda não foi enviado.</p>
                    
                    <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #f39c12; margin: 15px 0;'>
                        <strong>Projeto:</strong> $titulo<br>
                        <strong>Prazo limite:</strong> Dia 10 do mês atual.
                    </div>
                    
                    <p>Por favor, acesse o portal e regularize sua situação o mais rápido possível para evitar o bloqueio de horas.</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='https://sistemahae.page.gd/enviar_relatorio.php' style='background-color: #f39c12; color: #ffffff; padding: 14px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 16px;'>Acessar Portal HAE</a>
                    </div>
                    <p style='margin-top: 20px; font-size: 12px; color: #7f8c8d; text-align: center;'>Este é um aviso automático ($dia_hojeº aviso).</p>
                </div>
            ";
            
            try { dispararEmailSistema($email, $nome, $assunto, $corpo_email); } catch (Exception $e) {}
            
            $titulo_push = "Relatório Atrasado ⚠️";
            $msg_push = "O relatório de $nome_mes_passado do projeto HAE está pendente. Envie até o dia 10!";
            $link_destino = "https://sistemahae.page.gd/enviar_relatorio.php";
            try { dispararPush($proj['professor_id'], $titulo_push, $msg_push, $link_destino); } catch (Exception $e) {}
        }
        
        file_put_contents($arquivo_log, $hoje_str); // Salva o dia para não repetir hoje
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
                <div style='font-family: Arial, sans-serif; color: #2c3e50; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
                    <h2 style='color: #c0392b; margin-top: 0;'>Relatório de Pendências HAE</h2>
                    <p>Olá, <strong>" . htmlspecialchars($gestor['nome']) . "</strong>,</p>
                    <p>O prazo para envio dos relatórios mensais referentes a <strong>$nome_mes_passado/$ano_passado</strong> foi encerrado no dia 10.</p>
                    
                    <p>Abaixo está a lista dos professores que <strong>NÃO</strong> enviaram seus relatórios, mesmo após as notificações automáticas diárias:</p>
                    
                    <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #c0392b; margin: 15px 0;'>
                        $lista_html
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='https://sistemahae.page.gd/relatorios_atrasados.php' style='background-color: #c0392b; color: #ffffff; padding: 14px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 16px;'>Ver Painel de Atrasos</a>
                    </div>
                </div>
            ";
            
            try { dispararEmailSistema($gestor['email'], $gestor['nome'], $assunto_direcao, $corpo_direcao); } catch (Exception $e) {}
        }
        
        file_put_contents($arquivo_log, $hoje_str);
        echo "Relatorio de inadimplencia enviado a direcao.";
    }

} catch (PDOException $e) {
    exit("Erro no cron: " . $e->getMessage());
}
?>