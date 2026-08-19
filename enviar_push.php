<?php
// Puxa o arquivo com as chaves que o GitHub ignora
require_once __DIR__ . '/config/api_keys.php';

function dispararPush($usuario_alvo_id, $titulo, $mensagem, $url_destino = "/painel.php") {
    
    // Pega as chaves do arquivo seguro
    $app_id = ONESIGNAL_APP_ID;
    $rest_api_key = ONESIGNAL_REST_KEY;

    $content = array("en" => $mensagem, "pt" => $mensagem);
    $headings = array("en" => $titulo, "pt" => $titulo);

    // Formato robusto para encontrar o ID do banco de dados
    $fields = array(
        'app_id' => $app_id,
        'include_external_user_ids' => [(string)$usuario_alvo_id],
        'channel_for_external_user_ids' => 'push',
        'headings' => $headings,
        'contents' => $content,
        'url' => $url_destino
    );

    $fields_json = json_encode($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.onesignal.com/notifications");
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