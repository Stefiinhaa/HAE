<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas logados e com função 'Professor' acessam essa tela
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] !== 'Professor') {
    header("Location: painel.php");
    exit;
}

$erro = "";
$sucesso = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $professor_id = $_SESSION['usuario_id'];
    $semestre = $_POST['semestre'];
    $quantidade_horas = $_POST['quantidade_horas'];
    $titulo_projeto = $_POST['titulo_projeto'];
    $projeto_anterior = $_POST['projeto_anterior'] == 'Sim' ? 1 : 0;
    $nome_projeto_anterior = $projeto_anterior ? $_POST['nome_projeto_anterior'] : null;
    $objetivos_escola = $_POST['objetivos_escola'];
    
    // Carga Horária
    $horas_aula = $_POST['horas_aula'];
    $horas_atividade = $_POST['horas_atividade'];
    $horas_especificas = $_POST['horas_especificas'];
    $total_semanal = $_POST['total_semanal'];
    $total_mensal = $_POST['total_mensal'];
    
    $categoria = $_POST['categoria'];
    $justificativa = $_POST['justificativa'];
    $objetivo = $_POST['objetivo'];
    $metodologia = $_POST['metodologia'];
    $envolvidos = $_POST['envolvidos'];
    
    // Recursos (Checkboxes - transforma array em string separada por vírgula)
    $recursos_necessarios = isset($_POST['recursos']) ? implode(',', $_POST['recursos']) : '';
    $detalhamento_recursos = $_POST['detalhamento_recursos'];
    $cronograma = $_POST['cronograma'];
    $resultados_esperados = $_POST['resultados_esperados'];

    // Validação Back-end de 200 horas (Segurança extra)
    if ($total_mensal > 200) {
        $erro = "O total mensal não pode ultrapassar 200 horas.";
    } else {
        try {
            $sql = "INSERT INTO solicitacoes_hae 
                    (professor_id, semestre, quantidade_horas, titulo_projeto, projeto_anterior, nome_projeto_anterior, 
                    objetivos_escola, horas_aula, horas_atividade, horas_especificas, total_semanal, total_mensal, 
                    categoria, justificativa, objetivo, metodologia, envolvidos, recursos_necessarios, detalhamento_recursos, 
                    cronograma, resultados_esperados) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $professor_id, $semestre, $quantidade_horas, $titulo_projeto, $projeto_anterior, $nome_projeto_anterior,
                $objetivos_escola, $horas_aula, $horas_atividade, $horas_especificas, $total_semanal, $total_mensal,
                $categoria, $justificativa, $objetivo, $metodologia, $envolvidos, $recursos_necessarios, $detalhamento_recursos,
                $cronograma, $resultados_esperados
            ]);
            
            $sucesso = "Solicitação de HAE enviada com sucesso para análise da coordenação!";
        } catch (PDOException $e) {
            $erro = "Erro ao enviar solicitação: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Solicitação HAE - Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
<!-- Nova biblioteca de ícones profissionais -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos específicos para este formulário gigante */
        .form-card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border-top: 4px solid var(--fatec-red); }
        .form-section { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .form-section h3 { color: var(--fatec-red); margin-bottom: 15px; font-size: 16px; border-left: 3px solid var(--fatec-red); padding-left: 10px; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .full-width { grid-column: 1 / -1; }
        
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #444; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px; border: 1.5px solid #ddd; border-radius: 6px; font-size: 14px; }
        textarea { resize: vertical; min-height: 80px; }
        
        .checkbox-group { display: flex; gap: 15px; align-items: center; }
        .checkbox-group label { display: flex; align-items: center; font-weight: normal; font-size: 14px; gap: 5px; cursor: pointer; }
        
        .calc-box { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; }
        .calc-box input[readonly] { background: #e9ecef; color: #495057; cursor: not-allowed; font-weight: bold; }
        
        .btn-submit { background: var(--fatec-red); color: white; padding: 12px 25px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; float: right; transition: 0.3s;}
        .btn-submit:hover { background: #8a0000; }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; }
        
        .alerta-horas { color: #b20000; font-size: 12px; font-weight: bold; margin-top: 5px; display: none; }
    </style>
</head>
<body>

    <!-- Inclui a Sidebar (Pode ser separada num arquivo header.php no futuro para não repetir código) -->
    <?php
    // O PHP descobre magicamente em qual tela o usuário está agora
    $pagina_atual = basename($_SERVER['PHP_SELF']);
    ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="painel.php" class="brand">
                <img src="assets/img/logo.png" alt="Logo Fatec" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/5/52/Fatec_logo.svg'">
                <h2>HAE <span>FATEC</span></h2>
            </a>
            <button class="collapse-btn" id="collapse-btn"><i class="fa-solid fa-bars"></i></button>
        </div>
        
        <nav class="menu">
            <div class="menu-title">Navegação</div>
            <ul>
                <li><a href="painel.php" class="<?php echo ($pagina_atual == 'painel.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
                </a></li>
                
                <?php if ($_SESSION['usuario_funcao'] == 'Professor'): ?>
                    <li><a href="nova_solicitacao.php" class="<?php echo ($pagina_atual == 'nova_solicitacao.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-file-circle-plus"></i> <span>Nova Solicitação</span>
                    </a></li>
                    
                    <li><a href="meus_projetos.php" class="<?php echo ($pagina_atual == 'meus_projetos.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-folder-open"></i> <span>Meus Projetos</span>
                    </a></li>
                    
                    <li><a href="meus_projetos.php" class="<?php echo ($pagina_atual == 'enviar_relatorio.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-calendar-check"></i> <span>Enviar Relatório</span>
                    </a></li>
                    
                <?php else: ?>
                    <li><a href="analisar_solicitacoes.php" class="<?php echo ($pagina_atual == 'analisar_solicitacoes.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clipboard-check"></i> <span>Analisar Solicitações</span>
                    </a></li>
                    
                    <li><a href="acompanhar_relatorios.php" class="<?php echo ($pagina_atual == 'acompanhar_relatorios.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-chart-line"></i> <span>Acompanhar Relatórios</span>
                    </a></li>
                    
                    <li><a href="cadastrar_professor.php" class="<?php echo ($pagina_atual == 'cadastrar_professor.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-plus"></i> <span>Cadastrar Professor</span>
                    </a></li>
                <?php endif; ?>
                
                <li><a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-gear"></i> <span>Meu Perfil</span>
                </a></li>
                
                <li><a href="logout.php" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i> <span>Sair do Sistema</span>
                </a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle">☰</button>
                <h1>Solicitação de HAE</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo $_SESSION['usuario_nome']; ?></strong></div>
        </header>

        <?php if($sucesso) echo "<div class='alert-success'>$sucesso</div>"; ?>
        <?php if($erro) echo "<div class='alert-success' style='background:#fee2e2; color:#b91c1c; border-color:#b91c1c;'>$erro</div>"; ?>

        <div class="form-card">
            <form method="POST">
                
                <!-- DADOS DO PROJETO -->
                <div class="form-section">
                    <h3>Dados do Projeto</h3>
                    <div class="grid-2">
                        <div>
                            <label>Semestre / Ano (Ex: 1/2026)</label>
                            <input type="text" name="semestre" required placeholder="Ex: 1/2026">
                        </div>
                        <div>
                            <label>Quantidade de HAE Solicitada</label>
                            <input type="number" name="quantidade_horas" required min="1">
                        </div>
                        <div class="full-width">
                            <label>Título do Projeto</label>
                            <input type="text" name="titulo_projeto" required>
                        </div>
                        <div>
                            <label>Relacionado a projeto anterior?</label>
                            <select name="projeto_anterior" id="projeto_anterior" onchange="toggleProjetoAnterior()" required>
                                <option value="Não">Não</option>
                                <option value="Sim">Sim</option>
                            </select>
                        </div>
                        <div id="div_nome_anterior" style="display: none;">
                            <label>Nome do Projeto Anterior</label>
                            <input type="text" name="nome_projeto_anterior" id="nome_projeto_anterior">
                        </div>
                    </div>
                </div>

                <!-- CARGA HORÁRIA -->
                <div class="form-section calc-box">
                    <h3>Carga Horária Semanal (Cálculo Automático)</h3>
                    <div class="grid-3">
                        <div>
                            <label>Horas-aula</label>
                            <input type="number" name="horas_aula" id="horas_aula" value="0" min="0" oninput="calcularHoras()">
                        </div>
                        <div>
                            <label>Hora Atividade (50%)</label>
                            <input type="number" name="horas_atividade" id="horas_atividade" value="0" readonly>
                        </div>
                        <div>
                            <label>HAE do Projeto</label>
                            <input type="number" name="horas_especificas" id="horas_especificas" value="0" min="0" oninput="calcularHoras()">
                        </div>
                    </div>
                    <br>
                    <div class="grid-2">
                        <div>
                            <label>Total Semanal</label>
                            <input type="number" name="total_semanal" id="total_semanal" value="0" readonly>
                        </div>
                        <div>
                            <label>Total Mensal (Semanas x 4,5)</label>
                            <input type="number" name="total_mensal" id="total_mensal" value="0" readonly>
                            <div id="alerta_horas" class="alerta-horas">Atenção: O total não pode ultrapassar 200 horas!</div>
                        </div>
                    </div>
                </div>

                <!-- DETALHES ACADÊMICOS -->
                <div class="form-section">
                    <h3>Detalhamento Acadêmico</h3>
                    <div class="grid-2">
                        <div class="full-width">
                            <label>Objetivos/Meta(s) da Escola vinculada(s)</label>
                            <textarea name="objetivos_escola" required></textarea>
                        </div>
                        <div class="full-width">
                            <label>Categoria do Projeto</label>
                            <select name="categoria" required>
                                <option value="Acadêmico">Acadêmico</option>
                                <option value="Administrativo">Administrativo</option>
                                <option value="Extensão a comunidade">Extensão à comunidade</option>
                            </select>
                        </div>
                        <div class="full-width"><label>Justificativa</label><textarea name="justificativa" required></textarea></div>
                        <div class="full-width"><label>Objetivo</label><textarea name="objetivo" required></textarea></div>
                        <div class="full-width"><label>Metodologia</label><textarea name="metodologia" required></textarea></div>
                        <div class="full-width"><label>Envolvidos no projeto</label><textarea name="envolvidos" required></textarea></div>
                    </div>
                </div>

                <!-- RECURSOS E CRONOGRAMA -->
                <div class="form-section">
                    <h3>Recursos e Cronograma</h3>
                    <div class="full-width" style="margin-bottom: 15px;">
                        <label>Recursos Necessários</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="recursos[]" value="Financeiro"> Financeiro</label>
                            <label><input type="checkbox" name="recursos[]" value="Físico"> Físico</label>
                            <label><input type="checkbox" name="recursos[]" value="Humano"> Humano</label>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="full-width"><label>Detalhamento dos Recursos</label><textarea name="detalhamento_recursos"></textarea></div>
                        <div class="full-width"><label>Cronograma de Execução</label><textarea name="cronograma" required placeholder="Ex: Março a Julho de 2026"></textarea></div>
                        <div class="full-width"><label>Resultados Esperados</label><textarea name="resultados_esperados" required></textarea></div>
                    </div>
                </div>

                <div style="overflow: hidden; margin-top: 20px;">
                    <button type="submit" class="btn-submit" id="btn_submit">Enviar Solicitação de HAE</button>
                </div>
            </form>
        </div>
    </main>

    <script src="assets/js/painel.js"></script>
    <script>
        // Lógica para esconder/mostrar o campo de Projeto Anterior
        function toggleProjetoAnterior() {
            const select = document.getElementById('projeto_anterior');
            const divAnterior = document.getElementById('div_nome_anterior');
            const inputAnterior = document.getElementById('nome_projeto_anterior');
            
            if (select.value === 'Sim') {
                divAnterior.style.display = 'block';
                inputAnterior.setAttribute('required', 'required');
            } else {
                divAnterior.style.display = 'none';
                inputAnterior.removeAttribute('required');
                inputAnterior.value = '';
            }
        }

        // Lógica de cálculo automático das horas solicitada no documento
        function calcularHoras() {
            let horasAula = parseFloat(document.getElementById('horas_aula').value) || 0;
            let horasEspecificas = parseFloat(document.getElementById('horas_especificas').value) || 0;
            
            // Hora Atividade é 50% das Horas-aula
            let horasAtividade = horasAula / 2;
            document.getElementById('horas_atividade').value = horasAtividade;
            
            // Total Semanal
            let totalSemanal = horasAula + horasAtividade + horasEspecificas;
            document.getElementById('total_semanal').value = totalSemanal;
            
            // Total Mensal (Semanal x 4.5)
            let totalMensal = totalSemanal * 4.5;
            document.getElementById('total_mensal').value = totalMensal;
            
            // Validação das 200 horas
            const btnSubmit = document.getElementById('btn_submit');
            const alertaBox = document.getElementById('alerta_horas');
            const inputMensal = document.getElementById('total_mensal');
            
            if (totalMensal > 200) {
                btnSubmit.disabled = true;
                alertaBox.style.display = 'block';
                inputMensal.style.backgroundColor = '#fee2e2';
                inputMensal.style.color = '#b20000';
            } else {
                btnSubmit.disabled = false;
                alertaBox.style.display = 'none';
                inputMensal.style.backgroundColor = '#e9ecef';
                inputMensal.style.color = '#495057';
            }
        }
    </script>
</body>
</html>