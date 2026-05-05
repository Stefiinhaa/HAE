<?php
require 'config/conexao.php';

// Importação das classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/PHPMailer/Exception.php';
require 'vendor/PHPMailer/PHPMailer.php';
require 'vendor/PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $whatsapp = $_POST['whatsapp'];
    $funcao = $_POST['funcao'];
    $data_nascimento = $_POST['data_nascimento'];
    $data_admissao = $_POST['data_admissao'];
    $tipo_contrato = $_POST['tipo_contrato'];
    $formacao = $_POST['formacao'];
    
    // Geração da senha provisória: DDMMAAAA
    $data_obj = new DateTime($data_nascimento);
    $senha_provisoria = $data_obj->format('dmY');
    $senha_hash = md5($senha_provisoria);

    // Upload da Assinatura Digitalizada 
    $assinatura_path = "";
    if (isset($_FILES['assinatura']) && $_FILES['assinatura']['error'] == 0) {
        $extensao = pathinfo($_FILES['assinatura']['name'], PATHINFO_EXTENSION);
        $novo_nome = md5(uniqid()) . "." . $extensao;
        $diretorio = "uploads/assinaturas/";
        if (!is_dir($diretorio)) mkdir($diretorio, 0777, true);
        move_uploaded_file($_FILES['assinatura']['tmp_name'], $diretorio . $novo_nome);
        $assinatura_path = $diretorio . $novo_nome;
    }

    try {
        // CORREÇÃO AQUI: Nomes das colunas ajustados para bater com o banco de dados (telefone_whatsapp, formacao_academica, primeiro_acesso)
        $sql = "INSERT INTO usuarios (nome, email, telefone_whatsapp, funcao, data_nascimento, data_admissao, tipo_contrato, formacao_academica, assinatura_path, senha, primeiro_acesso) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $email, $whatsapp, $funcao, $data_nascimento, $data_admissao, $tipo_contrato, $formacao, $assinatura_path, $senha_hash]);

        // --- LÓGICA 1: ENVIO DE E-MAIL (PHPMailer) ---
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'SEU_EMAIL@gmail.com'; // Seu e-mail configurado
            $mail->Password   = 'SUA_SENHA_DE_APP';    // Sua senha de app de 16 dígitos
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('SEU_EMAIL@gmail.com', 'Sistema HAE Fatec');
            $mail->addAddress($email, $nome);

            $mail->isHTML(true);
            $mail->Subject = 'Sua Senha Provisória - Portal HAE';
            $mail->Body    = "Olá $nome,<br>Seu cadastro foi realizado. Sua senha provisória é: <b>$senha_provisoria</b>";
            $mail->send();
        } catch (Exception $e) {
            // Silenciar erro de e-mail para não travar o cadastro
        }

        // --- LÓGICA 2: LINK WHATSAPP (wa.me) ---
        $num_limpo = preg_replace('/\D/', '', $whatsapp);
        if (substr($num_limpo, 0, 2) !== '55') $num_limpo = '55' . $num_limpo;
        
        $msg = "Olá, Prof. $nome! Seu acesso ao Portal HAE foi criado. Senha provisória: $senha_provisoria";
        $link_wa = "https://wa.me/{$num_limpo}?text=" . urlencode($msg);

        header("Location: index.php?cadastro=sucesso&wa=" . urlencode($link_wa));
        exit;

    } catch (PDOException $e) {
        $erro = "Erro ao cadastrar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - Sistema HAE Fatec Garça</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f4f4; padding: 20px; }
        .form-container { background: #fff; max-width: 700px; margin: 0 auto; padding: 30px; border-radius: 8px; border-top: 6px solid #b20000; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { color: #b20000; text-align: center; margin-bottom: 25px; text-transform: uppercase; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full { grid-column: span 2; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-submit { width: 100%; padding: 15px; background: #b20000; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        .btn-submit:hover { background: #8a0000; }
        .error { color: #b20000; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Cadastro de Usuário</h2>
    <?php if(isset($erro)) echo "<p class='error'>$erro</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="grid">
            <div class="input-group full"><label>Nome Completo</label><input type="text" name="nome" required></div>
            <div class="input-group"><label>E-mail Institucional</label><input type="email" name="email" required></div>
            <div class="input-group"><label>WhatsApp</label><input type="text" id="whatsapp" name="whatsapp" required></div>
            <div class="input-group">
                <label>Função</label>
                <select name="funcao" required>
                    <option value="Professor">Professor(a)</option>
                    <option value="Coordenador">Coordenador(a)</option>
                    <option value="Diretor">Diretor(a)</option>
                </select>
            </div>
            <div class="input-group"><label>Data de Nascimento</label><input type="date" name="data_nascimento" required></div>
            <div class="input-group"><label>Data de Admissão</label><input type="date" name="data_admissao" required></div>
            <div class="input-group">
                <label>Tipo de Contrato</label>
                <select name="tipo_contrato" required>
                    <option value="Determinado">Determinado</option>
                    <option value="Indeterminado">Indeterminado</option>
                </select>
            </div>
            <div class="input-group full"><label>Formação Acadêmica</label><input type="text" name="formacao" required></div>
            <div class="input-group full"><label>Arquivo da Assinatura</label><input type="file" name="assinatura" accept="image/*" required></div>
        </div>
        <button type="submit" class="btn-submit">Finalizar Cadastro</button>
    </form>
</div>
<script>
    const handlePhone = (event) => {
        let input = event.target;
        input.value = phoneMask(input.value);
    }
    const phoneMask = (value) => {
        if (!value) return "";
        value = value.replace(/\D/g, '');
        value = value.replace(/(\d{2})(\d)/, "($1) $2");
        value = value.replace(/(\d)(\d{4})$/, "$1-$2");
        return value;
    }
    document.getElementById('whatsapp').addEventListener('keyup', handlePhone);
</script>
</body>
</html>