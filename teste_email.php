<?php
require_once __DIR__ . '/config/seguranca.php';
require_once __DIR__ . '/config/segredos.php';
require_once __DIR__ . '/vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// SEGURANÇA: ferramenta de diagnóstico restrita ao administrador do sistema.
// (Antes era pública: qualquer pessoa podia disparar e-mails e ver dados da conexão SMTP.)
$nomes_admin = ['Administrador', 'Administrador MD5'];
if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_nome'], $nomes_admin, true)) {
    http_response_code(403);
    die("Acesso restrito a desenvolvedores.");
}
hae_limitar_requisicoes('teste_email:' . hae_ip(), 5, 3600);

echo "<h3>Iniciando Teste de E-mail...</h3>";

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // AQUI É O SEGREDO: Vai imprimir toda a conversa com o Google na tela
    // Mascara credenciais (base64) que aparecem na conversa SMTP
    $mail->Debugoutput = function ($str, $level) {
        $str = preg_replace('/^(CLIENT -> SERVER: )[A-Za-z0-9+\/=]{12,}\s*$/m', '$1[oculto]', $str);
        echo h($str) . "<br>";
    };
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST; 
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USUARIO; 
    $mail->Password   = SMTP_SENHA; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
    $mail->Port       = 465;                         
    $mail->CharSet    = 'UTF-8';

    if (hae_ambiente_local()) {
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
    }

    $mail->setFrom(SMTP_USUARIO, SMTP_REMETENTE_NOME); 
    $mail->addAddress('stefanisantos1212@gmail.com', 'Stefani Teste');

    $mail->isHTML(true);
    $mail->Subject = 'Teste Diagnostico HAE';
    $mail->Body    = 'Se você recebeu isso, o servidor está perfeito!';

    $mail->send();
    echo "<br><br><strong style='color:green;'>✅ E-mail enviado com sucesso!</strong>";
} catch (Exception $e) {
    echo "<br><br><strong style='color:red;'>❌ O PHPMailer falhou: " . h($mail->ErrorInfo) . "</strong>";
}
?>