<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['funcao'] != 'Professor') {
    header("Location: index.php");
    exit;
}

// O professor já vem pré-preenchido [cite: 160]
$nome_professor = $_SESSION['nome'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Solicitação de HAE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f4f4f4; color: #333; }
        header { background: #000; color: #fff; padding: 20px 40px; border-bottom: 5px solid #b20000; display: flex; justify-content: space-between; }
        .container { max-width: 1000px; margin: 30px auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { color: #b20000; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 25px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .input-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        textarea { resize: vertical; height: 100px; }
        
        /* Tabela de Carga Horária */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f9f9f9; width: 70%; }
        .calculo-total { font-weight: bold; color: #b20000; background: #ffeeee; }
        
        .radio-group label { display: inline-block; font-weight: normal; margin-right: 15px; }
        .btn-enviar { background: #b20000; color: #fff; border: none; padding: 15px 30px; font-size: 16px; font-weight: bold; border-radius: 4px; cursor: pointer; width: 100%; margin-top: 20px; }
        .btn-enviar:hover { background: #8a0000; }
        #alerta-horas { color: red; font-weight: bold; display: none; margin-top: 5px; }
    </style>
</head>
<body>

<header>
    <h2>Portal HAE - Solicitação de Projeto</h2>
    <div>Prof(a). <?php echo htmlspecialchars($nome_professor); ?></div>
</header>

<div class="container">
    <h2>Formulário para Solicitação de Projeto (HAE)</h2>
    <form method="POST" action="salvar_solicitacao.php" id="formHae">
        
        <div class="grid-2">
            <div class="input-group">
                [cite_start]<label>Professor Responsável [cite: 160]</label>
                <input type="text" value="<?php echo htmlspecialchars($nome_professor); ?>" readonly>
            </div>
            <div class="input-group">
                [cite_start]<label>Semestre / Ano [cite: 161]</label>
                <input type="text" name="semestre_ano" placeholder="Ex: 2 / 2026" required>
            </div>
            <div class="input-group">
                [cite_start]<label>Quantidade de HAE Solicitada [cite: 162]</label>
                <input type="number" name="qtd_hae" id="qtd_hae" required>
            </div>
            <div class="input-group">
                [cite_start]<label>Título do Projeto [cite: 163]</label>
                <input type="text" name="titulo" required>
            </div>
        </div>

        <div class="input-group">
            [cite_start]<label>Projeto relacionado com desenvolvido anteriormente? [cite: 164, 165]</label>
            <div class="radio-group">
                <input type="radio" name="relacionado" value="Nao" checked onclick="document.getElementById('qual_projeto').style.display='none'"> Não
                <input type="radio" name="relacionado" value="Sim" onclick="document.getElementById('qual_projeto').style.display='block'"> Sim
            </div>
            [cite_start]<input type="text" name="qual_projeto" id="qual_projeto" placeholder="Se sim, informe qual o nome do projeto [cite: 166]" style="display:none; margin-top:10px;">
        </div>

        <div class="input-group">
            [cite_start]<label>Objetivos/Meta(s) da Escola à(s) qual(is) o projeto está vinculado [cite: 167]</label>
            <textarea name="objetivos_escola" required></textarea>
        </div>

        [cite_start]<h3>Carga Horária Semanal [cite: 168, 169]</h3>
        <table>
            <tr><th>Horas aula</th><td><input type="number" id="h_aula" value="0" min="0" oninput="calcularHoras()"></td></tr>
            <tr><th>Hora Atividade (50% das Horas-aula)</th><td><input type="number" id="h_atividade" value="0" readonly style="background:#eee;"></td></tr>
            <tr><th>Hora – Atividade Específica do Projeto</th><td><input type="number" id="h_especifica" value="0" min="0" oninput="calcularHoras()"></td></tr>
            <tr><th>Total Semanal</th><td class="calculo-total" id="total_semanal">0</td></tr>
            [cite_start]<tr><th>Total Mensal (Total Semanal x 4,5 Semanas) [cite: 170]</th><td class="calculo-total" id="total_mensal">0</td></tr>
        </table>
        [cite_start]<div id="alerta-horas">OBS: O TOTAL NÃO PODERÁ ULTRAPASSAR 200 HORAS MENSAIS! [cite: 171]</div>

        <div class="grid-2">
            <div class="input-group">
                [cite_start]<label>Categoria do Projeto [cite: 172, 173, 174]</label>
                <select name="categoria" required>
                    <option value="Administrativo">Administrativo</option>
                    <option value="Acadêmico">Acadêmico</option>
                    <option value="Extensão a comunidade">Extensão a comunidade</option>
                </select>
            </div>
            <div class="input-group radio-group">
                [cite_start]<label>Recursos Necessários [cite: 179, 180, 181]</label><br>
                <input type="checkbox" name="recursos[]" value="Financeiro"> Financeiro
                <input type="checkbox" name="recursos[]" value="Físico"> Físico
                <input type="checkbox" name="recursos[]" value="Humano"> Humano
            </div>
        </div>

        [cite_start]<div class="input-group"><label>Justificativa [cite: 175]</label><textarea name="justificativa" required></textarea></div>
        [cite_start]<div class="input-group"><label>Objetivo [cite: 176]</label><textarea name="objetivo" required></textarea></div>
        [cite_start]<div class="input-group"><label>Metodologia [cite: 177]</label><textarea name="metodologia" required></textarea></div>
        [cite_start]<div class="input-group"><label>Envolvidos no projeto [cite: 178]</label><textarea name="envolvidos" required></textarea></div>
        [cite_start]<div class="input-group"><label>Detalhamento dos Recursos Necessários [cite: 182]</label><textarea name="detalhe_recursos"></textarea></div>
        [cite_start]<div class="input-group"><label>Cronograma de Execução [cite: 183]</label><textarea name="cronograma" required></textarea></div>
        [cite_start]<div class="input-group"><label>Resultados Esperados [cite: 184]</label><textarea name="resultados" required></textarea></div>

        [cite_start]<button type="submit" class="btn-enviar" id="btn_submit">Enviar Formulário para Direção [cite: 133]</button>
    </form>
</div>

<script>
[cite_start]// Lógica Matemática do Documento [cite: 168, 170, 171]
function calcularHoras() {
    let aula = parseFloat(document.getElementById('h_aula').value) || 0;
    let especifica = parseFloat(document.getElementById('h_especifica').value) || 0;
    
    // Hora Atividade (50% das Horas-aula)
    let atividade = aula * 0.5;
    document.getElementById('h_atividade').value = atividade;

    let totalSemanal = aula + atividade + especifica;
    let totalMensal = totalSemanal * 4.5;

    document.getElementById('total_semanal').innerText = totalSemanal;
    document.getElementById('total_mensal').innerText = totalMensal;

    let btn = document.getElementById('btn_submit');
    let alerta = document.getElementById('alerta-horas');

    // Validação de 200 Horas Mensais [cite: 171]
    if(totalMensal > 200) {
        alerta.style.display = 'block';
        btn.disabled = true;
        btn.style.background = '#ccc';
    } else {
        alerta.style.display = 'none';
        btn.disabled = false;
        btn.style.background = '#b20000';
    }
}
</script>

</body>
</html>