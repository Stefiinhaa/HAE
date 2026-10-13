<?php
require_once __DIR__ . '/config/seguranca.php';
// Credenciais SMTP ficam fora do código-fonte (config/segredos.php, ignorado pelo Git)
require_once __DIR__ . '/config/segredos.php';

// Carrega os arquivos do PHPMailer
require_once __DIR__ . '/vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/SMTP.php';

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
        $mail->Host       = SMTP_HOST; 
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USUARIO; 
        $mail->Password   = SMTP_SENHA; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465;                         
        $mail->CharSet    = 'UTF-8';

        // =========================================================
        // CORREÇÃO PARA O XAMPP/LOCALHOST:
        // A verificação restrita de SSL só é dispensada no ambiente local.
        // Em produção o certificado do servidor SMTP é validado (evita interceptação).
        // =========================================================
        if (hae_ambiente_local()) {
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        }

        // Remetente e Destinatário
        $mail->setFrom(SMTP_USUARIO, SMTP_REMETENTE_NOME); 
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