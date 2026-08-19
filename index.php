<?php
session_start();
require 'config/conexao.php';

// Importa a central de envios de e-mail para usar na recuperação de senha
require_once 'enviar_email.php'; 

$erro = "";
$sucesso = "";
$email_digitado = ""; // Memória para o campo de login
$email_rec_digitado = ""; // Memória para o campo de recuperação

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ==============================================================================
    // LÓGICA DE RECUPERAÇÃO DE SENHA (ESQUECI A SENHA)
    // ==============================================================================
    if (isset($_POST['acao']) && $_POST['acao'] == 'esqueci_senha') {
        $email_rec = trim($_POST['email_recuperacao']);
        $email_rec_digitado = $email_rec; // Salva o que foi digitado
        
        $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE email = ?");
        $stmt->execute([$email_rec]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Gera uma senha aleatória provisória de 8 caracteres
            $nova_senha_temp = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
            $senha_hash = md5($nova_senha_temp);

            // Atualiza a senha no banco e força a tela de "Redefinir Senha" no próximo login
            $stmt_upd = $pdo->prepare("UPDATE usuarios SET senha = ?, primeiro_acesso = 2 WHERE id = ?");
            $stmt_upd->execute([$senha_hash, $user['id']]);

            // Formatação Profissional Idêntica ao cadastrar_professor.php
            $nome_usuario = $user['nome'];
            $email_usuario = $user['email'];
            $assunto = "Acesso Liberado - Sistema HAE Fatec";
            
            $corpo_email = "
                <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
                    <div style='background-color: #b20000; padding: 20px; text-align: center; color: white;'>
                        <h2 style='margin: 0; font-size: 20px;'>Sistema HAE - Fatec</h2>
                    </div>
                    <div style='padding: 20px;'>
                        <h3 style='color: #b20000; margin-top: 0;'>Olá, $nome_usuario.</h3>
                        <p>Recebemos uma solicitação de recuperação de senha para a sua conta no Portal HAE da Fatec.</p>
                        
                        <p>Para efetuar o seu acesso à plataforma, utilize os dados de autenticação abaixo:</p>
                        
                        <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #b20000; margin: 15px 0;'>
                            <ul style='margin: 0; padding-left: 20px;'>
                                <li style='margin-bottom: 10px;'><strong>Usuário:</strong> $email_usuario</li>
                                <li style='margin-bottom: 10px;'>
                                    <strong><img src='cid:img_texto_senha' alt='Senha Provisória' style='height: 12px; vertical-align: middle;'>:</strong> $nova_senha_temp
                                </li>
                                <li>
                                    <strong>Endereço do portal:</strong> Digite o endereço da imagem abaixo na barra do seu navegador:<br><br>
                                    <img src='cid:img_link_portal' alt='Link de Acesso' style='max-width: 250px; border: 1px solid #ccc; border-radius: 4px;'>
                                </li>
                            </ul>
                        </div>
                        
                        <p>Após entrar no sistema com esta senha provisória, você será redirecionado imediatamente para criar uma nova senha definitiva e restabelecer sua segurança.</p>
                        
                        <p style='font-size: 13px; color: #777; border-top: 1px solid #eee; padding-top: 15px;'>Se você não solicitou esta alteração, por favor ignore este e-mail ou avise a direção.</p>
                        
                        <p style='margin-top: 25px; font-size: 14px; color: #555;'>Atenciosamente,<br><strong>Gestão Acadêmica - Fatec</strong></p>
                    </div>
                </div>
            ";
            
            // Monta a lista de imagens idêntica à do cadastro
            $lista_imagens = [
                ['path' => 'img/link_acesso.jpeg', 'cid' => 'img_link_portal'],
                ['path' => 'img/texto_senha.jpeg', 'cid' => 'img_texto_senha']
            ];
            
            // Dispara o e-mail
            dispararEmailSistema($user['email'], $user['nome'], $assunto, $corpo_email, $lista_imagens);
            
            $sucesso = "As instruções de recuperação foram enviadas para o seu e-mail institucional. <strong>Por favor, verifique também a sua caixa de Spam ou Lixo Eletrônico.</strong>";
            $email_rec_digitado = ""; // Limpa a memória após o sucesso
        } else {
            $erro = "Não encontramos nenhum usuário com este e-mail no sistema.";
            echo "<script>window.onload = function() { alternarForms('recuperar'); }</script>"; // Mantém na tela de recuperação
        }
    } 
    // ==============================================================================
    // LÓGICA DE LOGIN NORMAL
    // ==============================================================================
    else {
        $email = trim($_POST['email']);
        $email_digitado = $email; // Salva o e-mail digitado na memória
        $senha = md5(trim($_POST['senha']));

        $sql = "SELECT * FROM usuarios WHERE email = ? AND senha = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $senha]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['primeiro_acesso'] = $usuario['primeiro_acesso'];

            $nomes_admin = ['Administrador', 'Administrador MD5'];
            $funcoes_validas = ['Professor', 'Coordenador', 'Diretor'];

            // God Mode (Simulação de Perfil)
            if (in_array($usuario['nome'], $nomes_admin) && !empty($_POST['simular_funcao']) && in_array($_POST['simular_funcao'], $funcoes_validas)) {
                $_SESSION['usuario_funcao'] = $_POST['simular_funcao']; 
            } else {
                $_SESSION['usuario_funcao'] = $usuario['funcao']; 
            }

            // Desvios de Rota
            if ($usuario['primeiro_acesso'] == 1) {
                header("Location: completar_cadastro.php");
                exit;
            } elseif ($usuario['primeiro_acesso'] == 2) {
                header("Location: redefinir_senha.php");
                exit;
            } else {
                header("Location: painel.php");
                exit;
            }
        } else {
            $erro = "E-mail ou senha inválidos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portal HAE Fatec</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --fatec-red: #b20000;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-box {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
            border-top: 6px solid var(--fatec-red);
            transition: 0.3s;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            font-size: 24px;
        }

        h2 span {
            color: var(--fatec-red);
        }

        .input-group {
            margin-bottom: 18px;
            position: relative;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #444;
        }

        input,
        select {
            width: 100%;
            padding: 12px 40px 12px 12px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            outline: none;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        input:focus,
        select:focus {
            border-color: var(--fatec-red);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--fatec-red);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 5px;
            font-size: 15px;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #8a0000;
            transform: translateY(-1px);
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
            border-left: 4px solid #b91c1c;
        }
        
        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: left;
            border-left: 4px solid #198754;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 34px;
            cursor: pointer;
            width: 20px;
            opacity: 0.5;
            transition: 0.3s;
        }

        .toggle-password:hover {
            opacity: 1;
        }

        .link-esqueci {
            display: block;
            text-align: right;
            font-size: 12px;
            color: var(--fatec-red);
            text-decoration: none;
            margin-top: -10px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .link-esqueci:hover { text-decoration: underline; }

        .dev-mode-box {
            display: none;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #ccc;
        }

        .dev-mode-box select {
            background: #f8f9fa;
            color: #555;
            cursor: pointer;
            padding: 10px;
            font-size: 14px;
        }
</style>
<!-- INTEGRAÇÃO ONESIGNAL (PUSH NOTIFICATIONS) -->
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
  window.OneSignalDeferred = window.OneSignalDeferred || [];
  OneSignalDeferred.push(async function(OneSignal) {
    await OneSignal.init({
      appId: "f3a9b7ad-ba4b-420c-8290-99f87501f1a3", // Seu App ID
      
      // DEIXA O SININHO EM PORTUGUÊS
      notifyButton: {
        enable: true,
        text: {
            'tip.state.unsubscribed': 'Ativar notificações',
            'tip.state.subscribed': 'Você está inscrito',
            'tip.state.blocked': 'Você bloqueou as notificações',
            'message.prenotify': 'Clique para receber notificações',
            'message.action.subscribed': 'Obrigado por se inscrever!',
            'message.action.resubscribed': 'Você está inscrito novamente',
            'message.action.unsubscribed': 'Você não receberá mais avisos',
            'dialog.main.title': 'Notificações HAE',
            'dialog.main.button.subscribe': 'INSCREVER-SE',
            'dialog.main.button.unsubscribe': 'CANCELAR INSCRIÇÃO',
            'dialog.blocked.title': 'Desbloquear Notificações',
            'dialog.blocked.message': 'Siga as instruções para permitir notificações:'
        }
      },
      
      // DEIXA O AVISO DO MEIO DA TELA EM PORTUGUÊS
      promptOptions: {
        slidedown: {
          prompts: [{
            type: "push",
            autoPrompt: true,
            text: {
              actionMessage: "Gostaríamos de enviar avisos importantes sobre seus projetos HAE e prazos de relatórios.",
              acceptButton: "Permitir",
              cancelButton: "Agora Não"
            },
            delay: {
              pageViews: 1,
              timeDelay: 2
            }
          }]
        }
      }
    });

    // Registra o ID apenas se o usuário estiver logado
    <?php if(isset($_SESSION['usuario_id'])): ?>
        OneSignal.login("<?php echo $_SESSION['usuario_id']; ?>");
    <?php endif; ?>
  });
</script>
</head>

<body>

    <div class="login-box">
        <h2>Portal <span>HAE</span></h2>
        
        <?php if ($erro) echo "<div class='error'><i class='fa-solid fa-circle-exclamation'></i> $erro</div>"; ?>
        <?php if ($sucesso) echo "<div class='success'><i class='fa-solid fa-circle-check'></i> $sucesso</div>"; ?>

        <!-- FORMULÁRIO 1: LOGIN -->
        <form method="POST" id="formLogin">
            <input type="hidden" name="acao" value="login">
            
            <div class="input-group">
                <label>E-mail Institucional</label>
                <!-- O VALOR É PREENCHIDO AUTOMATICAMENTE SE HOUVER ERRO -->
                <input type="email" name="email" id="email" required placeholder="exemplo@cps.sp.gov.br" value="<?php echo htmlspecialchars($email_digitado); ?>">
            </div>

            <div class="input-group">
                <label>Senha</label>
                <!-- A SENHA SEMPRE FICA VAZIA POR SEGURANÇA -->
                <input type="password" name="senha" id="senha" required placeholder="Sua senha">
                <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye.svg"
                    class="toggle-password" onclick="toggleVisibility('senha', this)" alt="Ver senha">
            </div>
            
            <a href="#" class="link-esqueci" onclick="alternarForms('recuperar')">Esqueceu a senha?</a>

            <!-- Dropdown de Homologação (Escondido via CSS) -->
            <div class="input-group dev-mode-box" id="caixa_dev">
                <label style="color: #7f8c8d;"><i class="fa-solid fa-code"></i> Ambiente de Homologação</label>
                <select name="simular_funcao" id="select_funcao">
                    <option value="">Acesso Normal (Meu Perfil)</option>
                    <option value="Professor">Forçar Login como: Professor</option>
                    <option value="Coordenador">Forçar Login como: Coordenador</option>
                    <option value="Diretor">Forçar Login como: Diretor</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Entrar no Sistema</button>
        </form>
        
        <!-- FORMULÁRIO 2: RECUPERAÇÃO DE SENHA (Oculto por padrão) -->
        <form method="POST" id="formRecuperar" style="display: none;">
            <input type="hidden" name="acao" value="esqueci_senha">
            
            <p style="font-size: 13px; color: #666; margin-bottom: 20px; text-align: center;">Digite seu e-mail institucional abaixo. Nós enviaremos uma nova senha provisória de acesso.</p>
            
            <div class="input-group">
                <label>E-mail Institucional</label>
                <input type="email" name="email_recuperacao" required placeholder="exemplo@cps.sp.gov.br" value="<?php echo htmlspecialchars($email_rec_digitado); ?>">
            </div>
            
            <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Enviar Nova Senha</button>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="#" onclick="alternarForms('login')" style="font-size: 13px; color: #555; text-decoration: none; font-weight: bold;"><i class="fa-solid fa-arrow-left"></i> Voltar para o Login</a>
            </div>
        </form>
    </div>

    <script>
        // Alternar entre formulário de Login e de Recuperação
        function alternarForms(alvo) {
            const formLogin = document.getElementById('formLogin');
            const formRecuperar = document.getElementById('formRecuperar');
            
            if (alvo === 'recuperar') {
                formLogin.style.display = 'none';
                formRecuperar.style.display = 'block';
            } else {
                formRecuperar.style.display = 'none';
                formLogin.style.display = 'block';
            }
        }

        // Função para mostrar/esconder a senha
        function toggleVisibility(inputId, element) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                element.src = "https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye-slash.svg";
            } else {
                input.type = "password";
                element.src = "https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular/eye.svg";
            }
        }

        // Função que fica espionando o campo de e-mail (God Mode)
        document.getElementById('email').addEventListener('input', function (e) {
            const emailDigitado = e.target.value.toLowerCase().trim();
            const caixaDev = document.getElementById('caixa_dev');
            const selectFuncao = document.getElementById('select_funcao');

            if (emailDigitado === 'admin@fatec.com' || emailDigitado === 'admin_md5@fatec.com') {
                caixaDev.style.display = 'block'; 
            } else {
                caixaDev.style.display = 'none'; 
                selectFuncao.value = ''; 
            }
        });
    </script>
</body>

</html>