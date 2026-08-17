<?php
// Carrega os arquivos do PHPMailer
require 'vendor/PHPMailer/Exception.php';
require 'vendor/PHPMailer/PHPMailer.php';
require 'vendor/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Função global para disparo de e-mails do sistema HAE usando Gmail
 * 
 * @param string $destinatario Email de quem vai receber
 * @param string $nome_destinatario Nome de quem vai receber
 * @param string $assunto Assunto do e-mail
 * @param string $corpo_html Corpo do e-mail formatado em HTML
 * @param array $imagens_embutidas Array de arrays contendo 'path' e 'cid' das imagens
 * @return bool True se enviou com sucesso, False se falhou
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

        // Remetente e Destinatário
        $mail->setFrom('sistemahae@gmail.com', 'HAE Fatec Garça'); 
        $mail->addAddress($destinatario, $nome_destinatario);

        // Laço de repetição para embutir todas as imagens passadas na lista
        if (!empty($imagens_embutidas)) {
            foreach ($imagens_embutidas as $img) {
                if (file_exists($img['path'])) {
                    // O SEGREDO ESTÁ AQUI: Extrair o nome do arquivo para o provedor reconhecer
                    $nome_arquivo = basename($img['path']);
                    
                    // Passar o $nome_arquivo como 3º parâmetro força o Gmail a colar a imagem inline
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
        return false;
    }
}
?>