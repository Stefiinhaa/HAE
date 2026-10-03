<?php
session_start();
require 'config/conexao.php';
require_once 'enviar_email.php';
require_once 'enviar_push.php';

// Segurança: Apenas Coordenador ou Diretor acessam
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_funcao'], ['Coordenador', 'Diretor'])) {
    header("Location: painel.php");
    exit;
}

$funcao_logada = $_SESSION['usuario_funcao'];
$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    $solicitacao_id = $_POST['solicitacao_id'];
    $return_url = isset($_POST['return_url']) ? trim($_POST['return_url']) : '';
    
    $acao_post = $_POST['acao'];
    if ($acao_post == 'aprovar') {
        $novo_status_individual = 'Aprovado';
    } elseif ($acao_post == 'devolver') {
        $novo_status_individual = 'Devolvido';
    } else {
        $novo_status_individual = 'Rejeitado';
    }
    
    $horas_aprovadas = $_POST['horas_aprovadas'] ?? 0;
    $parecer = trim($_POST['parecer']);
    $data_hoje = ($novo_status_individual == 'Aprovado') ? date('Y-m-d') : null;

    $tem_prazo = false;
    $prazo_data = "";
    $prazo_hora = "";
    
    // DATA DE INÍCIO DOS RELATÓRIOS (DIRETOR)
    $data_inicio_relatorios = null;
    if ($acao_post == 'aprovar' && $funcao_logada == 'Diretor') {
        $data_inicio_relatorios = !empty($_POST['data_inicio_relatorios']) ? $_POST['data_inicio_relatorios'] : null;
    }
    
    if ($acao_post == 'devolver') {
        if (!empty($_POST['prazo_data']) && !empty($_POST['prazo_hora'])) {
            $tem_prazo = true;
            $prazo_data = date('d/m/Y', strtotime($_POST['prazo_data']));
            $prazo_hora = $_POST['prazo_hora'];
            
            $parecer .= "\n\n[ PRAZO PARA CORREÇÃO ]\nO projeto deverá ser ajustado e submetido novamente no portal até o dia $prazo_data às $prazo_hora.";
        }
    }

    try {
        $stmt_current = $pdo->prepare("SELECT status_coordenador, status_diretor FROM solicitacoes_hae WHERE id = ?");
        $stmt_current->execute([$solicitacao_id]);
        $current = $stmt_current->fetch(PDO::FETCH_ASSOC);

        $status_coord = $current['status_coordenador'];
        $status_dir = $current['status_diretor'];

        if ($funcao_logada == 'Coordenador') {
            $status_coord = $novo_status_individual;
            $sql = "UPDATE solicitacoes_hae SET status_coordenador = ?, parecer_coordenador = ?, data_aprovacao_coordenador = ?, coordenador_id = ?, horas_aprovadas = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$status_coord, $parecer, $data_hoje, $usuario_id, $horas_aprovadas, $solicitacao_id]);
        } else if ($funcao_logada == 'Diretor') {
            $status_dir = $novo_status_individual;
            $sql = "UPDATE solicitacoes_hae SET status_diretor = ?, parecer_diretor = ?, data_aprovacao_diretor = ?, diretor_id = ?, horas_aprovadas = ?, data_inicio_relatorios = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$status_dir, $parecer, $data_hoje, $usuario_id, $horas_aprovadas, $data_inicio_relatorios, $solicitacao_id]);
        }

        $global_status = 'Pendente';
        if ($status_coord == 'Rejeitado' || $status_dir == 'Rejeitado') {
            $global_status = 'Rejeitado'; 
        } else if ($status_coord == 'Devolvido' || $status_dir == 'Devolvido') {
            $global_status = 'Devolvido'; 
        } else if ($status_coord == 'Aprovado' && $status_dir == 'Aprovado') {
            $global_status = 'Aprovado';  
        }

        $pdo->prepare("UPDATE solicitacoes_hae SET status_aprovacao = ? WHERE id = ?")->execute([$global_status, $solicitacao_id]);
        
        // NOTIFICAR DIRETOR
        if ($funcao_logada == 'Coordenador' && $novo_status_individual == 'Aprovado') {
            $stmt_dir = $pdo->query("SELECT id, nome, email FROM usuarios WHERE funcao = 'Diretor'");
            $diretores = $stmt_dir->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($diretores)) {
                $assunto_dir = "Nova Pendência: Projeto HAE Aguardando Análise";
                foreach ($diretores as $dir) {
                    $corpo_dir = "
                        <div style='font-family: Arial, sans-serif; color: #2c3e50; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
                            <h2 style='color: #f39c12; margin-top: 0;'>Ação Necessária - Sistema HAE</h2>
                            <p>Olá, Diretor(a) <strong>" . htmlspecialchars($dir['nome']) . "</strong>,</p>
                            <p>Um projeto HAE acabou de receber o parecer favorável da Coordenação e agora <strong>aguarda a sua análise final</strong>.</p>
                            <p>Por favor, acesse o painel do <strong>Sistema HAE</strong> no seu navegador para emitir o parecer oficial.</p>
                            <p style='margin-top: 20px; font-size: 12px; color: #7f8c8d;'>Mensagem automática do Sistema de Gestão Acadêmica HAE - Fatec.</p>
                        </div>
                    ";
                    
                    try { dispararEmailSistema($dir['email'], $dir['nome'], $assunto_dir, $corpo_dir); } catch (Exception $e) {}
                    try { dispararPush($dir['id'], "Projeto Aguardando Análise 📋", "A Coordenação aprovou um projeto HAE. Ele aguarda sua análise final.", "https://sistemahae.page.gd/analisar_solicitacoes.php"); } catch (Exception $e) {}
                }
            }
        }
        
        // NOTIFICAR PROFESSOR
        $deve_notificar_prof = false;
        $cor_topo = "";
        $status_texto = "";
        $msg_corpo = "";
        $assunto = "";
        $push_titulo = "";
        $push_mensagem = "";

        if ($novo_status_individual == 'Rejeitado') {
            $deve_notificar_prof = true;
            $cor_topo = "#c0392b";
            $assunto = "Projeto HAE Rejeitado";
            $status_texto = "Rejeitado (Bloqueado)";
            $msg_corpo = "O seu projeto foi analisado e <strong>REJEITADO</strong> pela $funcao_logada. Este projeto foi encerrado e está bloqueado para edições.";
            $push_titulo = "Projeto HAE Rejeitado ✕";
            $push_mensagem = "Seu projeto foi rejeitado pela $funcao_logada. Acesse para visualizar o parecer.";
            
        } else if ($novo_status_individual == 'Devolvido') {
            $deve_notificar_prof = true;
            $cor_topo = "#f39c12";
            $assunto = "Atenção: Correções Necessárias - Projeto HAE";
            $status_texto = "Devolvido para Ajustes";
            $msg_corpo = "O seu projeto foi analisado e <strong>DEVOLVIDO</strong> pela $funcao_logada. Veja o parecer oficial abaixo e realize as correções necessárias no portal.";
            $push_titulo = "Projeto HAE Devolvido ⟲";
            $push_mensagem = "O avaliador encontrou pendências e devolveu seu projeto para ajustes.";
            
            if ($tem_prazo) {
                $msg_corpo .= "
                <div style='background: #fff; border: 1px solid #e0e0e0; border-left: 4px solid #e74c3c; padding: 15px; margin-top: 20px; font-size: 14px; border-radius: 4px;'>
                    <strong style='color: #c0392b; display: block; margin-bottom: 5px;'>📅 PRAZO DE ENTREGA ESTABELECIDO:</strong>
                    Para não comprometer o calendário de aprovações, solicitamos que o projeto corrigido seja submetido no sistema impreterivelmente até o dia <strong>$prazo_data</strong> às <strong>$prazo_hora</strong>.
                </div>";
                $push_mensagem .= " ⏳ Prazo para correção: $prazo_data às $prazo_hora.";
            }
            
        } else if ($global_status == 'Aprovado') {
            $deve_notificar_prof = true;
            $cor_topo = "#27ae60";
            $assunto = "Aprovação Final: Projeto HAE Liberado";
            $status_texto = "Totalmente Aprovado";
            $msg_corpo = "Parabéns! O seu projeto passou por todas as instâncias e foi <strong>oficialmente aprovado</strong>. Ele já está ativo e pronto para o envio de relatórios.";
            $push_titulo = "Projeto HAE Aprovado! 🎉";
            $push_mensagem = "Parabéns! Seu projeto foi aprovado e está ativo no sistema.";
        }

        if ($deve_notificar_prof) {
            $stmt_prof = $pdo->prepare("SELECT u.id, u.nome, u.email, s.titulo_projeto FROM solicitacoes_hae s JOIN usuarios u ON s.professor_id = u.id WHERE s.id = ?");
            $stmt_prof->execute([$solicitacao_id]);
            $prof = $stmt_prof->fetch(PDO::FETCH_ASSOC);

            if ($prof) {
                $nome_prof = $prof['nome'];
                $email_prof = $prof['email'];
                $titulo_proj = $prof['titulo_projeto'];

                $corpo_email = "
                    <div style='font-family: Arial, sans-serif; color: #2c3e50; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
                        <h2 style='color: $cor_topo; margin-top: 0;'>Atualização do Projeto HAE</h2>
                        <p>Olá, Prof(a). <strong>" . htmlspecialchars($nome_prof) . "</strong>,</p>
                        <p>O seu projeto <strong>$titulo_proj</strong> passou por uma avaliação e o status foi atualizado para: <strong>$status_texto</strong>.</p>
                        <p>$msg_corpo</p>
                        
                        <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid $cor_topo; margin: 15px 0; font-style: italic;'>
                            <strong>Parecer Oficial:</strong><br>
                            " . nl2br(htmlspecialchars($parecer)) . "
                        </div>
                        
                        <p>Acesse o painel do <strong>Sistema HAE</strong> no seu navegador para visualizar os detalhes e realizar correções, se necessário.</p>
                        <p style='margin-top: 20px; font-size: 12px; color: #7f8c8d;'>Mensagem automática do Sistema de Gestão Acadêmica HAE - Fatec.</p>
                    </div>
                ";
                
                try { dispararEmailSistema($email_prof, $nome_prof, $assunto, $corpo_email); } catch (Exception $e) {}
                try { dispararPush($prof['id'], $push_titulo, $push_mensagem, "https://sistemahae.page.gd/meus_projetos.php"); } catch (Exception $e) {}
            }
        }

        header("Location: analisar_solicitacoes.php?status=sucesso" . (!empty($return_url) ? "&" . $return_url : ""));
        exit;
    } catch (Exception $e) {
        $mensagem = "Erro ao processar: " . $e->getMessage();
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'sucesso') {
    $mensagem = "Parecer registrado com sucesso!";
}

$visualizando_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$detalhes = null;
$link_voltar = "analisar_solicitacoes.php";
$back_query = "";

if ($visualizando_id) {
    $back_params = $_GET;
    unset($back_params['id'], $back_params['status']);
    
    if (!empty($back_params)) {
        $back_query = http_build_query($back_params);
        $link_voltar .= "?" . $back_query;
    }

    $sql = "SELECT s.*, u.nome AS professor_nome, coord.nome AS nome_coordenador_alvo 
            FROM solicitacoes_hae s 
            JOIN usuarios u ON s.professor_id = u.id 
            LEFT JOIN usuarios coord ON s.coordenador_alvo_id = coord.id
            WHERE s.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$visualizando_id]);
    $detalhes = $stmt->fetch(PDO::FETCH_ASSOC);

} else {
    $mes_atual_calc = (int)date('m');
    $ano_atual_calc = (int)date('Y');
    $semestre_padrao = ($mes_atual_calc <= 6) ? "1/$ano_atual_calc" : "2/$ano_atual_calc";

    $filtro_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
    $filtro_status = isset($_GET['status_filtro']) ? trim($_GET['status_filtro']) : 'Aguardando'; 
    $filtro_semestre = isset($_GET['semestre']) ? trim($_GET['semestre']) : $semestre_padrao;

    $where = ["1=1"];
    $params = [];

    if (!empty($filtro_busca)) {
        $where[] = "(u.nome LIKE ? OR s.titulo_projeto LIKE ? OR s.categoria LIKE ?)";
        $params[] = "%$filtro_busca%";
        $params[] = "%$filtro_busca%";
        $params[] = "%$filtro_busca%";
    }

    if (!empty($filtro_semestre) && $filtro_semestre != 'Todos') {
        $where[] = "s.semestre = ?";
        $params[] = $filtro_semestre;
    }

    if ($filtro_status == 'Aguardando') {
        if ($funcao_logada == 'Coordenador') {
            $where[] = "s.status_coordenador = 'Pendente' AND s.status_aprovacao NOT IN ('Rejeitado', 'Devolvido')";
        } else {
            $where[] = "s.status_diretor = 'Pendente' AND s.status_coordenador = 'Aprovado' AND s.status_aprovacao NOT IN ('Rejeitado', 'Devolvido')";
        }
    } elseif ($filtro_status == 'Pendentes_Geral' && $funcao_logada == 'Diretor') {
        $where[] = "s.status_diretor = 'Pendente' AND s.status_aprovacao NOT IN ('Rejeitado', 'Devolvido')";
    } elseif ($filtro_status == 'MeusAprovados') {
        if ($funcao_logada == 'Diretor') {
            $where[] = "s.status_diretor = 'Aprovado' AND s.diretor_id = ?";
            $params[] = $usuario_id;
        } else {
            $where[] = "s.status_coordenador = 'Aprovado' AND s.coordenador_id = ?";
            $params[] = $usuario_id;
        }
    } elseif ($filtro_status == 'MeusDevolvidos') {
        if ($funcao_logada == 'Diretor') {
            $where[] = "s.status_diretor = 'Devolvido' AND s.diretor_id = ?";
            $params[] = $usuario_id;
        } else {
            $where[] = "s.status_coordenador = 'Devolvido' AND s.coordenador_id = ?";
            $params[] = $usuario_id;
        }
    } elseif ($filtro_status == 'MeusRejeitados') {
        if ($funcao_logada == 'Diretor') {
            $where[] = "s.status_diretor = 'Rejeitado' AND s.diretor_id = ?";
            $params[] = $usuario_id;
        } else {
            $where[] = "s.status_coordenador = 'Rejeitado' AND s.coordenador_id = ?";
            $params[] = $usuario_id;
        }
    } elseif ($filtro_status == 'Aprovados') {
        $where[] = "s.status_aprovacao = 'Aprovado'";
    } elseif ($filtro_status == 'Devolvidos') {
        $where[] = "s.status_aprovacao = 'Devolvido'";
    } elseif ($filtro_status == 'Rejeitados') {
        $where[] = "s.status_aprovacao = 'Rejeitado'";
    }

    $stmt_sem = $pdo->query("SELECT DISTINCT semestre FROM solicitacoes_hae ORDER BY semestre DESC");
    $semestres_disponiveis = $stmt_sem->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($semestre_padrao, $semestres_disponiveis)) {
        array_unshift($semestres_disponiveis, $semestre_padrao);
    }

    $sql = "SELECT s.*, u.nome AS professor_nome, coord.nome AS nome_coordenador_alvo
            FROM solicitacoes_hae s 
            JOIN usuarios u ON s.professor_id = u.id 
            LEFT JOIN usuarios coord ON s.coordenador_alvo_id = coord.id
            WHERE " . implode(" AND ", $where) . " 
            ORDER BY u.nome ASC, s.data_criacao DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $projetos_agrupados_total = [];
    foreach ($solicitacoes as $proj) {
        $projetos_agrupados_total[$proj['professor_nome']][] = $proj;
    }

    $limite_por_pagina = 10;
    $total_professores = count($projetos_agrupados_total);
    $total_paginas = ceil($total_professores / $limite_por_pagina);
    
    $pagina_atual_pag = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_atual_pag < 1) $pagina_atual_pag = 1;
    if ($pagina_atual_pag > $total_paginas && $total_paginas > 0) $pagina_atual_pag = $total_paginas;

    $offset = ($pagina_atual_pag - 1) * $limite_por_pagina;
    $projetos_agrupados = array_slice($projetos_agrupados_total, $offset, $limite_por_pagina, true);

    $query_params = $_GET;
    unset($query_params['pagina'], $query_params['status']); 
    $query_string = http_build_query($query_params);
    $url_base = "analisar_solicitacoes.php?" . ($query_string ? $query_string . "&" : "");
}

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisar Solicitações - Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js"></script>
    <script>
    const firebaseConfig = {
        apiKey: "AIzaSyCXkLWCZD3vKkybvp41YyyU_G2vaeZRcs0",
        authDomain: "hae-fatec.firebaseapp.com",
        projectId: "hae-fatec",
        storageBucket: "hae-fatec.firebasestorage.app",
        messagingSenderId: "732325516207",
        appId: "1:732325516207:web:93cdd26e78656ec2ee156a"
    };
    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();

    function solicitarPermissaoPush() {
        Notification.requestPermission().then((permission) => {
            if (permission === 'granted') {
                navigator.serviceWorker.register('./firebase-messaging-sw.js')
                .then(function(registration) {
                    return messaging.getToken({ 
                        vapidKey: "BEgkKtj6Eq-ttKtvBL3xOoIoyAAdwiWxOLLygWTlwBSEqWx8AY5oZsvFRY033g71NhAhDKg_kcYEErTiE0cbmoE",
                        serviceWorkerRegistration: registration
                    });
                })
                .then((currentToken) => {
                    if (currentToken) {
                        fetch('salvar_token.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ token: currentToken })
                        });
                    }
                }).catch((err) => console.log('Erro ao pegar token:', err));
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        solicitarPermissaoPush();
    });
    </script>

    <style>
        .filter-bar { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; border-left: 4px solid #3498db; }
        .filter-group { display: flex; flex-direction: column; flex: 1; min-width: 150px; }
        .filter-group label { font-size: 12px; font-weight: bold; color: #555; margin-bottom: 5px; text-transform: uppercase; }
        .filter-group input, .filter-group select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; outline: none; font-size: 14px; transition: 0.3s; }
        .filter-group input:focus, .filter-group select:focus { border-color: var(--fatec-red); }
        
        .btn-filtrar { background: var(--fatec-red); color: white; border: none; padding: 11px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-filtrar:hover { background: #8a0000; }
        .btn-limpar { background: #f1f3f5; color: #444; border: 1px solid #ddd; padding: 10px 15px; border-radius: 5px; font-weight: bold; cursor: pointer; text-decoration: none; transition: 0.3s; }
        .btn-limpar:hover { background: #e9ecef; }
        
        .card-table { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); overflow: hidden; margin-bottom: 30px; border-top: 4px solid var(--fatec-red); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; font-size: 14px; border-bottom: 1px solid #eee; }
        th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        
        .linha-mestra { cursor: pointer; transition: 0.2s; }
        .linha-mestra:hover { background-color: #f4f6f9; }
        .linha-mestra td { border-bottom: 1px solid #e0e0e0; }
        .icone-expandir { color: #888; transition: transform 0.3s; margin-right: 8px; font-size: 12px; }
        .linha-mestra.aberta .icone-expandir { transform: rotate(180deg); color: var(--fatec-red); }
        .linha-mestra.aberta td { background-color: #f9f0f0; border-bottom: none; }
        
        .gaveta-detalhes { display: none; background-color: #fafbfc; }
        .gaveta-aberta { display: table-row; }
        .tabela-interna { width: 100%; border-left: 4px solid var(--fatec-red); margin: 0; background: #fff; box-shadow: inset 0 2px 5px rgba(0,0,0,0.02); }
        .tabela-interna th { background: #fff; font-size: 11px; border-bottom: 2px solid #eee; padding: 10px 20px; }
        .tabela-interna td { padding: 12px 20px; font-size: 13.5px; vertical-align: middle; }

        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; white-space: nowrap; }
        .badge-pendente { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-aprovado { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .badge-devolvido { background: #ffecb3; color: #856404; border: 1px solid #ffeeba; }
        .badge-rejeitado { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .badge-espera { background: #e1f5fe; color: #0288d1; border: 1px solid #81d4fa;}

        .btn-action { background: #1e1e2d; color: #fff; padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; font-weight: bold;}
        .btn-action:hover { background: var(--fatec-red); }
        .btn-voltar { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 15px; color: #666; text-decoration: none; font-weight: bold; font-size: 13px; }
        
        .btn-motivo { background: #e74c3c; color: #fff; border: none; cursor: pointer; }
        .btn-motivo:hover { background: #c0392b; }
        .btn-motivo-devolvido { background: #f39c12; color: #fff; border: none; cursor: pointer;}
        .btn-motivo-devolvido:hover { background: #d68910;}

        .paginacao { display: flex; justify-content: center; gap: 8px; margin-bottom: 40px; }
        .paginacao a { display: inline-block; padding: 10px 15px; background: #fff; border: 1px solid #ddd; color: #444; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 13px; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .paginacao a:hover { background: #f8f9fa; border-color: #ccc; transform: translateY(-1px); }
        .paginacao a.active { background: var(--fatec-red); color: #fff; border-color: var(--fatec-red); }

        /* PAINEL DIVIDIDO E ESTILOS EXECUTIVOS DO FORMULÁRIO */
        .split-view { display: flex; gap: 20px; align-items: flex-start; }
        .doc-preview { flex: 6.8; height: 86vh; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 2px solid #ddd; background: #525659; }
        .doc-preview iframe { width: 100%; height: 100%; border: none; }
        
        .form-parecer { flex: 3.2; background: #fff; padding: 22px; border-radius: 10px; border-top: 4px solid var(--fatec-red); box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: sticky; top: 15px; }
        .form-parecer-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; }
        .form-parecer-header h3 { margin: 0; color: var(--fatec-red); font-size: 16px; display: flex; align-items: center; gap: 8px; }
        .badge-cargo { background: #f1f3f5; color: #495057; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }

        /* Card Compacto de Contexto (Coordenação) */
        .context-card { background: #f8f9fa; border: 1px solid #e9ecef; border-left: 3px solid #3498db; border-radius: 6px; padding: 10px 12px; margin-bottom: 15px; font-size: 12.5px; color: #495057; }
        .context-card-row { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
        .context-card-row:last-child { margin-bottom: 0; }
        .context-quote { background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 7px 10px; margin-top: 5px; font-style: italic; color: #333; font-size: 12.5px; }

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1.35fr; gap: 12px; margin-bottom: 14px; }
        .form-group { margin-bottom: 14px; }
        .form-parecer label { display: flex; align-items: center; justify-content: space-between; font-weight: 700; margin-bottom: 6px; font-size: 12px; color: #444; text-transform: uppercase; letter-spacing: 0.3px; }
        .form-parecer input[type="number"], 
        .form-parecer input[type="date"], 
        .form-parecer input[type="time"], 
        .form-parecer textarea { width: 100%; padding: 9px 11px; border: 1px solid #ced4da; border-radius: 6px; font-size: 13.5px; outline: none; box-sizing: border-box; transition: 0.2s; font-family: inherit; }
        .form-parecer input:focus, .form-parecer textarea:focus { border-color: var(--fatec-red); box-shadow: 0 0 0 2px rgba(178,0,0,0.08); }
        .form-parecer textarea { resize: vertical; min-height: 85px; }

        .tooltip-icon { color: #888; cursor: help; font-size: 13px; }
        .tooltip-icon:hover { color: #333; }

        /* Caixa Compacta de Prazo de Devolução */
        .prazo-toggle-box { border: 1px solid #e2e8f0; background: #fafbfc; border-radius: 6px; padding: 10px 12px; margin-bottom: 16px; transition: 0.2s; }
        .prazo-toggle-box:hover { border-color: #cbd5e1; }
        .prazo-label { cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 12.5px !important; color: #475569 !important; margin: 0 !important; text-transform: none !important; font-weight: 600 !important; justify-content: flex-start !important; }
        .prazo-fields { display: none; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #cbd5e1; }

        /* Hierarquia de Botões de Decisão */
        .botoes-acao { display: flex; flex-direction: column; gap: 8px; }
        .botoes-secundarios { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        
        .btn-aprovar { background: #2ecc71; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 2px 6px rgba(46, 204, 113, 0.25); }
        .btn-aprovar:hover { background: #27ae60; transform: translateY(-1px); }
        
        .btn-devolver { background: #fff8eb; color: #d68910; border: 1px solid #f5cba7; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-devolver:hover { background: #f39c12; color: #fff; border-color: #f39c12; }
        
        .btn-rejeitar { background: #fdf2f2; color: #c0392b; border: 1px solid #f5b7b1; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-rejeitar:hover { background: #e74c3c; color: #fff; border-color: #e74c3c; }
        
        .historico-box { background: #f8f9fa; border-left: 3px solid #3498db; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; color: #444; }
        .btn-ver-pdf-mobile { display: none; background: #3498db; color: white; padding: 12px; text-align: center; border-radius: 6px; text-decoration: none; font-weight: bold; margin-bottom: 15px; font-size: 13px; }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
        .modal-box { background: #fff; padding: 25px; border-radius: 10px; width: 90%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border-top: 4px solid #3498db; position: relative; animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .modal-header h3 { color: #3498db; margin: 0; font-size: 18px; display: flex; align-items: center; gap: 8px; }
        .btn-close-modal { background: none; border: none; font-size: 20px; cursor: pointer; color: #888; transition: 0.3s; }
        .btn-close-modal:hover { color: #333; }
        .modal-content-text { font-size: 14px; color: #444; line-height: 1.6; max-height: 60vh; overflow-y: auto; padding-right: 5px; }
        .modal-content-text p { background: #f9f9f9; padding: 15px; border-radius: 6px; border: 1px solid #eee; font-style: italic; }

        @media (max-width: 1024px) {
            .split-view { flex-direction: column; }
            .doc-preview { display: none; }
            .form-parecer { flex: 1; width: 100%; position: relative; top: 0; box-sizing: border-box; }
            .btn-ver-pdf-mobile { display: block; }
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
            <button class="collapse-btn" id="collapse-btn" title="Minimizar Menu">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
        </div>
        
        <nav class="menu">
            <div class="menu-title">Navegação</div>
            <ul>
                <li><a href="painel.php" class="<?php echo ($pagina_atual == 'painel.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie"></i> <span class="menu-text">Dashboard</span></a></li>
                
                <?php if ($_SESSION['usuario_funcao'] == 'Professor'): ?>
                    <li><a href="nova_solicitacao.php" class="<?php echo ($pagina_atual == 'nova_solicitacao.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-circle-plus"></i> <span class="menu-text">Nova Solicitação</span></a></li>
                    <li><a href="meus_projetos.php" class="<?php echo ($pagina_atual == 'meus_projetos.php') ? 'active' : ''; ?>"><i class="fa-solid fa-folder-open"></i> <span class="menu-text">Meus Projetos</span></a></li>
                    <li><a href="enviar_relatorio.php" class="<?php echo ($pagina_atual == 'enviar_relatorio.php') ? 'active' : ''; ?>"><i class="fa-solid fa-calendar-check"></i> <span class="menu-text">Enviar Relatório</span></a></li>
                    <li><a href="meus_rascunhos.php" class="<?php echo ($pagina_atual == 'meus_rascunhos.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-pen"></i> <span class="menu-text">Meus Rascunhos</span></a></li>
                <?php else: ?>
                    <li><a href="analisar_solicitacoes.php" class="<?php echo ($pagina_atual == 'analisar_solicitacoes.php') ? 'active' : ''; ?>"><i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">Analisar Solicitações</span></a></li>
                    <li><a href="acompanhar_relatorios.php" class="<?php echo ($pagina_atual == 'acompanhar_relatorios.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> <span class="menu-text">Acompanhar Relatórios</span></a></li>
                    <li><a href="relatorios_atrasados.php" class="<?php echo ($pagina_atual == 'relatorios_atrasados.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-invoice"></i> <span class="menu-text">Relatórios Atrasados</span></a></li>
                    
                    <?php if ($_SESSION['usuario_funcao'] == 'Diretor'): ?>
                        <li><a href="projetos_hae.php" class="<?php echo ($pagina_atual == 'projetos_hae.php') ? 'active' : ''; ?>"><i class="fa-solid fa-list-check"></i> <span class="menu-text">Projetos HAE</span></a></li>
                        <li><a href="cadastrar_professor.php" class="<?php echo ($pagina_atual == 'cadastrar_professor.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-plus"></i> <span class="menu-text">Cadastrar Usuário</span></a></li>
                        <li><a href="listar_usuarios.php" class="<?php echo ($pagina_atual == 'listar_usuarios.php') ? 'active' : ''; ?>"><i class="fa-solid fa-users"></i> <span class="menu-text">Lista de Usuários</span></a></li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <li><a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>
                
                <?php if ($_SESSION['usuario_funcao'] == 'Diretor'): ?>
                    <li><a href="configuracoes.php" class="<?php echo ($pagina_atual == 'configuracoes.php') ? 'active' : ''; ?>"><i class="fa-solid fa-cogs"></i> <span class="menu-text">Configurações</span></a></li>
                <?php endif; ?>
                
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span class="menu-text">Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1><?php echo $visualizando_id ? "Análise de Projeto" : "Gestão de Solicitações"; ?></h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <?php if($mensagem): ?>
            <div class="alert-success">✅ <?php echo $mensagem; ?></div>
        <?php endif; ?>

        <?php if ($visualizando_id && $detalhes): ?>
            <a href="<?php echo htmlspecialchars($link_voltar); ?>" class="btn-voltar"><i class="fa-solid fa-arrow-left"></i> Voltar para a lista</a>
            
            <div class="split-view">
                <div class="doc-preview">
                    <iframe src="documento_hae.php?id=<?php echo $visualizando_id; ?>"></iframe>
                </div>
                
                <div class="form-parecer">
                    <div class="form-parecer-header">
                        <h3><i class="fa-solid fa-clipboard-check"></i> Emitir Parecer</h3>
                        <span class="badge-cargo"><?php echo $funcao_logada; ?></span>
                    </div>
                    
                    <a href="documento_hae.php?id=<?php echo $visualizando_id; ?>" target="_blank" class="btn-ver-pdf-mobile">📄 Abrir Documento em Tela Cheia</a>
                    
                    <?php 
                        $tem_alvo = !empty($detalhes['nome_coordenador_alvo']);
                        $tem_parecer_previo = ($funcao_logada == 'Diretor' && !empty($detalhes['parecer_coordenador'])) || ($funcao_logada == 'Coordenador' && !empty($detalhes['parecer_diretor']));
                    ?>

                    <!-- CARD UNIFICADO DE CONTEXTO (COORDENAÇÃO / DIREÇÃO) -->
                    <?php if ($tem_alvo || $tem_parecer_previo): ?>
                        <div class="context-card">
                            <?php if ($tem_alvo): ?>
                                <div class="context-card-row">
                                    <i class="fa-solid fa-bullseye" style="color: #3498db;"></i>
                                    <span><strong>Coord. Indicado:</strong> <?php echo htmlspecialchars($detalhes['nome_coordenador_alvo']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($funcao_logada == 'Diretor' && !empty($detalhes['parecer_coordenador'])): ?>
                                <div class="context-card-row" style="align-items: flex-start; flex-direction: column; gap: 2px;">
                                    <span><i class="fa-solid fa-comment-dots" style="color: #27ae60;"></i> <strong>Parecer da Coordenação:</strong></span>
                                    <div class="context-quote"><?php echo nl2br(htmlspecialchars($detalhes['parecer_coordenador'])); ?></div>
                                </div>
                            <?php elseif ($funcao_logada == 'Coordenador' && !empty($detalhes['parecer_diretor'])): ?>
                                <div class="context-card-row" style="align-items: flex-start; flex-direction: column; gap: 2px;">
                                    <span><i class="fa-solid fa-comment-dots" style="color: #27ae60;"></i> <strong>Parecer da Direção:</strong></span>
                                    <div class="context-quote"><?php echo nl2br(htmlspecialchars($detalhes['parecer_diretor'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($detalhes['status_aprovacao'] == 'Rejeitado'): ?>
                        <div class="historico-box" style="border-left-color: #e74c3c; background: #fff9f9;">
                            <strong style="color: #c0392b;"><i class="fa-solid fa-ban"></i> Projeto Rejeitado (Fechado)</strong><br>
                            Este projeto já foi avaliado e reprovado definitivamente. Ele foi encerrado e não pode mais ser editado pelo professor.
                        </div>
                    <?php elseif ($detalhes['status_aprovacao'] == 'Devolvido'): ?>
                        <div class="historico-box" style="border-left-color: #f39c12; background: #fffdf5;">
                            <strong style="color: #d68910;"><i class="fa-solid fa-rotate-left"></i> Projeto Devolvido</strong><br>
                            Este projeto foi devolvido ao professor para ajustes. Ele poderá editá-lo e submetê-lo novamente para análise.
                        </div>
                    <?php elseif ($detalhes['status_aprovacao'] == 'Aprovado'): ?>
                        <div class="historico-box" style="border-left-color: #2ecc71; background: #f4fbf7;">
                            <strong style="color: #27ae60;"><i class="fa-solid fa-check-double"></i> Projeto Aprovado</strong><br>
                            Este projeto já foi totalmente aprovado por ambas as instâncias (Direção e Coordenação).
                        </div>
                    <?php elseif (($funcao_logada == 'Coordenador' && $detalhes['status_coordenador'] != 'Pendente') || ($funcao_logada == 'Diretor' && $detalhes['status_diretor'] != 'Pendente')): ?>
                        <div class="historico-box" style="border-left-color: #2ecc71; background: #f4fbf7;">
                            <strong style="color: #27ae60;"><i class="fa-solid fa-check"></i> Parecer Concluído</strong><br>
                            Você já emitiu e salvou o seu parecer favorável para este projeto. Aguardando a outra instância.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="analisar_solicitacoes.php?id=<?php echo $visualizando_id; ?>" id="formAnalise">
                            <input type="hidden" name="solicitacao_id" value="<?php echo $visualizando_id; ?>">
                            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($back_query); ?>">
                            
                            <?php 
                                $valor_sugerido = ($detalhes['horas_aprovadas'] !== null) ? $detalhes['horas_aprovadas'] : $detalhes['quantidade_horas'];
                                $data_inicio_sugerida = !empty($detalhes['data_inicio_relatorios']) ? $detalhes['data_inicio_relatorios'] : date('Y-m-d');
                            ?>

                            <!-- LINHA DUPLA COMPACTA (HORAS + DATA DE INÍCIO PARA O DIRETOR) -->
                            <?php if ($funcao_logada == 'Diretor'): ?>
                                <div class="form-grid-2">
                                    <div>
                                        <label>
                                            <span>Horas HAE</span>
                                            <span style="font-size: 10px; color: #888; font-weight: normal;">(Pedidas: <?php echo (int)$detalhes['quantidade_horas']; ?>h)</span>
                                        </label>
                                        <input type="number" name="horas_aprovadas" value="<?php echo htmlspecialchars($valor_sugerido); ?>" required min="0">
                                    </div>
                                    <div>
                                        <label>
                                            <span style="color: #27ae60;"><i class="fa-solid fa-calendar-check"></i> Início Relatórios</span>
                                            <i class="fa-solid fa-circle-question tooltip-icon" title="Define a partir de qual data o sistema cobrará os relatórios mensais caso o projeto seja aprovado."></i>
                                        </label>
                                        <input type="date" name="data_inicio_relatorios" id="data_inicio_relatorios" value="<?php echo htmlspecialchars($data_inicio_sugerida); ?>">
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="form-group">
                                    <label>
                                        <span>Horas HAE Recomendadas</span>
                                        <span style="font-size: 11px; color: #888; font-weight: normal;">(Solicitadas pelo Prof.: <?php echo (int)$detalhes['quantidade_horas']; ?>h)</span>
                                    </label>
                                    <input type="number" name="horas_aprovadas" value="<?php echo htmlspecialchars($valor_sugerido); ?>" required min="0">
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Seu Parecer Oficial</label>
                                <textarea name="parecer" id="campo_parecer" rows="4" placeholder="Digite sua avaliação sobre o projeto..." required><?php echo ($funcao_logada == 'Coordenador') ? htmlspecialchars($detalhes['parecer_coordenador']) : htmlspecialchars($detalhes['parecer_diretor']); ?></textarea>
                            </div>
                            
                            <!-- CAIXA ENXUTA DE PRAZO PARA DEVOLUÇÃO -->
                            <div class="prazo-toggle-box">
                                <label class="prazo-label">
                                    <input type="checkbox" id="check_prazo" onchange="document.getElementById('box_prazo_campos').style.display = this.checked ? 'grid' : 'none';" style="width: 15px; height: 15px; margin: 0; cursor: pointer;"> 
                                    <span><i class="fa-solid fa-stopwatch" style="color: #f39c12;"></i> Estabelecer prazo limite (em caso de devolução)</span>
                                </label>
                                
                                <div id="box_prazo_campos" class="prazo-fields">
                                    <div>
                                        <span style="display: block; font-size: 11px; color: #555; font-weight: bold; margin-bottom: 4px;">Data Limite</span>
                                        <input type="date" name="prazo_data">
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 11px; color: #555; font-weight: bold; margin-bottom: 4px;">Horário</span>
                                        <input type="time" name="prazo_hora">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- BOTÕES DE AÇÃO EM HIERARQUIA -->
                            <div class="botoes-acao">
                                <button type="submit" name="acao" value="aprovar" class="btn-aprovar" onclick="return validarParecer('aprovar');">
                                    <i class="fa-solid fa-check"></i> Aprovar Projeto HAE
                                </button>
                                <div class="botoes-secundarios">
                                    <button type="submit" name="acao" value="devolver" class="btn-devolver" onclick="return validarParecer('devolver');">
                                        <i class="fa-solid fa-rotate-left"></i> Devolver
                                    </button>
                                    <button type="submit" name="acao" value="rejeitar" class="btn-rejeitar" onclick="return validarParecer('rejeitar');">
                                        <i class="fa-solid fa-xmark"></i> Rejeitar
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <form method="GET" class="filter-bar">
                <div class="filter-group" style="flex: 2;">
                    <label>Buscar Professor ou Título</label>
                    <input type="text" name="busca" placeholder="Digite uma palavra-chave..." value="<?php echo htmlspecialchars($filtro_busca); ?>">
                </div>
                
                <div class="filter-group">
                    <label>Semestre</label>
                    <select name="semestre">
                        <option value="Todos">Todos os Semestres</option>
                        <?php foreach($semestres_disponiveis as $sem): ?>
                            <?php if(!empty($sem)): ?>
                                <option value="<?php echo $sem; ?>" <?php echo $filtro_semestre == $sem ? 'selected' : ''; ?>><?php echo $sem; ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Filtro de Status</label>
                    <select name="status_filtro">
                        <optgroup label="Minhas Ações">
                            <?php if ($funcao_logada == 'Diretor'): ?>
                                <option value="Aguardando" <?php echo $filtro_status == 'Aguardando' ? 'selected' : ''; ?>>Aguardando Minha Análise</option>
                                <option value="Pendentes_Geral" <?php echo $filtro_status == 'Pendentes_Geral' ? 'selected' : ''; ?>>Todas Minhas Pendências</option>
                            <?php else: ?>
                                <option value="Aguardando" <?php echo $filtro_status == 'Aguardando' ? 'selected' : ''; ?>>Aguardando Minha Ação</option>
                            <?php endif; ?>
                            <option value="MeusAprovados" <?php echo $filtro_status == 'MeusAprovados' ? 'selected' : ''; ?>>Projetos que Aprovei</option>
                            <option value="MeusDevolvidos" <?php echo $filtro_status == 'MeusDevolvidos' ? 'selected' : ''; ?>>Projetos que Devolvi</option>
                            <option value="MeusRejeitados" <?php echo $filtro_status == 'MeusRejeitados' ? 'selected' : ''; ?>>Projetos que Rejeitei</option>
                        </optgroup>
                        <optgroup label="Visão Global">
                            <option value="Todos" <?php echo $filtro_status == 'Todos' ? 'selected' : ''; ?>>Todos os Projetos</option>
                            <option value="Aprovados" <?php echo $filtro_status == 'Aprovados' ? 'selected' : ''; ?>>Aprovados Totalmente</option>
                            <option value="Devolvidos" <?php echo $filtro_status == 'Devolvidos' ? 'selected' : ''; ?>>Devolvidos p/ Ajuste (Geral)</option>
                            <option value="Rejeitados" <?php echo $filtro_status == 'Rejeitados' ? 'selected' : ''; ?>>Rejeitados (Geral)</option>
                        </optgroup>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-filtrar"><i class="fa-solid fa-filter"></i> Filtrar</button>
                    <a href="analisar_solicitacoes.php" class="btn-limpar">Limpar</a>
                </div>
            </form>

            <div class="card-table">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40%;">Professor(a)</th>
                                <th>Total de Lançamentos</th>
                                <th>Status dos Projetos</th>
                                <th style="text-align: right;">Expandir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($projetos_agrupados) > 0): ?>
                                <?php $id_accordion = 1; ?>
                                <?php foreach ($projetos_agrupados as $nome_prof => $lista_projetos): ?>
                                    
                                    <?php 
                                        $qtd_projetos = count($lista_projetos);
                                        $qtd_minha_acao = 0;
                                        $qtd_esperando_coord = 0;
                                        $qtd_rejeitados = 0;
                                        $qtd_devolvidos = 0;
                                        $qtd_aprovados = 0;
                                        
                                        foreach($lista_projetos as $p) {
                                            if ($p['status_aprovacao'] == 'Rejeitado') {
                                                $qtd_rejeitados++;
                                            } elseif ($p['status_aprovacao'] == 'Devolvido') {
                                                $qtd_devolvidos++;
                                            } elseif ($p['status_aprovacao'] == 'Aprovado') {
                                                $qtd_aprovados++;
                                            } else {
                                                if ($funcao_logada == 'Coordenador' && $p['status_coordenador'] == 'Pendente') {
                                                    if (empty($p['coordenador_alvo_id']) || $p['coordenador_alvo_id'] == $usuario_id) {
                                                        $qtd_minha_acao++;
                                                    }
                                                }
                                                if ($funcao_logada == 'Diretor' && $p['status_diretor'] == 'Pendente') {
                                                    if ($p['status_coordenador'] == 'Aprovado') {
                                                        $qtd_minha_acao++;
                                                    } else {
                                                        $qtd_esperando_coord++;
                                                    }
                                                }
                                            }
                                        }
                                    ?>
                                    
                                    <tr class="linha-mestra" id="mestra_<?php echo $id_accordion; ?>" onclick="toggleGaveta(<?php echo $id_accordion; ?>)">
                                        <td>
                                            <i class="fa-solid fa-chevron-down icone-expandir"></i>
                                            <strong><i class="fa-solid fa-user-tie" style="color: #888; margin-right: 5px;"></i> <?php echo htmlspecialchars($nome_prof); ?></strong>
                                        </td>
                                        <td><span style="color: #666; font-size: 13px;"><?php echo $qtd_projetos; ?> projeto(s)</span></td>
                                        <td>
                                            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                <?php if($qtd_minha_acao > 0): ?>
                                                    <span class="badge badge-pendente" style="background:#f39c12; color:white; border:none;"><i class="fa-solid fa-bell"></i> <?php echo $qtd_minha_acao; ?> Pronto p/ Você</span>
                                                <?php endif; ?>
                                                
                                                <?php if($qtd_esperando_coord > 0): ?>
                                                    <span class="badge" style="background:#e1f5fe; color:#0288d1; border: 1px solid #81d4fa;"><i class="fa-solid fa-hourglass-half"></i> <?php echo $qtd_esperando_coord; ?> Aguardando Coord.</span>
                                                <?php endif; ?>
                                                
                                                <?php if($qtd_devolvidos > 0): ?>
                                                    <span class="badge badge-devolvido"><i class="fa-solid fa-rotate-left"></i> <?php echo $qtd_devolvidos; ?> Devolvido(s)</span>
                                                <?php endif; ?>
                                                
                                                <?php if($qtd_rejeitados > 0): ?>
                                                    <span class="badge badge-rejeitado"><i class="fa-solid fa-ban"></i> <?php echo $qtd_rejeitados; ?> Rejeitado(s)</span>
                                                <?php endif; ?>
                                                
                                                <?php if($qtd_aprovados > 0): ?>
                                                    <span class="badge badge-aprovado"><i class="fa-solid fa-check-double"></i> <?php echo $qtd_aprovados; ?> Aprovado(s)</span>
                                                <?php endif; ?>
                                                
                                                <?php if($qtd_minha_acao == 0 && $qtd_esperando_coord == 0 && $qtd_rejeitados == 0 && $qtd_devolvidos == 0 && $qtd_aprovados == 0): ?>
                                                    <span class="badge" style="background:#eee; color:#888;">Sem Projetos</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td style="text-align: right; color: var(--fatec-red); font-size: 12px; font-weight: bold;">Ver detalhes</td>
                                    </tr>
                                    
                                    <tr class="gaveta-detalhes" id="gaveta_<?php echo $id_accordion; ?>">
                                        <td colspan="4" style="padding: 0;">
                                            <table class="tabela-interna">
                                                <thead>
                                                    <tr>
                                                        <th>Data / Título do Projeto</th>
                                                        <th>Semestre</th>
                                                        <th>Status Geral</th>
                                                        <th>Ação</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($lista_projetos as $proj): ?>
                                                        <?php 
                                                            $texto_status = $proj['status_aprovacao'];
                                                            $badge_class = 'badge-pendente';
                                                            
                                                            if($proj['status_aprovacao'] == 'Aprovado') {
                                                                $badge_class = 'badge-aprovado';
                                                            } else if($proj['status_aprovacao'] == 'Rejeitado') {
                                                                $badge_class = 'badge-rejeitado';
                                                            } else if($proj['status_aprovacao'] == 'Devolvido') {
                                                                $badge_class = 'badge-devolvido';
                                                            } else if($proj['status_coordenador'] == 'Aprovado' && $proj['status_diretor'] == 'Pendente') {
                                                                $texto_status = 'Aguardando Diretor';
                                                                $badge_class = 'badge-espera';
                                                            } else if($proj['status_diretor'] == 'Aprovado' && $proj['status_coordenador'] == 'Pendente') {
                                                                $texto_status = 'Aguardando Coord.';
                                                                $badge_class = 'badge-espera';
                                                            } else {
                                                                $texto_status = 'Pendente (Ambos)';
                                                            }

                                                            $motivo_recusa = "";
                                                            if (in_array($proj['status_aprovacao'], ['Rejeitado', 'Devolvido'])) {
                                                                if (in_array($proj['status_diretor'], ['Rejeitado', 'Devolvido'])) {
                                                                    $motivo_recusa = "Parecer do(a) Diretor(a):\n" . $proj['parecer_diretor'];
                                                                } elseif (in_array($proj['status_coordenador'], ['Rejeitado', 'Devolvido'])) {
                                                                    $motivo_recusa = "Parecer do(a) Coordenador(a):\n" . $proj['parecer_coordenador'];
                                                                }
                                                            }
                                                            
                                                            $current_params = $_GET;
                                                            unset($current_params['status']);
                                                            $url_filters = http_build_query($current_params);
                                                            $link_avaliar = "analisar_solicitacoes.php?id=" . $proj['id'] . (!empty($url_filters) ? "&" . $url_filters : "");
                                                        ?>
                                                        <tr>
                                                            <td style="width: 50%; color: #444;">
                                                                <span style="font-size:11px; color:#888;"><?php echo date('d/m/Y', strtotime($proj['data_criacao'])); ?></span><br>
                                                                <strong><?php echo htmlspecialchars($proj['titulo_projeto']); ?></strong>
                                                                
                                                                <?php if (!empty($proj['nome_coordenador_alvo'])): ?>
                                                                    <br>
                                                                    <?php if ($funcao_logada == 'Coordenador' && $proj['coordenador_alvo_id'] == $_SESSION['usuario_id']): ?>
                                                                        <span class="badge" style="background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; margin-top: 4px; font-size: 10px;"><i class="fa-solid fa-bullseye"></i> Direcionado a Você</span>
                                                                    <?php else: ?>
                                                                        <span class="badge" style="background: #f1f3f5; color: #6c757d; border: 1px solid #dee2e6; margin-top: 4px; font-size: 10px;"><i class="fa-solid fa-user-tag"></i> Direcionado p/ <?php echo htmlspecialchars($proj['nome_coordenador_alvo']); ?></span>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($proj['semestre']); ?></td>
                                                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $texto_status; ?></span></td>
                                                            <td style="display: flex; gap: 5px;">
                                                                <a href="<?php echo htmlspecialchars($link_avaliar); ?>" class="btn-action">
                                                                    <i class="fa-solid fa-folder-open"></i> Avaliar
                                                                </a>
                                                                
                                                                <?php if (in_array($proj['status_aprovacao'], ['Rejeitado', 'Devolvido'])): ?>
                                                                    <?php 
                                                                        $btn_color = $proj['status_aprovacao'] == 'Devolvido' ? 'btn-motivo-devolvido' : 'btn-motivo';
                                                                        $btn_icon = $proj['status_aprovacao'] == 'Devolvido' ? 'fa-rotate-left' : 'fa-ban';
                                                                        $titulo_modal = $proj['status_aprovacao'] == 'Devolvido' ? 'Motivo da Devolução' : 'Motivo da Rejeição';
                                                                    ?>
                                                                    <button type="button" class="btn-action <?php echo $btn_color; ?>" data-motivo="<?php echo htmlspecialchars($motivo_recusa, ENT_QUOTES, 'UTF-8'); ?>" data-titulo="<?php echo $titulo_modal; ?>" onclick="abrirModalMotivo(this, event)">
                                                                        <i class="fa-solid <?php echo $btn_icon; ?>"></i> Feedback
                                                                    </button>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>

                                    <?php $id_accordion++; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; padding: 40px; color: #888;">Nenhuma solicitação encontrada com os filtros selecionados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div class="paginacao">
                    <?php if ($pagina_atual_pag > 1): ?>
                        <a href="<?php echo $url_base . 'pagina=' . ($pagina_atual_pag - 1); ?>"><i class="fa-solid fa-angle-left"></i> Anterior</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="<?php echo $url_base . 'pagina=' . $i; ?>" class="<?php echo $i == $pagina_atual_pag ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($pagina_atual_pag < $total_paginas): ?>
                        <a href="<?php echo $url_base . 'pagina=' . ($pagina_atual_pag + 1); ?>">Próxima <i class="fa-solid fa-angle-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </main>

    <div class="modal-overlay" id="modalMotivo" onclick="fecharModalMotivo(event)">
        <div class="modal-box" onclick="event.stopPropagation();">
            <div class="modal-header">
                <h3 id="modalTitulo"><i class="fa-solid fa-comment-dots"></i> Feedback da Avaliação</h3>
                <button class="btn-close-modal" onclick="fecharModalMotivo()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-content-text">
                <p id="textoMotivo"></p>
            </div>
        </div>
    </div>

    <script src="assets/js/painel.js"></script>
    <script>
        function toggleGaveta(id) {
            var gaveta = document.getElementById('gaveta_' + id);
            var linhaMestra = document.getElementById('mestra_' + id);
            
            if (gaveta.classList.contains('gaveta-aberta')) {
                gaveta.classList.remove('gaveta-aberta');
                linhaMestra.classList.remove('aberta');
            } else {
                gaveta.classList.add('gaveta-aberta');
                linhaMestra.classList.add('aberta');
            }
        }

        function abrirModalMotivo(btn, event) {
            event.stopPropagation(); 
            let texto = btn.getAttribute('data-motivo');
            let titulo = btn.getAttribute('data-titulo');
            
            document.getElementById('modalTitulo').innerHTML = '<i class="fa-solid fa-comment-dots"></i> ' + titulo;
            document.getElementById('textoMotivo').innerHTML = texto.replace(/\n/g, '<br>');
            document.getElementById('modalMotivo').style.display = 'flex';
        }

        function fecharModalMotivo() {
            document.getElementById('modalMotivo').style.display = 'none';
        }

        function validarParecer(acao) {
            var campoParecer = document.getElementById('campo_parecer').value.trim();
            if (campoParecer === '') {
                alert('Atenção: É obrigatório preencher o campo "Seu Parecer Oficial" antes de prosseguir com a avaliação!');
                document.getElementById('campo_parecer').focus();
                return false; 
            }
            if (acao === 'rejeitar') {
                return confirm('Tem certeza que deseja REJEITAR DEFINITIVAMENTE este projeto HAE? (O professor será bloqueado de editá-lo)');
            } else if (acao === 'devolver') {
                let checkPrazo = document.getElementById('check_prazo');
                if (checkPrazo && checkPrazo.checked) {
                    let dataPrazo = document.querySelector('input[name="prazo_data"]').value;
                    let horaPrazo = document.querySelector('input[name="prazo_hora"]').value;
                    if (!dataPrazo || !horaPrazo) {
                        alert('Por favor, preencha a Data e o Horário limite ou desmarque a opção de prazo.');
                        return false;
                    }
                }
                return confirm('Deseja DEVOLVER este projeto para o professor fazer correções?');
            } else {
                <?php if ($funcao_logada == 'Diretor'): ?>
                let dataInicio = document.querySelector('input[name="data_inicio_relatorios"]');
                if (dataInicio && dataInicio.value === '') {
                    alert('Por favor, informe a Data de Início dos Relatórios antes de aprovar o projeto.');
                    dataInicio.focus();
                    return false;
                }
                <?php endif; ?>
                return confirm('Confirmar o seu parecer FAVORÁVEL para este projeto?');
            }
        }
    </script>
</body>
</html>