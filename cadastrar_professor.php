<?php
session_start();
require 'config/conexao.php';
require_once 'enviar_email.php'; 

// Apenas Direção e Coordenação podem acessar
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_funcao'], ['Coordenador', 'Diretor'])) {
    header("Location: painel.php");
    exit;
}

$sucesso = "";
$erro = "";
$link_wa = "";
$resultados_importacao = []; // Array para guardar os resultados do Excel/CSV

$pagina_atual = basename($_SERVER['PHP_SELF']);

// =========================================================================
// 1. PROCESSAMENTO DE CADASTRO INDIVIDUAL
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'individual') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $whatsapp = trim($_POST['whatsapp']);
    $data_nascimento = $_POST['data_nascimento'];
    $funcao = $_POST['funcao'];

    $senha_provisoria = date('dmY', strtotime($data_nascimento));
    $senha_hash = md5($senha_provisoria);
    $saudacao = ($funcao == 'Professor') ? "Prof(a)." : (($funcao == 'Coordenador') ? "Coordenador(a)" : "Diretor(a)");

    try {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $erro = "Este e-mail já está cadastrado no sistema.";
        } else {
            $sql = "INSERT INTO usuarios (nome, email, telefone_whatsapp, data_nascimento, funcao, senha, primeiro_acesso) VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $whatsapp, $data_nascimento, $funcao, $senha_hash]);

            // DISPARO AUTOMÁTICO DE E-MAIL
            $assunto = "Acesso Liberado - Sistema HAE Fatec";
            $corpo_email = "
                <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
                    <div style='background-color: #b20000; padding: 20px; text-align: center; color: white;'>
                        <h2 style='margin: 0; font-size: 20px;'>Sistema HAE - Fatec</h2>
                    </div>
                    <div style='padding: 20px;'>
                        <h3 style='color: #b20000; margin-top: 0;'>Olá, $saudacao $nome.</h3>
                        <p>O seu perfil institucional foi ativado com sucesso pela administração.</p>
                        <p>Para efetuar o seu primeiro acesso à plataforma, utilize os dados de autenticação abaixo:</p>
                        <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #b20000; margin: 15px 0;'>
                            <ul style='margin: 0; padding-left: 20px;'>
                                <li style='margin-bottom: 10px;'><strong>Usuário:</strong> $email</li>
                                <li style='margin-bottom: 10px;'>
                                    <strong><img src='cid:img_texto_senha' alt='Senha Provisória' style='height: 12px; vertical-align: middle;'>:</strong> $senha_provisoria <em>(sua data de nascimento)</em>
                                </li>
                                <li>
                                    <strong>Endereço do portal:</strong> Digite o endereço da imagem abaixo na barra do seu navegador:<br><br>
                                    <img src='cid:img_link_portal' alt='Link de Acesso' style='max-width: 250px; border: 1px solid #ccc; border-radius: 4px;'>
                                </li>
                            </ul>
                        </div>
                        <p>Recomendamos que, após a entrada no sistema, você atualize seus dados de acesso e cadastre a sua assinatura digital na seção de perfil.</p>
                        <p style='margin-top: 25px; font-size: 14px; color: #555;'>Atenciosamente,<br><strong>Gestão Acadêmica - Fatec</strong></p>
                    </div>
                </div>
            ";
            
            $lista_imagens = [
                ['path' => __DIR__ . '/img/link_acesso.jpeg', 'cid' => 'img_link_portal'],
                ['path' => __DIR__ . '/img/texto_senha.jpeg', 'cid' => 'img_texto_senha']
            ];
            
            $email_enviado = dispararEmailSistema($email, $nome, $assunto, $corpo_email, $lista_imagens);

            // GERA O LINK DO WHATSAPP
            $num_limpo = preg_replace('/\D/', '', $whatsapp);
            if (substr($num_limpo, 0, 2) !== '55') $num_limpo = '55' . $num_limpo;
            $msg = "Olá, $saudacao $nome! Seu acesso ao Portal HAE Fatec foi criado.\n\n*E-mail:* $email\n*Senha provisória:* $senha_provisoria (Sua data de nascimento)\n\nPor favor, acesse o sistema para completar seu perfil, cadastrar sua imagem de assinatura digital e criar uma nova senha definitiva.\nAcesse: http://sistemahae.page.gd/";
            $link_wa = "https://wa.me/{$num_limpo}?text=" . urlencode($msg);

            if ($email_enviado) {
                $sucesso = "Usuário cadastrado com sucesso! Um e-mail com as credenciais já foi disparado.";
            } else {
                $sucesso = "Usuário cadastrado com sucesso! (Atenção: O disparo de e-mail falhou, avise-o pelo WhatsApp).";
            }
        }
    } catch (PDOException $e) {
        $erro = "Erro ao cadastrar: " . $e->getMessage();
    }
}

// =========================================================================
// 2. PROCESSAMENTO DE IMPORTAÇÃO EM LOTE (PLANILHA INTELIGENTE)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'importar') {
    
    // Recebe o JSON gerado pelo SheetJS no navegador
    if (!empty($_POST['json_data'])) {
        $dados_planilha = json_decode($_POST['json_data'], true);
        
        if (is_array($dados_planilha) && count($dados_planilha) > 1) {
            $linha_atual = 0;
            
            foreach ($dados_planilha as $data) {
                $linha_atual++;
                if ($linha_atual == 1) continue; // Pula o cabeçalho (Linha 1)
                
                // Evita erros caso alguma coluna esteja vazia
                $nome = trim($data[0] ?? '');
                $email = trim($data[1] ?? '');
                $whatsapp = trim($data[2] ?? '');
                $data_nascimento_bruta = trim($data[3] ?? '');
                $funcao = trim($data[4] ?? '');
                
                if (empty($nome) || empty($email)) continue;
                
                // Formatação segura da data
                if (strpos($data_nascimento_bruta, '/') !== false) {
                    $partes_data = explode('/', $data_nascimento_bruta);
                    if (count($partes_data) == 3) {
                        $data_nascimento = $partes_data[2] . '-' . $partes_data[1] . '-' . $partes_data[0];
                    } else {
                        $data_nascimento = $data_nascimento_bruta;
                    }
                } else {
                    $data_nascimento = $data_nascimento_bruta;
                }
                
                $senha_provisoria = date('dmY', strtotime($data_nascimento));
                $senha_hash = md5($senha_provisoria);
                $saudacao = ($funcao == 'Professor') ? "Prof(a)." : (($funcao == 'Coordenador') ? "Coordenador(a)" : "Diretor(a)");
                
                $status_linha = "";
                $whatsapp_link_linha = "";
                
                try {
                    // Verifica duplicidade
                    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    if ($stmt->rowCount() > 0) {
                        $status_linha = "<span style='color:#e74c3c; font-weight:bold;'>Já Cadastrado</span>";
                    } else {
                        // Cadastra no banco
                        $sql = "INSERT INTO usuarios (nome, email, telefone_whatsapp, data_nascimento, funcao, senha, primeiro_acesso) VALUES (?, ?, ?, ?, ?, ?, 1)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$nome, $email, $whatsapp, $data_nascimento, $funcao, $senha_hash]);
                        
                        // Envio de E-mail
                        $assunto = "Acesso Liberado - Sistema HAE Fatec";
                        $corpo_email = "
                            <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
                                <div style='background-color: #b20000; padding: 20px; text-align: center; color: white;'>
                                    <h2 style='margin: 0; font-size: 20px;'>Sistema HAE - Fatec</h2>
                                </div>
                                <div style='padding: 20px;'>
                                    <h3 style='color: #b20000; margin-top: 0;'>Olá, $saudacao $nome.</h3>
                                    <p>O seu perfil institucional foi ativado com sucesso pela administração.</p>
                                    <p>Para efetuar o seu primeiro acesso à plataforma, utilize os dados de autenticação abaixo:</p>
                                    <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #b20000; margin: 15px 0;'>
                                        <ul style='margin: 0; padding-left: 20px;'>
                                            <li style='margin-bottom: 10px;'><strong>Usuário:</strong> $email</li>
                                            <li style='margin-bottom: 10px;'>
                                                <strong><img src='cid:img_texto_senha' alt='Senha Provisória' style='height: 12px; vertical-align: middle;'>:</strong> $senha_provisoria <em>(sua data de nascimento)</em>
                                            </li>
                                            <li>
                                                <strong>Endereço do portal:</strong> Digite o endereço da imagem abaixo na barra do seu navegador:<br><br>
                                                <img src='cid:img_link_portal' alt='Link de Acesso' style='max-width: 250px; border: 1px solid #ccc; border-radius: 4px;'>
                                            </li>
                                        </ul>
                                    </div>
                                    <p>Recomendamos que, após a entrada no sistema, você atualize seus dados de acesso e cadastre a sua assinatura digital na seção de perfil.</p>
                                    <p style='margin-top: 25px; font-size: 14px; color: #555;'>Atenciosamente,<br><strong>Gestão Acadêmica - Fatec</strong></p>
                                </div>
                            </div>
                        ";
                        
                        $lista_imagens = [
                            ['path' => __DIR__ . '/img/link_acesso.jpeg', 'cid' => 'img_link_portal'],
                            ['path' => __DIR__ . '/img/texto_senha.jpeg', 'cid' => 'img_texto_senha']
                        ];
                        
                        $email_enviado = dispararEmailSistema($email, $nome, $assunto, $corpo_email, $lista_imagens);
                        
                        if ($email_enviado) {
                            $status_linha = "<span style='color:#27ae60; font-weight:bold;'>Sucesso (E-mail Enviado)</span>";
                        } else {
                            $status_linha = "<span style='color:#f39c12; font-weight:bold;'>Salvo (Falha no E-mail)</span>";
                        }
                        
                        // Gera Link WhatsApp
                        $num_limpo = preg_replace('/\D/', '', $whatsapp);
                        if (substr($num_limpo, 0, 2) !== '55' && strlen($num_limpo) >= 10) $num_limpo = '55' . $num_limpo;
                        $msg = "Olá, $saudacao $nome! Seu acesso ao Portal HAE Fatec foi criado.\n\n*E-mail:* $email\n*Senha provisória:* $senha_provisoria (Sua data de nascimento)\n\nPor favor, acesse o sistema para completar seu perfil, cadastrar sua imagem de assinatura digital e criar uma nova senha definitiva.\nAcesse: http://sistemahae.page.gd/";
                        $whatsapp_link_linha = "https://wa.me/{$num_limpo}?text=" . urlencode($msg);
                    }
                    
                } catch (Exception $e) {
                    $status_linha = "<span style='color:#e74c3c;'>Erro no banco</span>";
                }
                
                // Guarda para exibir na tabela final
                $resultados_importacao[] = [
                    'nome' => $nome,
                    'email' => $email,
                    'funcao' => $funcao,
                    'status' => $status_linha,
                    'link_wa' => $whatsapp_link_linha
                ];
            }
        } else {
            $erro = "O arquivo enviado parece estar vazio ou com o formato corrompido.";
        }
    } else {
        $erro = "Nenhum dado recebido da planilha.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Usuários - Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- BIBLIOTECA SHEETJS (Lê Excel direto no navegador) -->
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

    <style>
        .tabs-container { margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .tab-btn { flex: 1; padding: 12px 25px; border: none; background: #e9ecef; color: #555; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 14px; text-align: center; }
        .tab-btn:hover { background: #dde2e6; }
        .tab-btn.active { background: var(--fatec-red); color: #fff; box-shadow: 0 4px 10px rgba(178,0,0,0.2); }

        .form-card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03); border-top: 4px solid var(--fatec-red); max-width: 800px; margin: 0 auto; display: none; }
        .form-card.active { display: block; animation: fadeIn 0.4s ease; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .input-group { margin-bottom: 20px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #444; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; transition: 0.3s; box-sizing: border-box; }
        input:focus, select:focus { border-color: var(--fatec-red); }

        .btn-submit { width: 100%; background: var(--fatec-red); color: white; padding: 15px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; transition: 0.3s; }
        .btn-submit:hover { background: #8a0000; }
        .btn-submit:disabled { background: #95a5a6; cursor: not-allowed; }

        .btn-whatsapp { display: inline-block; text-align: center; background: #25D366; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 12px; transition: 0.3s; width: 100%; box-sizing: border-box;}
        .btn-whatsapp:hover { background: #128C7E; }

        #idade_display { font-size: 12px; color: #27ae60; font-weight: bold; margin-top: 5px; display: none; }
        
        /* ESTILOS DA ÁREA DE IMPORTAÇÃO E TABELAS RESPONSIVAS */
        .info-box { background: #f8f9fa; border-left: 4px solid #3498db; padding: 20px; border-radius: 6px; margin-bottom: 25px; }
        .info-box h4 { margin-top: 0; color: #2c3e50; font-size: 16px; margin-bottom: 10px; }
        .info-box p { font-size: 13px; color: #555; margin-bottom: 10px; line-height: 1.5; }
        
        /* O segredo para tabelas não quebrarem no celular */
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-top: 15px; border-radius: 6px; border: 1px solid #eee; }
        
        .tabela-exemplo, .tabela-resultados { width: 100%; border-collapse: collapse; font-size: 12px; background: #fff; min-width: 600px; }
        .tabela-exemplo th, .tabela-exemplo td, .tabela-resultados th, .tabela-resultados td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: middle;}
        .tabela-exemplo th, .tabela-resultados th { background: #f1f3f5; color: #333; font-weight: bold; }
        
        .upload-area { border: 2px dashed #ccc; padding: 40px 20px; text-align: center; border-radius: 8px; background: #fafbfc; transition: 0.3s; cursor: pointer; margin-bottom: 20px; }
        .upload-area:hover { border-color: var(--fatec-red); background: #fff9f9; }
        .upload-area i { font-size: 40px; color: #bbb; margin-bottom: 15px; }
        .upload-area p { margin: 0; color: #666; font-size: 14px; font-weight: bold; }
        .upload-area span { font-size: 12px; color: #999; }
        #arquivo_excel { display: none; }
        
        /* MEDIA QUERIES PARA CELULAR */
        @media (max-width: 768px) {
            .form-card { padding: 20px 15px; }
            .grid-2 { grid-template-columns: 1fr; gap: 10px; }
            .tabs-container { flex-direction: column; }
            .upload-area { padding: 30px 15px; }
            .upload-area p { font-size: 13px; }
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
                <li><a href="painel.php" class="<?php echo ($pagina_atual == 'painel.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie"></i> <span class="menu-text">Dashboard</span></a></li>
                <?php if ($_SESSION['usuario_funcao'] == 'Professor'): ?>
                    <li><a href="nova_solicitacao.php"><i class="fa-solid fa-file-circle-plus"></i> <span class="menu-text">Nova Solicitação</span></a></li>
                    <li><a href="meus_projetos.php"><i class="fa-solid fa-folder-open"></i> <span class="menu-text">Meus Projetos</span></a></li>
                    <li><a href="enviar_relatorio.php"><i class="fa-solid fa-calendar-check"></i> <span class="menu-text">Enviar Relatório</span></a></li>
                    <li><a href="meus_rascunhos.php"><i class="fa-solid fa-file-pen"></i> <span class="menu-text">Meus Rascunhos</span></a></li>
                <?php else: ?>
                    <li><a href="analisar_solicitacoes.php"><i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">Analisar Solicitações</span></a></li>
                    <li><a href="acompanhar_relatorios.php"><i class="fa-solid fa-chart-line"></i> <span class="menu-text">Acompanhar Relatórios</span></a></li>
                    <li><a href="relatorios_atrasados.php"><i class="fa-solid fa-file-invoice"></i> <span class="menu-text">Relatórios Atrasados</span></a></li>
                    <?php if ($_SESSION['usuario_funcao'] == 'Diretor'): ?>
                        <li><a href="projetos_hae.php"><i class="fa-solid fa-list-check"></i> <span class="menu-text">Projetos HAE</span></a></li>
                        <li><a href="cadastrar_professor.php" class="active"><i class="fa-solid fa-user-plus"></i> <span class="menu-text">Cadastrar Usuário</span></a></li>
                        <li><a href="listar_usuarios.php"><i class="fa-solid fa-users"></i> <span class="menu-text">Lista de Usuários</span></a></li>
                    <?php endif; ?>
                <?php endif; ?>
                <li><a href="perfil.php"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>
                <?php if ($_SESSION['usuario_funcao'] == 'Diretor'): ?>
                    <li><a href="configuracoes.php"><i class="fa-solid fa-cogs"></i> <span class="menu-text">Configurações</span></a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span class="menu-text">Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Cadastrar Usuários</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <?php if ($erro) echo "<div class='alert-success' style='background:#fee2e2; color:#b91c1c; border-color:#b91c1c; margin-bottom: 20px;'>❌ $erro</div>"; ?>

        <!-- SE ACABOU DE PROCESSAR O LOTE, MOSTRA A TELA DE RESULTADOS -->
        <?php if (!empty($resultados_importacao)): ?>
            <div class="form-card active" style="max-width: 1000px;">
                <h2 style="color: var(--fatec-red); margin-top: 0; margin-bottom: 10px;"><i class="fa-solid fa-square-poll-vertical"></i> Relatório de Importação</h2>
                <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Abaixo estão os resultados do processamento da sua planilha.</p>
                
                <div class="table-responsive">
                    <table class="tabela-resultados">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Função</th>
                                <th>Status no Sistema</th>
                                <th style="text-align: center; width: 150px;">Plano B (WhatsApp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($resultados_importacao as $res): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($res['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($res['email']); ?></td>
                                    <td><?php echo htmlspecialchars($res['funcao']); ?></td>
                                    <td><?php echo $res['status']; ?></td>
                                    <td style="text-align: center;">
                                        <?php if(!empty($res['link_wa']) && strpos($res['status'], 'Já Cadastrado') === false): ?>
                                            <a href="<?php echo $res['link_wa']; ?>" target="_blank" class="btn-whatsapp"><i class="fa-brands fa-whatsapp"></i> Notificar</a>
                                        <?php else: ?>
                                            <span style="color:#aaa; font-size:11px;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <br>
                <div style="text-align: center;">
                    <a href="cadastrar_professor.php" style="background: #333; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;"><i class="fa-solid fa-arrow-left"></i> Voltar para Cadastros</a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- TELA NORMAL DE CADASTRO COM ABAS -->
            <div class="tabs-container">
                <button class="tab-btn active" onclick="switchTab('individual')"><i class="fa-solid fa-user-plus"></i> Cadastro Individual</button>
                <button class="tab-btn" onclick="switchTab('lote')"><i class="fa-solid fa-file-excel"></i> Importar Planilha (.xls, .xlsx, .csv)</button>
            </div>

            <!-- ABA 1: CADASTRO INDIVIDUAL -->
            <div class="form-card active" id="tab_individual">
                <?php if ($sucesso): ?>
                    <div class="alert-success" style="margin-bottom: 20px;">✅ <?php echo $sucesso; ?></div>
                    <p style="color: #666; font-size: 14px; text-align: center; margin-bottom: 20px;">
                        O e-mail foi disparado para <strong><?php echo htmlspecialchars($email); ?></strong>.<br>
                        Se preferir, você também pode enviar como um reforço pelo WhatsApp:
                    </p>
                    <a href="<?php echo $link_wa; ?>" target="_blank" class="btn-whatsapp" style="font-size: 15px; padding: 15px;">
                        <i class="fa-brands fa-whatsapp"></i> Enviar credenciais via WhatsApp
                    </a>
                    <br><br>
                    <div style="text-align: center;">
                        <a href="cadastrar_professor.php" style="color:var(--fatec-red); font-weight:bold; text-decoration:none;"><i class="fa-solid fa-arrow-rotate-left"></i> Cadastrar outro usuário</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="acao" value="individual">
                        <div class="input-group">
                            <label>Nome Completo</label>
                            <input type="text" name="nome" required>
                        </div>

                        <div class="grid-2">
                            <div class="input-group">
                                <label>E-mail Institucional</label>
                                <input type="email" name="email" required placeholder="exemplo@fatec.sp.gov.br">
                            </div>
                            <div class="input-group">
                                <label>Função no Sistema</label>
                                <select name="funcao" required>
                                    <option value="Professor">Professor(a)</option>
                                    <option value="Coordenador">Coordenador(a)</option>
                                    <option value="Diretor">Diretor(a) / ATA</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="input-group">
                                <label>Data de Nascimento</label>
                                <input type="date" name="data_nascimento" id="data_nascimento" required>
                                <div id="idade_display"></div>
                            </div>
                            <div class="input-group">
                                <label>Número do WhatsApp</label>
                                <input type="text" name="whatsapp" id="whatsapp" required placeholder="(00) 00000-0000">
                            </div>
                        </div>

                        <div style="background: #f8f9fa; padding: 12px; border-radius: 4px; font-size: 12px; color: #666; margin-bottom: 20px; border-left: 3px solid #ccc;">
                            <i class="fa-solid fa-circle-info"></i> A senha provisória será gerada automaticamente utilizando os números da data de nascimento (DDMMAAAA) e disparada via e-mail.
                        </div>

                        <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Finalizar Cadastro</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- ABA 2: IMPORTAÇÃO INTELIGENTE DE PLANILHA -->
            <div class="form-card" id="tab_lote" style="max-width: 800px;">
                <div class="info-box">
                    <h4><i class="fa-solid fa-circle-info"></i> Instruções para Importação Perfeita</h4>
                    <p>O sistema processa automaticamente arquivos do Excel <strong>(.xls, .xlsx)</strong> e arquivos <strong>.CSV</strong>. Sua planilha deve conter <strong>exatamente 5 colunas</strong>, e a primeira linha deve ser sempre o cabeçalho (que será ignorado pelo sistema).</p>
                    
                    <p style="margin-top: 15px;"><strong>Formato exigido das colunas (nesta ordem):</strong></p>
                    
                    <div class="table-responsive">
                        <table class="tabela-exemplo">
                            <thead>
                                <tr>
                                    <th>Coluna A</th>
                                    <th>Coluna B</th>
                                    <th>Coluna C</th>
                                    <th>Coluna D</th>
                                    <th>Coluna E</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Nome</strong></td>
                                    <td><strong>E-mail</strong></td>
                                    <td><strong>WhatsApp</strong></td>
                                    <td><strong>Data de Nasc.</strong></td>
                                    <td><strong>Função</strong></td>
                                </tr>
                                <tr style="color: #666; font-style: italic;">
                                    <td>Ana Silva</td>
                                    <td>ana@fatec.sp.gov.br</td>
                                    <td>(14) 99999-9999</td>
                                    <td>15/08/1980</td>
                                    <td>Professor</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <ul style="font-size: 12px; color: #e74c3c; margin-top: 15px; padding-left: 15px;">
                        <li>A Data de Nascimento deve preferencialmente estar no formato <strong>DD/MM/AAAA</strong>.</li>
                        <li>A Função deve ser escrita exatamente como: <strong>Professor</strong>, <strong>Coordenador</strong> ou <strong>Diretor</strong>.</li>
                    </ul>
                </div>

                <form method="POST" id="formImportacao">
                    <input type="hidden" name="acao" value="importar">
                    <input type="hidden" name="json_data" id="json_data" value="">
                    
                    <div class="upload-area" onclick="document.getElementById('arquivo_excel').click();">
                        <i class="fa-solid fa-file-excel" style="color: #27ae60;"></i>
                        <p id="upload_text">Clique aqui para selecionar a sua Planilha (Excel ou CSV)</p>
                        <span>Formatos aceitos: .xlsx, .xls, .csv</span>
                        <input type="file" id="arquivo_excel" accept=".xlsx, .xls, .csv" required onchange="atualizarNomeArquivo(this)">
                    </div>

                    <button type="submit" class="btn-submit" id="btn_processar_lote" style="background-color: #27ae60;"><i class="fa-solid fa-gears"></i> Processar Planilha e Cadastrar</button>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <script src="assets/js/painel.js"></script>
    <script>
        // Sistema de Abas
        function switchTab(tabId) {
            document.getElementById('tab_individual').classList.remove('active');
            document.getElementById('tab_lote').classList.remove('active');
            
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            if (tabId === 'individual') {
                document.getElementById('tab_individual').classList.add('active');
                document.querySelector('.tab-btn:nth-child(1)').classList.add('active');
            } else {
                document.getElementById('tab_lote').classList.add('active');
                document.querySelector('.tab-btn:nth-child(2)').classList.add('active');
            }
        }

        function atualizarNomeArquivo(input) {
            const texto = document.getElementById('upload_text');
            if (input.files && input.files.length > 0) {
                texto.innerHTML = "Arquivo selecionado: <strong style='color:#27ae60;'>" + input.files[0].name + "</strong>";
            } else {
                texto.innerHTML = "Clique aqui para selecionar a sua Planilha (Excel ou CSV)";
            }
        }

        // MOTOR DE LEITURA DO EXCEL (SheetJS)
        document.getElementById('formImportacao').addEventListener('submit', function(e) {
            e.preventDefault(); 
            
            const fileInput = document.getElementById('arquivo_excel');
            const file = fileInput.files[0];
            
            if (!file) {
                alert("Por favor, selecione um arquivo.");
                return;
            }

            const btnProcessar = document.getElementById('btn_processar_lote');
            btnProcessar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Lendo arquivo e processando envios...';
            btnProcessar.disabled = true;

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, {type: 'array'});
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    
                    const jsonArr = XLSX.utils.sheet_to_json(worksheet, {header: 1, defval: "", raw: false});
                    
                    document.getElementById('json_data').value = JSON.stringify(jsonArr);
                    
                    HTMLFormElement.prototype.submit.call(document.getElementById('formImportacao'));
                } catch (error) {
                    alert("Erro ao ler o arquivo Excel. Verifique se não está corrompido.");
                    btnProcessar.innerHTML = '<i class="fa-solid fa-gears"></i> Processar Planilha e Cadastrar';
                    btnProcessar.disabled = false;
                }
            };
            
            reader.readAsArrayBuffer(file);
        });

        // Máscara WhatsApp Individual
        const inputWhatsapp = document.getElementById('whatsapp');
        if (inputWhatsapp) {
            inputWhatsapp.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) value = value.slice(0, 11);
                if (value.length > 2) value = `(${value.slice(0, 2)}) ${value.slice(2)}`;
                if (value.length > 10) value = `${value.slice(0, 10)}-${value.slice(10)}`;
                e.target.value = value;
            });
        }

        // Cálculo de Idade Individual
        const inputDataNasc = document.getElementById('data_nascimento');
        if (inputDataNasc) {
            inputDataNasc.addEventListener('input', function () {
                let valData = this.value;
                let displayIdade = document.getElementById('idade_display');
                if (valData) {
                    let hoje = new Date();
                    let nascimento = new Date(valData);
                    nascimento.setMinutes(nascimento.getMinutes() + nascimento.getTimezoneOffset());
                    let idade = hoje.getFullYear() - nascimento.getFullYear();
                    let mes = hoje.getMonth() - nascimento.getMonth();
                    if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) idade--;
                    
                    if (idade >= 0) {
                        displayIdade.innerHTML = `<i class="fa-solid fa-user-clock"></i> Idade: ${idade} anos`;
                        displayIdade.style.display = 'block';
                    } else {
                        displayIdade.style.display = 'none';
                    }
                } else {
                    displayIdade.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>