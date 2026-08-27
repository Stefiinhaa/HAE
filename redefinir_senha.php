<?php
session_start();
require 'config/conexao.php';

// Se não tiver feito login inicial, manda pro index
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    if (strlen($nova_senha) < 6) {
        $erro = "Sua nova senha deve ter no mínimo 6 caracteres.";
    } elseif ($nova_senha !== $confirma_senha) {
        $erro = "As senhas não coincidem. Digite com atenção e tente novamente.";
    } else {
        try {
            $senha_criptografada = md5($nova_senha);
            // Atualiza a senha e retira a trava de redefinição obrigatória
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, primeiro_acesso = 0 WHERE id = ?");
            $stmt->execute([$senha_criptografada, $_SESSION['usuario_id']]);

            // Redireciona o usuário recém-desbloqueado para o Painel Principal
            header("Location: painel.php");
            exit;
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar o banco de dados: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha - HAE Fatec</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background-color: #f4f6f9; display: flex; align-items: center; 
            justify-content: center; height: 100vh; margin: 0; font-family: 'Arial', sans-serif; 
        }
        .login-card { 
            background: #fff; width: 100%; max-width: 420px; padding: 40px; 
            border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            border-top: 5px solid #b20000; text-align: center; box-sizing: border-box;
        }
        .login-card img { max-width: 150px; margin-bottom: 20px; }
        .login-card h2 { color: #333; font-size: 22px; margin-bottom: 10px; }
        .login-card p { color: #666; font-size: 14px; margin-bottom: 30px; line-height: 1.5; }
        
        .input-group { text-align: left; margin-bottom: 20px; position: relative; }
        .input-group label { display: block; font-weight: bold; color: #555; font-size: 13px; margin-bottom: 5px; }
        
        /* CONTAINER DO INPUT COM ÍCONE */
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { width: 100%; padding: 12px; padding-right: 40px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; transition: 0.3s; box-sizing: border-box; }
        .password-wrapper input:focus { border-color: #b20000; box-shadow: 0 0 5px rgba(178, 0, 0, 0.2); }
        .toggle-password { position: absolute; right: 12px; color: #888; cursor: pointer; transition: 0.3s; font-size: 16px; }
        .toggle-password:hover { color: #b20000; }
        
        /* BARRA DE FORÇA DE SENHA */
        .strength-container { margin-top: 8px; width: 100%; height: 6px; background-color: #eee; border-radius: 3px; overflow: hidden; }
        .strength-bar { height: 100%; width: 0%; border-radius: 3px; transition: width 0.4s ease, background-color 0.4s ease; }
        .feedback-text { display: flex; justify-content: space-between; font-size: 11px; margin-top: 5px; font-weight: bold; color: #888; }
        
        .btn-submit { width: 100%; background: #b20000; color: white; border: none; padding: 14px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;}
        .btn-submit:hover { background: #8a0000; }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; }
        
        .alert-error { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; text-align: left; border-left: 4px solid #b91c1c; }
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
    <div class="login-card">
        <img src="img/cps_fatecgarca_logo.jfif" alt="Fatec Logo">
        <h2>Atualização de Segurança</h2>
        <p>Sua senha provisória precisa ser alterada. Crie agora a sua nova senha definitiva de acesso ao portal HAE.</p>
        
        <?php if($erro): ?>
            <div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST" id="formReset">
            <div class="input-group">
                <label>Nova Senha Definitiva</label>
                <div class="password-wrapper">
                    <input type="password" name="nova_senha" id="nova_senha" placeholder="Mínimo 6 caracteres" required autofocus>
                    <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('nova_senha', this)"></i>
                </div>
                <div class="strength-container">
                    <div class="strength-bar" id="barra-forca"></div>
                </div>
                <div class="feedback-text">
                    <span id="dica-senha">Letras, números e símbolos fortalecem a senha</span>
                    <span id="texto-forca"></span>
                </div>
            </div>

            <div class="input-group">
                <label>Confirme a Nova Senha</label>
                <div class="password-wrapper">
                    <input type="password" name="confirma_senha" id="confirma_senha" placeholder="Repita a senha" required>
                    <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('confirma_senha', this)"></i>
                </div>
                <div class="feedback-text" style="justify-content: flex-end;">
                    <span id="texto-match"></span>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="btnSalvar"><i class="fa-solid fa-floppy-disk"></i> Salvar e Acessar o Painel</button>
        </form>
    </div>

    <script>
        // Função para alternar visibilidade (Ver Senha)
        function toggleVisibility(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Elementos DOM
        const inputSenha = document.getElementById('nova_senha');
        const inputConfirma = document.getElementById('confirma_senha');
        const barraForca = document.getElementById('barra-forca');
        const textoForca = document.getElementById('texto-forca');
        const textoMatch = document.getElementById('texto-match');
        const btnSalvar = document.getElementById('btnSalvar');

        // Validação da Força da Senha
        inputSenha.addEventListener('input', function() {
            const senha = inputSenha.value;
            let forca = 0;
            
            if (senha.length > 0) {
                // Pontuação baseada em critérios
                if (senha.length >= 6) forca += 25;
                if (senha.length >= 8) forca += 25;
                if (/[A-Z]/.test(senha) || /[a-z]/.test(senha)) forca += 15;
                if (/[0-9]/.test(senha)) forca += 15;
                if (/[^A-Za-z0-9]/.test(senha)) forca += 20;

                // Limita a 100%
                if (forca > 100) forca = 100;
                
                barraForca.style.width = forca + '%';

                // Aplica Cores e Textos
                if (forca < 40) {
                    barraForca.style.backgroundColor = '#e74c3c'; // Vermelho
                    textoForca.textContent = 'Fraca';
                    textoForca.style.color = '#e74c3c';
                } else if (forca < 75) {
                    barraForca.style.backgroundColor = '#f39c12'; // Laranja
                    textoForca.textContent = 'Média';
                    textoForca.style.color = '#f39c12';
                } else {
                    barraForca.style.backgroundColor = '#2ecc71'; // Verde
                    textoForca.textContent = 'Forte';
                    textoForca.style.color = '#2ecc71';
                }
            } else {
                barraForca.style.width = '0%';
                textoForca.textContent = '';
            }
            
            verificarMatch();
        });

        // Validação de Confirmação em Tempo Real
        function verificarMatch() {
            if (inputConfirma.value.length > 0) {
                if (inputSenha.value === inputConfirma.value) {
                    textoMatch.innerHTML = '<i class="fa-solid fa-check"></i> Senhas coincidem';
                    textoMatch.style.color = '#2ecc71';
                } else {
                    textoMatch.innerHTML = '<i class="fa-solid fa-xmark"></i> As senhas não coincidem';
                    textoMatch.style.color = '#e74c3c';
                }
            } else {
                textoMatch.innerHTML = '';
            }
        }

        inputConfirma.addEventListener('input', verificarMatch);
    </script>
</body>
</html>