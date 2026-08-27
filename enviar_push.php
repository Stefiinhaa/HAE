<?php
require_once __DIR__ . '/config/conexao.php';

function getFCMTokenOAuth() {
    $chave_json = __DIR__ . '/config/firebase_credentials.json';
    
    if (!file_exists($chave_json)) {
        return "ERRO_ARQUIVO: O arquivo firebase_credentials.json não foi encontrado na pasta config!";
    }
    
    $keyFile = json_decode(file_get_contents($chave_json), true);
    
    if (!isset($keyFile['client_email'])) {
        return "ERRO_JSON: O arquivo JSON parece inválido.";
    }
    
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $claim = json_encode([
        'iss' => $keyFile['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => $keyFile['token_uri'],
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlClaim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));
    
    $signatureInput = $base64UrlHeader . '.' . $base64UrlClaim;
    $signature = '';
    
    $privateKey = openssl_pkey_get_private($keyFile['private_key']);
    
    if (!$privateKey) {
        return "ERRO_CHAVE: O PHP não conseguiu ler a chave privada.";
    }

    $sucesso = openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    
    if (!$sucesso) {
        return "ERRO_OPENSSL: O PHP falhou ao gerar a assinatura digital.";
    }

    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $signatureInput . '.' . $base64UrlSignature;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $keyFile['token_uri']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=' . $jwt);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $curl_err = curl_error($ch);
    curl_close($ch);
    
    if ($curl_err) {
        return "ERRO_CURL: " . $curl_err;
    }
    
    $data = json_decode($response, true);
    if (isset($data['access_token'])) {
        return $data['access_token'];
    }
    
    return "ERRO_GOOGLE: " . $response;
}

function dispararPush($usuario_alvo_id, $titulo, $mensagem, $url_destino = "https://sistemahae.page.gd/painel.php") {
    global $pdo;
    
    // 1. Busca os tokens do usuário alvo
    $stmt = $pdo->prepare("SELECT token FROM fcm_tokens WHERE usuario_id = ?");
    $stmt->execute([$usuario_alvo_id]);
    $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tokens)) return "Nenhum token encontrado no banco para este usuário.";

    // 2. Autentica no Google
    $access_token = getFCMTokenOAuth();
    
    if (strpos($access_token, 'ERRO') === 0) {
        return $access_token;
    }

    $keyFile = json_decode(file_get_contents(__DIR__ . '/config/firebase_credentials.json'), true);
    $project_id = $keyFile['project_id'];
    $url = "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send";

    $resultados = [];

    // 3. Dispara para cada aparelho cadastrado
    foreach ($tokens as $device_token) {
        $fields = [
            'message' => [
                'token' => $device_token,
                'notification' => [
                    'title' => $titulo,
                    'body' => $mensagem
                ],
                'webpush' => [
                    'fcm_options' => [
                        'link' => $url_destino
                    ]
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // ==============================================================
        // O TOQUE DE MESTRE: Limpeza Automática
        // ==============================================================
        $resultado_json = json_decode($response, true);
        
        // Se o Google disser que não encontrou o Token (NOT_FOUND)
        if (isset($resultado_json['error']) && $resultado_json['error']['status'] === 'NOT_FOUND') {
            // Vai no banco e deleta ele silenciosamente
            $stmt_del = $pdo->prepare("DELETE FROM fcm_tokens WHERE token = ?");
            $stmt_del->execute([$device_token]);
        }
        // ==============================================================

        $resultados[] = $response;
    }
    
    return json_encode($resultados);
}
?>