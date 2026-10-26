<?php
/**
 * ==============================================================================
 * CENTRAL DE SEGURANÇA DO SISTEMA HAE
 * ==============================================================================
 * Este arquivo substitui o session_start() em todas as páginas e centraliza:
 *   - Sessão segura (HttpOnly, SameSite, Secure, modo estrito, expiração por inatividade)
 *   - Cabeçalhos HTTP de segurança
 *   - Proteção contra CSRF (validação de origem em requisições que alteram dados)
 *   - Limite de requisições por IP (mitiga abuso / DoS de aplicação) e força bruta
 *   - Ocultação de erros técnicos em produção
 *   - Funções auxiliares: escape XSS, hash de senha, upload seguro de imagens
 * ==============================================================================
 */

if (defined('HAE_SEGURANCA')) {
    return;
}
define('HAE_SEGURANCA', true);

// Tempo máximo de inatividade da sessão (segundos). 4 horas.
define('HAE_SESSAO_INATIVIDADE', 14400);
// Limite global de requisições PHP por IP dentro de 60 segundos.
define('HAE_LIMITE_GLOBAL_POR_MINUTO', 1200);

// ------------------------------------------------------------------------------
// AMBIENTE
// ------------------------------------------------------------------------------
// Se (e SOMENTE se) a hospedagem colocar o site atrás de um proxy/CDN que envia o IP real do visitante
// em um cabeçalho confiável (ex.: 'CF-Connecting-IP' ou 'X-Real-IP'), informe o nome do cabeçalho aqui.
// Deixe vazio na Locaweb comum (sem proxy). Habilitar sem necessidade permite forjar o IP.
if (!defined('HAE_IP_CABECALHO')) {
    define('HAE_IP_CABECALHO', '');
}

function hae_ip() {
    // Padrão: somente REMOTE_ADDR (cabeçalhos como X-Forwarded-For podem ser forjados pelo atacante).
    if (HAE_IP_CABECALHO !== '') {
        $chave = 'HTTP_' . strtoupper(str_replace('-', '_', HAE_IP_CABECALHO));
        if (!empty($_SERVER[$chave])) {
            $candidato = trim(explode(',', (string) $_SERVER[$chave])[0]);
            if (filter_var($candidato, FILTER_VALIDATE_IP)) {
                return $candidato;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function hae_ambiente_local() {
    if (PHP_SAPI === 'cli') {
        return true;
    }
    $ip = hae_ip();
    if ($ip !== '127.0.0.1' && $ip !== '::1') {
        return false;
    }
    // Além do IP, o endereço acessado precisa ser local. Isso impede que um proxy do provedor
    // (que chega ao PHP como 127.0.0.1) faça o site em produção se passar por ambiente de testes.
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return (bool) preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $host);
}

function hae_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/**
 * Retorna a URL base do sistema (ex.: https://sistemahae.fatecgarca.edu.br).
 * Se APP_URL estiver definida em segredos.php, usa ela; senão detecta automaticamente pelo domínio atual.
 */
function hae_url_base() {
    if (defined('APP_URL') && APP_URL !== '') {
        return rtrim(APP_URL, '/');
    }
    $proto = hae_https() ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    return $proto . $host . ($dir === '' ? '' : $dir);
}

// Em produção nunca exibe erros técnicos na tela (vazam caminhos, SQL e senhas).
if (!hae_ambiente_local()) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}

// ------------------------------------------------------------------------------
// FUNÇÕES AUXILIARES
// ------------------------------------------------------------------------------

/** Escapa texto para saída em HTML (proteção contra XSS). */
function h($valor) {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Registra o erro técnico no log do servidor e devolve uma mensagem genérica para o usuário. */
function hae_erro_seguro($e, $contexto = '') {
    error_log('[HAE] ' . $contexto . ' ' . get_class($e) . ': ' . $e->getMessage());
    return 'Ocorreu um erro interno. Tente novamente; se persistir, contate o suporte.';
}

/**
 * Converte um valor em literal JavaScript seguro para usar DENTRO de um atributo HTML
 * (ex.: onclick="funcao(<?php echo hae_js_attr($valor); ?>)"). Impede quebra da string JS e injeção de HTML.
 */
function hae_js_attr($valor) {
    return htmlspecialchars(
        json_encode((string) $valor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * O "modo desenvolvedor" identifica o administrador pelo NOME do usuário.
 * Para que ninguém ganhe esse poder apenas editando o próprio nome, estes nomes são reservados.
 */
function hae_nome_reservado($nome) {
    $n = strtolower(trim(preg_replace('/\s+/', ' ', (string) $nome)));
    return in_array($n, ['administrador', 'administrador md5'], true);
}

// ------------------------------------------------------------------------------
// SENHAS (bcrypt, com migração transparente das senhas antigas em MD5)
// ------------------------------------------------------------------------------
function hae_hash_senha($senha) {
    return password_hash((string) $senha, PASSWORD_DEFAULT);
}

/**
 * Gera uma senha temporária forte (padrão: 16 caracteres, ~95 bits de entropia).
 *  - Usa random_int(), o gerador criptograficamente seguro do sistema operacional.
 *  - Garante ao menos 2 caracteres de cada grupo (maiúscula, minúscula, número, símbolo).
 *  - Remove caracteres ambíguos (O/0, I/l/1) porque o usuário digita a senha lida no e-mail.
 *  - Símbolos escolhidos para não quebrar HTML/URL/WhatsApp (sem < > & " ' \ / % espaço).
 *  - Embaralha o resultado (Fisher-Yates com random_int) para a posição dos grupos não ser previsível.
 * A segurança vem da entropia real, não de "esconder" o método: mesmo conhecendo este código, a senha não é adivinhável.
 */
function hae_gerar_senha_temporaria($tamanho = 16) {
    $grupos = [
        'ABCDEFGHJKLMNPQRSTUVWXYZ',
        'abcdefghijkmnpqrstuvwxyz',
        '23456789',
        '!@#$*?+=-_',
    ];
    $tamanho = max(12, (int) $tamanho);
    $todos = implode('', $grupos);
    $chars = [];

    foreach ($grupos as $grupo) {
        for ($i = 0; $i < 2; $i++) {
            $chars[] = $grupo[random_int(0, strlen($grupo) - 1)];
        }
    }
    while (count($chars) < $tamanho) {
        $chars[] = $todos[random_int(0, strlen($todos) - 1)];
    }
    for ($i = count($chars) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        $tmp = $chars[$i];
        $chars[$i] = $chars[$j];
        $chars[$j] = $tmp;
    }
    return implode('', $chars);
}

/**
 * Confere a senha. Aceita hashes novos (bcrypt) e antigos (MD5).
 * $precisa_atualizar vira true quando o hash gravado é antigo e deve ser regravado.
 */
function hae_verificar_senha($senha, $hash_gravado, &$precisa_atualizar = false) {
    $precisa_atualizar = false;
    $hash_gravado = (string) $hash_gravado;
    if ($hash_gravado === '') {
        return false;
    }
    if (strncmp($hash_gravado, '$2y$', 4) === 0 || strncmp($hash_gravado, '$argon', 6) === 0) {
        $ok = password_verify((string) $senha, $hash_gravado);
        if ($ok && password_needs_rehash($hash_gravado, PASSWORD_DEFAULT)) {
            $precisa_atualizar = true;
        }
        return $ok;
    }
    // Hash legado (MD5)
    $ok = hash_equals(strtolower($hash_gravado), md5((string) $senha));
    if ($ok) {
        $precisa_atualizar = true;
    }
    return $ok;
}

// ------------------------------------------------------------------------------
// LIMITADOR DE TAXA (arquivos temporários; não exige alterar o banco de dados)
// ------------------------------------------------------------------------------

/**
 * Conta (e opcionalmente incrementa) as ocorrências de $chave dentro de uma janela fixa.
 * Retorna o total após a operação. Em caso de falha de E/S, não bloqueia ninguém.
 */
function hae_rl($chave, $janela_segundos, $incrementar = true) {
    $dir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'hae_rl';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $arquivo = $dir . DIRECTORY_SEPARATOR . hash('sha256', $chave) . '.rl';
    $fp = @fopen($arquivo, 'c+');
    if (!$fp) {
        return 0;
    }
    flock($fp, LOCK_EX);
    $bruto = stream_get_contents($fp);
    $partes = explode('|', (string) $bruto);
    $inicio = isset($partes[0]) ? (int) $partes[0] : 0;
    $contagem = isset($partes[1]) ? (int) $partes[1] : 0;
    $agora = time();

    if ($inicio === 0 || ($agora - $inicio) >= $janela_segundos) {
        $inicio = $agora;
        $contagem = 0;
    }
    if ($incrementar) {
        $contagem++;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $inicio . '|' . $contagem);
    }
    flock($fp, LOCK_UN);
    fclose($fp);

    // Limpeza ocasional de arquivos antigos
    if (mt_rand(1, 300) === 1) {
        foreach (@glob($dir . DIRECTORY_SEPARATOR . '*.rl') ?: [] as $velho) {
            if (@filemtime($velho) < $agora - 86400) {
                @unlink($velho);
            }
        }
    }
    return $contagem;
}

function hae_rl_zerar($chave) {
    $arquivo = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'hae_rl' . DIRECTORY_SEPARATOR . hash('sha256', $chave) . '.rl';
    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}

/** Interrompe a requisição com HTTP 429 caso o limite seja excedido. */
function hae_limitar_requisicoes($chave, $maximo, $janela_segundos, $json = false) {
    if (hae_rl($chave, $janela_segundos) > $maximo) {
        http_response_code(429);
        header('Retry-After: ' . $janela_segundos);
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Muitas requisições. Aguarde e tente novamente.']);
        } else {
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Muitas requisições. Aguarde alguns instantes e tente novamente.';
        }
        exit;
    }
}

// ------------------------------------------------------------------------------
// PROTEÇÃO CONTRA CSRF (validação de origem)
// ------------------------------------------------------------------------------
function hae_host_da_url($url) {
    $p = @parse_url((string) $url);
    if (!$p || empty($p['host'])) {
        return null;
    }
    return strtolower($p['host']);
}

/** True quando a requisição vem do próprio site (Origin ou Referer), ou quando nenhum dos dois é enviado. */
function hae_origem_valida($exigir_cabecalho = false) {
    $meu_host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        return hae_host_da_url($_SERVER['HTTP_ORIGIN']) === $meu_host;
    }
    if (!empty($_SERVER['HTTP_REFERER'])) {
        return hae_host_da_url($_SERVER['HTTP_REFERER']) === $meu_host;
    }
    // Sem Origin/Referer: o cookie SameSite=Lax já impede o envio da sessão em POST de outro site.
    return !$exigir_cabecalho;
}

/** Para ações destrutivas feitas via GET (links): exige que o clique tenha partido do próprio site. */
function hae_exigir_referer_proprio() {
    if (!hae_origem_valida(true)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Acesso negado: requisição de origem não confiável.');
    }
}

// ------------------------------------------------------------------------------
// UPLOAD SEGURO DE IMAGENS
// ------------------------------------------------------------------------------
/**
 * Valida e salva uma imagem enviada. A extensão é definida pelo CONTEÚDO real do arquivo,
 * nunca pelo nome enviado pelo usuário (impede upload de .php disfarçado).
 * Retorna ['ok' => true, 'caminho' => '...'] ou ['ok' => false, 'erro' => '...'].
 */
function hae_salvar_upload_imagem($arquivo, $diretorio, $prefixo = '', $max_bytes = 3145728) {
    if (!isset($arquivo['error']) || is_array($arquivo['error']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'erro' => 'Falha no envio do arquivo.'];
    }
    if (!is_uploaded_file($arquivo['tmp_name'])) {
        return ['ok' => false, 'erro' => 'Arquivo de envio inválido.'];
    }
    if ($arquivo['size'] > $max_bytes) {
        return ['ok' => false, 'erro' => 'A imagem excede o tamanho máximo de ' . round($max_bytes / 1048576) . ' MB.'];
    }

    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = (string) finfo_file($finfo, $arquivo['tmp_name']);
        finfo_close($finfo);
    } else {
        $info = @getimagesize($arquivo['tmp_name']);
        $mime = $info['mime'] ?? '';
    }

    if (!isset($permitidos[$mime])) {
        return ['ok' => false, 'erro' => 'Formato de imagem inválido. Use JPG, PNG, GIF, WEBP ou AVIF.'];
    }
    // Para os formatos comuns, confirma que realmente é uma imagem decodificável.
    if ($mime !== 'image/avif' && @getimagesize($arquivo['tmp_name']) === false) {
        return ['ok' => false, 'erro' => 'O arquivo enviado não é uma imagem válida.'];
    }

    $diretorio = rtrim($diretorio, '/\\') . '/';
    if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true)) {
        return ['ok' => false, 'erro' => 'Não foi possível criar a pasta de destino.'];
    }

    $prefixo = preg_replace('/[^A-Za-z0-9_\-]/', '', $prefixo);
    $novo_nome = $prefixo . bin2hex(random_bytes(16)) . '.' . $permitidos[$mime];
    if (!move_uploaded_file($arquivo['tmp_name'], $diretorio . $novo_nome)) {
        return ['ok' => false, 'erro' => 'Falha ao salvar a imagem no servidor.'];
    }
    @chmod($diretorio . $novo_nome, 0644);
    return ['ok' => true, 'caminho' => $diretorio . $novo_nome];
}

// ------------------------------------------------------------------------------
// CABEÇALHOS HTTP DE SEGURANÇA
// ------------------------------------------------------------------------------
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: frame-ancestors 'self'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header_remove('X-Powered-By');
    if (hae_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

// ------------------------------------------------------------------------------
// LIMITE GLOBAL POR IP (freio contra inundação de requisições)
// ------------------------------------------------------------------------------
if (PHP_SAPI !== 'cli') {
    hae_limitar_requisicoes('global:' . hae_ip(), HAE_LIMITE_GLOBAL_POR_MINUTO, 60);
}

// ------------------------------------------------------------------------------
// CSRF: bloqueia requisições que alteram dados vindas de outro site
// ------------------------------------------------------------------------------
if (PHP_SAPI !== 'cli' && in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    if (!hae_origem_valida()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Acesso negado: requisição de origem não confiável.');
    }
}

// ------------------------------------------------------------------------------
// SESSÃO SEGURA
// ------------------------------------------------------------------------------
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => hae_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Expira sessões abandonadas
    $agora = time();
    if (isset($_SESSION['usuario_id'], $_SESSION['hae_ultima_atividade'])
        && ($agora - (int) $_SESSION['hae_ultima_atividade']) > HAE_SESSAO_INATIVIDADE) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
    $_SESSION['hae_ultima_atividade'] = $agora;
}
