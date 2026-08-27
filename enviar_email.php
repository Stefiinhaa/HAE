<?php
// Carrega os arquivos do PHPMailer
require 'vendor/PHPMailer/Exception.php';
require 'vendor/PHPMailer/PHPMailer.php';
require 'vendor/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Função global para disparo de e-mails do sistema HAE usando Gmail
 */
function dispararEmailSistema($destinatario, $nome_destinatario, $assunto, $corpo_html, $imagens_embutidas = []) {
    $mail = new PHPMailer(true);

    try {
        // Configurações do Servidor SMTP do Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sistemahae@gmail.com'; 
        $mail->Password   = 'rupwbxsotdmwwnky'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465;                         
        $mail->CharSet    = 'UTF-8';

        // =========================================================
        // CORREÇÃO PARA O XAMPP/LOCALHOST:
        // Obriga o PHP a ignorar a verificação restrita de SSL local
        // =========================================================
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Remetente e Destinatário
        $mail->setFrom('sistemahae@gmail.com', 'HAE Fatec Garça'); 
        $mail->addAddress($destinatario, $nome_destinatario);

        // Laço de repetição para embutir todas as imagens (caso existam)
        if (!empty($imagens_embutidas)) {
            foreach ($imagens_embutidas as $img) {
                if (file_exists($img['path'])) {
                    $nome_arquivo = basename($img['path']);
                    $mail->addEmbeddedImage($img['path'], $img['cid'], $nome_arquivo);
                }
            }
        }

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $corpo_html;
        $mail->AltBody = strip_tags($corpo_html);

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Salva o erro silencioso no log do servidor para diagnóstico profundo
        error_log("Erro no PHPMailer: {$mail->ErrorInfo}");
        return false;
    }
}
?>