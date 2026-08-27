<?php
session_start();
require 'config/conexao.php';
require_once 'enviar_email.php'; // Chama a nossa nova central de e-mails

// Apenas Direção e Coordenação podem acessar
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_funcao'], ['Coordenador', 'Diretor'])) {
    header("Location: painel.php");
    exit;
}

$sucesso = "";
$erro = "";
$link_wa = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $whatsapp = trim($_POST['whatsapp']);
    $data_nascimento = $_POST['data_nascimento'];
    $funcao = $_POST['funcao'];

    // Gera a senha provisória baseada na data de nascimento (DDMMAAAA)
    $senha_provisoria = date('dmY', strtotime($data_nascimento));
    $senha_hash = md5($senha_provisoria);

    // Formata a saudação baseada na função
    $saudacao = ($funcao == 'Professor') ? "Prof(a)." : (($funcao == 'Coordenador') ? "Coordenador(a)" : "Diretor(a)");

    try {
        // Verifica se o email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $erro = "Este e-mail já está cadastrado no sistema.";
        } else {
            // Insere no banco
            $sql = "INSERT INTO usuarios (nome, email, telefone_whatsapp, data_nascimento, funcao, senha, primeiro_acesso) 
                    VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $whatsapp, $data_nascimento, $funcao, $senha_hash]);

// =========================================================================
            // DISPARO AUTOMÁTICO DE E-MAIL COM MÚLTIPLAS IMAGENS
            // =========================================================================
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
            
            // Monta a lista de imagens para mandar para a função
            $lista_imagens = [
                ['path' => 'img/link_acesso.jpeg', 'cid' => 'img_link_portal'],
                ['path' => 'img/texto_senha.jpeg', 'cid' => 'img_texto_senha']
            ];
            
            // Tenta enviar o e-mail passando a lista de imagens
            $email_enviado = dispararEmailSistema($email, $nome, $assunto, $corpo_email, $lista_imagens);
            // =========================================================================

            // Gera o link do WhatsApp (Plano B)
            $num_limpo = preg_replace('/\D/', '', $whatsapp);
            if (substr($num_limpo, 0, 2) !== '55')
                $num_limpo = '55' . $num_limpo;
            $msg = "Olá, $saudacao $nome! Seu acesso ao Portal HAE Fatec foi criado.\n\n*E-mail:* $email\n*Senha provisória:* $senha_provisoria (Sua data de nascimento)\n\nPor favor, acesse o sistema para completar seu perfil, cadastrar sua imagem de assinatura digital e criar uma nova senha definitiva.\nAcesse: http://sistemahae.page.gd/";
            $link_wa = "https://wa.me/{$num_limpo}?text=" . urlencode($msg);

            // Mensagem de feedback dinâmica
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

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Usuário - Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-card {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
            border-top: 4px solid var(--fatec-red);
            max-width: 600px;
            margin: 0 auto;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 13px;
            color: #444;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        input:focus,
        select:focus {
            border-color: var(--fatec-red);
        }

        .btn-submit {
            width: 100%;
            background: var(--fatec-red);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #8a0000;
        }

        .btn-whatsapp {
            display: block;
            text-align: center;
            background: #25D366;
            color: white;
            padding: 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 15px;
            transition: 0.3s;
        }

        .btn-whatsapp:hover {
            background: #128C7E;
        }

        #idade_display {
            font-size: 12px;
            color: #27ae60;
            font-weight: bold;
            margin-top: 5px;
            display: none;
        }
    </style>
<!-- FIREBASE PUSH NOTIFICATIONS -->
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
              messaging.getToken({ vapidKey: "BEgkKtj6Eq-ttKtvBL3xOoIoyAAdwiWxOLLygWTlwBSEqWx8AY5oZsvFRY033g71NhAhDKg_kcYEErTiE0cbmoE" })
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
                <li><a href="painel.php" class="<?php echo ($pagina_atual == 'painel.php') ? 'active' : ''; ?>"><i
                            class="fa-solid fa-chart-pie"></i> <span class="menu-text">Dashboard</span></a></li>

                <?php if ($_SESSION['usuario_funcao'] == 'Professor'): ?>
                    <li><a href="nova_solicitacao.php"
                            class="<?php echo ($pagina_atual == 'nova_solicitacao.php') ? 'active' : ''; ?>"><i
                                class="fa-solid fa-file-circle-plus"></i> <span class="menu-text">Nova
                                Solicitação</span></a></li>
                    <li><a href="meus_projetos.php"
                            class="<?php echo ($pagina_atual == 'meus_projetos.php') ? 'active' : ''; ?>"><i
                                class="fa-solid fa-folder-open"></i> <span class="menu-text">Meus Projetos</span></a></li>
                    <li><a href="enviar_relatorio.php"
                            class="<?php echo ($pagina_atual == 'enviar_relatorio.php') ? 'active' : ''; ?>"><i
                                class="fa-solid fa-calendar-check"></i> <span class="menu-text">Enviar Relatório</span></a>
                    </li>
                    <li><a href="meus_rascunhos.php"
                            class="<?php echo ($pagina_atual == 'meus_rascunhos.php') ? 'active' : ''; ?>"><i
                                class="fa-solid fa-file-pen"></i> <span class="menu-text">Meus Rascunhos</span></a></li>
                <?php else: ?>
                    <li><a href="analisar_solicitacoes.php"
                            class="<?php echo ($pagina_atual == 'analisar_solicitacoes.php') ? 'active' : ''; ?>"><i
                                class="fa-solid fa-clipboard-check"></i> <span class="menu-text">Analisar
                                Solicitações</span></a></li>
                    <li><a href="acompanhar_relatorios.php"
                            class="<?php echo ($pagina_atual == 'acompanhar_relatorios.php') ? 'active' : ''; ?>"><i
                                class="fa-solid fa-chart-line"></i> <span class="menu-text">Acompanhar Relatórios</span></a>
                    </li>
                    <li><a href="relatorios_atrasados.php"
                            class="<?php echo ($pagina_atual == 'relatorios_atrasados.php') ? 'active' : ''; ?>"><i
                                class="fa-solid fa-file-invoice"></i> <span class="menu-text">Relatórios
                                Atrasados</span></a></li>

                    <?php if ($_SESSION['usuario_funcao'] == 'Diretor'): ?>
                        <li><a href="projetos_hae.php"
                                class="<?php echo ($pagina_atual == 'projetos_hae.php') ? 'active' : ''; ?>"><i
                                    class="fa-solid fa-list-check"></i> <span class="menu-text">Projetos HAE</span></a></li>
                        <li><a href="cadastrar_professor.php"
                                class="<?php echo ($pagina_atual == 'cadastrar_professor.php') ? 'active' : ''; ?>"><i
                                    class="fa-solid fa-user-plus"></i> <span class="menu-text">Cadastrar Usuário</span></a></li>
                        <li><a href="listar_usuarios.php"
                                class="<?php echo ($pagina_atual == 'listar_usuarios.php') ? 'active' : ''; ?>"><i
                                    class="fa-solid fa-users"></i> <span class="menu-text">Lista de Usuários</span></a></li>
                    <?php endif; ?>
                <?php endif; ?>

                <li><a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>"><i
                            class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>

                <?php if ($_SESSION['usuario_funcao'] == 'Diretor'): ?>
                    <li><a href="configuracoes.php"
                            class="<?php echo ($pagina_atual == 'configuracoes.php') ? 'active' : ''; ?>"><i
                                class="fa-solid fa-cogs"></i> <span class="menu-text">Configurações</span></a></li>
                <?php endif; ?>

                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span
                            class="menu-text">Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Cadastrar Novo Usuário</h1>
            </div>
        </header>

        <?php if ($erro)
            echo "<div class='alert-success' style='background:#fee2e2; color:#b91c1c; border-color:#b91c1c;'>❌ $erro</div>"; ?>

        <div class="form-card">
            <?php if ($sucesso): ?>
                <div class="alert-success" style="margin-bottom: 20px;">✅
                    <?php echo $sucesso; ?>
                </div>
                <p style="color: #666; font-size: 14px; text-align: center; margin-bottom: 20px;">
                    O e-mail foi disparado para <strong>
                        <?php echo htmlspecialchars($email); ?>
                    </strong>.<br>
                    Se preferir, você também pode enviar como um reforço pelo WhatsApp:
                </p>
                <a href="<?php echo $link_wa; ?>" target="_blank" class="btn-whatsapp">
                    <i class="fa-brands fa-whatsapp"></i> Plano B: Enviar via WhatsApp
                </a>
                <a href="cadastrar_professor.php"
                    style="display:block; text-align:center; margin-top:20px; color:var(--fatec-red); font-weight:bold; text-decoration:none;">←
                    Cadastrar outro usuário</a>
            <?php else: ?>
                <form method="POST">
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

                    <div
                        style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px; color: #666; margin-bottom: 20px; border-left: 3px solid #ccc;">
                        <i class="fa-solid fa-circle-info"></i> A senha provisória será gerada automaticamente utilizando os
                        números da data de nascimento (DDMMAAAA) e disparada via e-mail.
                    </div>

                    <button type="submit" class="btn-submit"><i class="fa-solid fa-user-check"></i> Registrar
                        Usuário</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/js/painel.js"></script>
    <script>
        // Verifica se o campo existe na tela antes de aplicar a máscara
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

        // Verifica se o campo existe na tela antes de calcular a idade
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

                    if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
                        idade--;
                    }

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