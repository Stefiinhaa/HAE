<?php
require 'vendor/PHPMailer/Exception.php';
require 'vendor/PHPMailer/PHPMailer.php';
require 'vendor/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h3>Iniciando Teste de E-mail...</h3>";

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // AQUI É O SEGREDO: Vai imprimir toda a conversa com o Google na tela
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    $mail->Username   = 'sistemahae@gmail.com'; 
    $mail->Password   = 'rupwbxsotdmwwnky'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
    $mail->Port       = 465;                         
    $mail->CharSet    = 'UTF-8';

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom('sistemahae@gmail.com', 'HAE Fatec Garça'); 
    $mail->addAddress('stefanisantos1212@gmail.com', 'Stefani Teste');

    $mail->isHTML(true);
    $mail->Subject = 'Teste Diagnostico HAE';
    $mail->Body    = 'Se você recebeu isso, o servidor está perfeito!';

    $mail->send();
    echo "<br><br><strong style='color:green;'>✅ E-mail enviado com sucesso!</strong>";
} catch (Exception $e) {
    echo "<br><br><strong style='color:red;'>❌ O PHPMailer falhou: {$mail->ErrorInfo}</strong>";
}
?>