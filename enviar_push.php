<?php
/**
 * Função para disparar Push Notifications via OneSignal
 * 
 * @param int $usuario_alvo_id O ID do usuário no seu banco de dados
 * @param string $titulo O título da notificação (Ex: Projeto Devolvido)
 * @param string $mensagem O texto do push
 * @param string $url_destino Para qual página o usuário vai se clicar na notificação
 */
function dispararPush($usuario_alvo_id, $titulo, $mensagem, $url_destino = "/painel.php") {
    
    // =========================================================================
    // COLE SUAS CHAVES DO ONESIGNAL AQUI
    // =========================================================================
    $app_id = "f3a9b7ad-ba4b-420c-8290-99f87501f1a3";
    $rest_api_key = "CHAVE_REMOVIDA_POR_SEGURANCA";
    // =========================================================================

    $content = array(
        "en" => $mensagem, // O OneSignal usa 'en' como padrão universal
        "pt" => $mensagem
    );
    
    $headings = array(
        "en" => $titulo,
        "pt" => $titulo
    );

    // Configura o pacote de envio. Repare no "external_id" que aponta direto para o ID do usuário no banco.
    $fields = array(
        'app_id' => $app_id,
        'include_aliases' => array('external_id' => [(string)$usuario_alvo_id]),
        'target_channel' => 'push',
        'headings' => $headings,
        'contents' => $content,
        'url' => $url_destino
    );

    $fields_json = json_encode($fields);

    // Disparo via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.onesignal.com/notifications?c=push");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $rest_api_key
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_json);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
?>