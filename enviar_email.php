<?php
// Se usou Composer, descomente a linha abaixo e apague os requires manuais:
// require 'vendor/autoload.php';

// Se baixou manualmente, aponte para a pasta correta:
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'libs/PHPMailer/src/Exception.php';
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';

function dispararEmailSenha($emailDestino, $nome, $senhaProvisoria) {
    $mail = new PHPMailer(true);

    try {
        // Configurações do Servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'seu.email.fatec@gmail.com'; // O seu e-mail do Gmail
        $mail->Password   = 'sua-senha-de-app-de-16-digitos'; // A Senha de App gerada
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Tente ENCRYPTION_STARTTLS e porta 587 se falhar
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // Remetente e Destinatário
        $mail->setFrom('seu.email.fatec@gmail.com', 'Sistema HAE - Direção');
        $mail->addAddress($emailDestino, $nome);

        // Conteúdo do E-mail
        $mail->isHTML(true);
        $mail->Subject = 'Acesso ao Sistema HAE - Sua Senha Provisória';
        
        // Corpo do E-mail em HTML
        $mail->Body = "
            <h2>Olá, Prof(a). $nome,</h2>
            <p>Seu cadastro no portal de Horas Atividades Específicas foi concluído.</p>
            <p>Sua senha provisória de acesso é: <strong>$senhaProvisoria</strong></p>
            <p><em>Por motivos de segurança, o sistema exigirá a troca desta senha no seu primeiro login.</em></p>
            <br>
            <p>Atenciosamente,<br>Direção Fatec</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Para debugar, você pode dar echo em $mail->ErrorInfo
        return false;
    }
}
?>