<?php
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $whatsapp = $_POST['whatsapp'];
    $funcao = $_POST['funcao'];
    $data_nascimento = $_POST['data_nascimento'];
    
    // Gerar senha provisória: DDMMAAAA
    $data_obj = new DateTime($data_nascimento);
    $senha_provisoria = $data_obj->format('dmY');
    $senha_hash = md5($senha_provisoria);

    // ... (restante dos campos e upload de assinatura) ...

    try {
        $sql = "INSERT INTO usuarios (nome, email, whatsapp, funcao, data_nascimento, senha, trocar_senha) 
                VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $email, $whatsapp, $funcao, $data_nascimento, $senha_hash]);

        // --- LÓGICA DE ENVIO (SIMULAÇÃO) ---
        // Aqui você chamaria suas funções de disparo:
        // enviarEmail($email, $senha_provisoria);
        // enviarWhatsApp($whatsapp, $senha_provisoria);

        // Redireciona para o login com parâmetro de sucesso
        header("Location: index.php?cadastro=sucesso");
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
    <title>Cadastro - Sistema HAE</title>
    <style>
        /* Mantendo seu estilo Fatec (#b20000) */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f4f4; padding: 20px; }
        .form-container { background: #fff; max-width: 600px; margin: 0 auto; padding: 30px; border-radius: 8px; border-top: 5px solid #b20000; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { color: #b20000; margin-bottom: 20px; text-align: center; }
        .input-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-cadastrar { width: 100%; padding: 14px; background: #b20000; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Novo Cadastro HAE</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="input-group">
            <label>Nome Completo</label>
            <input type="text" name="nome" required>
        </div>
        <div class="input-group">
            <label>E-mail Institucional</label>
            <input type="email" name="email" required>
        </div>
        <div class="input-group">
            <label>WhatsApp (com DDD)</label>
            <input type="text" name="whatsapp" id="whatsapp" placeholder="(00) 00000-0000" maxlength="15" required>
        </div>
        <div class="input-group">
            <label>Data de Nascimento</label>
            <input type="date" name="data_nascimento" required>
        </div>
        <div class="input-group">
            <label>Função</label>
            <select name="funcao">
                <option value="Professor">Professor(a)</option>
                <option value="Coordenador">Coordenador(a)</option>
                <option value="Diretor">Diretor(a)</option>
            </select>
        </div>
        <button type="submit" class="btn-cadastrar">Finalizar e Enviar Senha</button>
    </form>
</div>

<script>
    // Máscara de Telefone Automática
    const handlePhone = (event) => {
        let input = event.target
        input.value = phoneMask(input.value)
    }

    const phoneMask = (value) => {
        if (!value) return ""
        value = value.replace(/\D/g, '')
        value = value.replace(/(\d{2})(\d)/, "($1) $2")
        value = value.replace(/(\d)(\d{4})$/, "$1-$2")
        return value
    }

    document.getElementById('whatsapp').addEventListener('keyup', handlePhone)
</script>

</body>
</html>