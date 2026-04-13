// No topo do seu arquivo cadastro.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'path/to/PHPMailer/src/Exception.php';
require 'path/to/PHPMailer/src/PHPMailer.php';
require 'path/to/PHPMailer/src/SMTP.php';

function enviarEmailSenha($emailDestino, $nomeUser, $senhaProv) {
    $mail = new PHPMailer(true);
    try {
        // Configurações do Servidor (Exemplo Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'seu-email@gmail.com'; // Seu e-mail
        $mail->Password   = 'sua-senha-de-app';    // Senha de App do Google
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Destinatário
        $mail->setFrom('seu-email@gmail.com', 'Sistema HAE Fatec');
        $mail->addAddress($emailDestino, $nomeUser);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = 'Sua Senha Provisória - Sistema HAE';
        $mail->Body    = "Olá $nomeUser, seu cadastro foi realizado.<br>Sua senha provisória é: <b>$senhaProv</b>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}