<?php
if (!defined('HAE_SEGURANCA')) {
    http_response_code(403);
    exit('Acesso restrito.');
}

// Modelo de config/segredos.php. Copie para "segredos.php" e preencha com os valores reais.
// O arquivo segredos.php NÃO deve ser enviado ao Git.
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USUARIO', 'seu_email@gmail.com');
define('SMTP_SENHA', 'SENHA_DE_APP_DO_GMAIL');
define('SMTP_REMETENTE_NOME', 'HAE Fatec Garça');

// Use um valor longo e aleatório (ex.: 40 caracteres). Deve ser o mesmo usado na URL do agendador do cron.
define('CRON_TOKEN', 'TROQUE_POR_UM_TOKEN_LONGO_E_ALEATORIO');

// URL base do sistema na Locaweb (ex.: https://sistemahae.fatecgarca.edu.br).
// Se deixar vazio (''), o sistema detectará o domínio automaticamente.
define('APP_URL', '');
