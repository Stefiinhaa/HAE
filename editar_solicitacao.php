<?php
session_start();
require 'config/conexao.php';

// Segurança: Apenas Professor acessa
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] != 'Professor') {
    header("Location: painel.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$projeto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = "";

// 1. Busca os dados atuais do projeto para preencher o formulário
$stmt = $pdo->prepare("SELECT * FROM solicitacoes_hae WHERE id = ? AND professor_id = ? AND status_aprovacao = 'Rejeitado'");
$stmt->execute([$projeto_id, $usuario_id]);
$projeto = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não achar o projeto ou ele não estiver rejeitado, bloqueia o acesso
if (!$projeto) {
    die("Acesso negado. Este projeto não existe ou não está disponível para edição.");
}

// 2. Processa a atualização (Reenvio)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $semestre = trim($_POST['semestre']);
    $quantidade_horas = (int)$_POST['quantidade_horas'];
    $titulo_projeto = trim($_POST['titulo_projeto']);
    $projeto_anterior = (int)$_POST['projeto_anterior'];
    $nome_projeto_anterior = $projeto_anterior ? trim($_POST['nome_projeto_anterior']) : null;
    $objetivos_escola = trim($_POST['objetivos_escola']);
    $horas_aula = (int)$_POST['horas_aula'];
    $horas_atividade = (int)$_POST['horas_atividade'];
    $horas_especificas = (int)$_POST['horas_especificas'];
    $total_semanal = (int)$_POST['total_semanal'];
    $total_mensal = (int)$_POST['total_mensal'];
    $categoria = trim($_POST['categoria']);
    $justificativa = trim($_POST['justificativa']);
    $objetivo = trim($_POST['objetivo']);
    $metodologia = trim($_POST['metodologia']);
    $envolvidos = trim($_POST['envolvidos']);
    $detalhamento_recursos = trim($_POST['detalhamento_recursos']);
    $cronograma = trim($_POST['cronograma']);
    $resultados_esperados = trim($_POST['resultados_esperados']);

    // Concatena os recursos marcados
    $recursos = isset($_POST['recursos']) ? implode(", ", $_POST['recursos']) : '';

    if ($total_mensal > 200) {
        $erro = "Atenção: O Total Mensal não pode ultrapassar 200 horas!";
    } else {
        try {
            // Atualiza os dados E RESETA os status para Pendente
            $sql = "UPDATE solicitacoes_hae SET 
                    semestre = ?, quantidade_horas = ?, titulo_projeto = ?, projeto_anterior = ?, 
                    nome_projeto_anterior = ?, objetivos_escola = ?, horas_aula = ?, horas_atividade = ?, 
                    horas_especificas = ?, total_semanal = ?, total_mensal = ?, categoria = ?, 
                    justificativa = ?, objetivo = ?, metodologia = ?, envolvidos = ?, recursos_necessarios = ?, 
                    detalhamento_recursos = ?, cronograma = ?, resultados_esperados = ?,
                    status_aprovacao = 'Pendente', status_coordenador = 'Pendente', status_diretor = 'Pendente',
                    parecer_coordenador = NULL, parecer_diretor = NULL, data_aprovacao_coordenador = NULL, data_aprovacao_diretor = NULL
                    WHERE id = ? AND professor_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $semestre, $quantidade_horas, $titulo_projeto, $projeto_anterior, 
                $nome_projeto_anterior, $objetivos_escola, $horas_aula, $horas_atividade, 
                $horas_especificas, $total_semanal, $total_mensal, $categoria, 
                $justificativa, $objetivo, $metodologia, $envolvidos, $recursos, 
                $detalhamento_recursos, $cronograma, $resultados_esperados,
                $projeto_id, $usuario_id
            ]);

            header("Location: meus_projetos.php?status=reenviado");
            exit;
        } catch (PDOException $e) {
            $erro = "Erro ao salvar: " . $e->getMessage();
        }
    }
}

$pagina_atual = 'meus_projetos.php'; // Mantém o menu "Meus Projetos" ativo
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Solicitação - HAE Fatec</title>
    <link rel="stylesheet" href="assets/css/painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border-top: 4px solid #f39c12; margin-bottom: 30px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .full { grid-column: 1 / -1; }
        
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #444; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; transition: 0.3s; background: #fff; }
        input:focus, select:focus, textarea:focus { border-color: #f39c12; }
        input[readonly] { background: #f4f6f9; color: #666; cursor: not-allowed; border-color: #ddd; }
        
        .section-title { font-size: 16px; color: var(--fatec-red); border-bottom: 2px solid #eee; padding-bottom: 10px; margin: 30px 0 20px 0; }
        
        .btn-submit { background: #f39c12; color: white; padding: 15px 30px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; transition: 0.3s; width: 100%; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-submit:hover { background: #d68910; }
        
        .aviso-revisao { background: #fff3cd; color: #856404; padding: 15px; border-radius: 6px; border-left: 4px solid #ffeeba; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>

<aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="painel.php" class="brand">
                <img src="img/cps_fatecgarca_logo.jfif" alt="Logo Fatec">
                <h2 class="brand-text">HAE</h2>
            </a>
            <button class="collapse-btn" id="collapse-btn" title="Minimizar Menu">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
        </div>
        
        <nav class="menu">
            <div class="menu-title">Navegação</div>
            <ul>
                <li>
                    <a href="painel.php" class="<?php echo ($pagina_atual == 'painel.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-chart-pie"></i> <span class="menu-text">Dashboard</span>
                    </a>
                </li>
                
                <?php if ($_SESSION['usuario_funcao'] == 'Professor'): ?>
                    <li>
                        <a href="nova_solicitacao.php" class="<?php echo ($pagina_atual == 'nova_solicitacao.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-file-circle-plus"></i> <span class="menu-text">Nova Solicitação</span>
                        </a>
                    </li>
                    <li>
                        <a href="meus_projetos.php" class="<?php echo ($pagina_atual == 'meus_projetos.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-folder-open"></i> <span class="menu-text">Meus Projetos</span>
                        </a>
                    </li>
                    <li>
                        <a href="enviar_relatorio.php" class="<?php echo ($pagina_atual == 'enviar_relatorio.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-calendar-check"></i> <span class="menu-text">Enviar Relatório</span>
                        </a>
                    </li>
                    <li>
                        <a href="meus_relatorios.php" class="<?php echo ($pagina_atual == 'meus_relatorios.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-list-check"></i> <span class="menu-text">Meus Relatórios</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="analisar_solicitacoes.php" class="<?php echo ($pagina_atual == 'analisar_solicitacoes.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">Analisar Solicitações</span>
                        </a>
                    </li>
                    <li>
                        <a href="acompanhar_relatorios.php" class="<?php echo ($pagina_atual == 'acompanhar_relatorios.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-chart-line"></i> <span class="menu-text">Acompanhar Relatórios</span>
                        </a>
                    </li>
                    <li>
                        <a href="cadastrar_professor.php" class="<?php echo ($pagina_atual == 'cadastrar_professor.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-user-plus"></i> <span class="menu-text">Cadastrar Usuário</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <li>
                    <a href="perfil.php" class="<?php echo ($pagina_atual == 'perfil.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php" class="logout-link">
                        <i class="fa-solid fa-right-from-bracket"></i> <span class="menu-text">Sair do Sistema</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Editar Projeto Rejeitado</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <a href="meus_projetos.php" style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: #666; text-decoration: none; font-weight: bold; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Voltar</a>

        <div class="aviso-revisao">
            <i class="fa-solid fa-triangle-exclamation"></i> <strong>Atenção:</strong> Corrija os apontamentos solicitados pela Direção/Coordenação. Ao clicar em "Salvar e Reenviar", este projeto voltará para a fila de análise.
        </div>

        <?php if($erro) echo "<div class='alert-success' style='background:#fee2e2; color:#b91c1c; border-color:#b91c1c; margin-bottom:20px;'>❌ $erro</div>"; ?>

        <form method="POST" class="form-card">
            
            <h3 class="section-title">Dados Iniciais</h3>
            <div class="grid-2">
                <div>
                    <label>Semestre / Ano</label>
                    <input type="text" name="semestre" value="<?php echo htmlspecialchars($projeto['semestre']); ?>" required placeholder="Ex: 1/2026">
                </div>
                <div>
                    <label>Quantidade de HAE Solicitada</label>
                    <input type="number" name="quantidade_horas" value="<?php echo htmlspecialchars($projeto['quantidade_horas']); ?>" required min="1">
                </div>
            </div>

            <div class="grid-2">
                <div class="full">
                    <label>Título do Projeto</label>
                    <input type="text" name="titulo_projeto" value="<?php echo htmlspecialchars($projeto['titulo_projeto']); ?>" required>
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <label>Relacionado com projeto anterior?</label>
                    <select name="projeto_anterior" id="projeto_anterior" onchange="toggleAnterior()">
                        <option value="0" <?php echo $projeto['projeto_anterior'] == 0 ? 'selected' : ''; ?>>Não</option>
                        <option value="1" <?php echo $projeto['projeto_anterior'] == 1 ? 'selected' : ''; ?>>Sim</option>
                    </select>
                </div>
                <div id="div_nome_anterior" style="<?php echo $projeto['projeto_anterior'] == 0 ? 'display: none;' : ''; ?>">
                    <label>Nome do Projeto Anterior</label>
                    <input type="text" name="nome_projeto_anterior" id="nome_projeto_anterior" value="<?php echo htmlspecialchars($projeto['nome_projeto_anterior']); ?>">
                </div>
            </div>

            <div class="full" style="margin-top: 15px;">
                <label>Objetivos/Meta(s) da Escola à(s) qual(is) o projeto está vinculado</label>
                <textarea name="objetivos_escola" rows="3" required><?php echo htmlspecialchars($projeto['objetivos_escola']); ?></textarea>
            </div>

            <h3 class="section-title">Cálculo de Carga Horária Semanal</h3>
            <div class="grid-3">
                <div>
                    <label>Horas aula</label>
                    <input type="number" name="horas_aula" id="horas_aula" value="<?php echo $projeto['horas_aula']; ?>" required oninput="calcularHoras()">
                </div>
                <div>
                    <label>Hora Atividade (Calculado Autom.)</label>
                    <input type="number" name="horas_atividade" id="horas_atividade" value="<?php echo $projeto['horas_atividade']; ?>" readonly>
                </div>
                <div>
                    <label>HAE Específica do Projeto</label>
                    <input type="number" name="horas_especificas" id="horas_especificas" value="<?php echo $projeto['horas_especificas']; ?>" required oninput="calcularHoras()">
                </div>
            </div>
            <div class="grid-2">
                <div>
                    <label>Total Semanal</label>
                    <input type="number" name="total_semanal" id="total_semanal" value="<?php echo $projeto['total_semanal']; ?>" readonly>
                </div>
                <div>
                    <label>Total Mensal <small style="color:var(--fatec-red);">(Máx 200h)</small></label>
                    <input type="number" name="total_mensal" id="total_mensal" value="<?php echo $projeto['total_mensal']; ?>" readonly style="font-weight:bold;">
                </div>
            </div>

            <h3 class="section-title">Apresentação do Projeto</h3>
            <div class="grid-2">
                <div class="full">
                    <label>Categoria do Projeto</label>
                    <select name="categoria" required>
                        <option value="Administrativo" <?php echo $projeto['categoria'] == 'Administrativo' ? 'selected' : ''; ?>>Administrativo</option>
                        <option value="Acadêmico" <?php echo $projeto['categoria'] == 'Acadêmico' ? 'selected' : ''; ?>>Acadêmico</option>
                        <option value="Extensão a comunidade" <?php echo $projeto['categoria'] == 'Extensão a comunidade' ? 'selected' : ''; ?>>Extensão a comunidade</option>
                    </select>
                </div>
            </div>

            <div class="grid-2">
                <div class="full"><label>Justificativa</label><textarea name="justificativa" rows="3" required><?php echo htmlspecialchars($projeto['justificativa']); ?></textarea></div>
                <div class="full"><label>Objetivo</label><textarea name="objetivo" rows="3" required><?php echo htmlspecialchars($projeto['objetivo']); ?></textarea></div>
                <div class="full"><label>Metodologia</label><textarea name="metodologia" rows="3" required><?php echo htmlspecialchars($projeto['metodologia']); ?></textarea></div>
                <div class="full"><label>Envolvidos no projeto</label><textarea name="envolvidos" rows="3" required><?php echo htmlspecialchars($projeto['envolvidos']); ?></textarea></div>
            </div>

            <div class="full" style="margin-top: 15px;">
                <label>Recursos Necessários</label>
                <?php $recursos_array = explode(", ", $projeto['recursos_necessarios']); ?>
                <div style="display: flex; gap: 20px; margin-bottom: 10px;">
                    <label style="font-weight: normal;"><input type="checkbox" name="recursos[]" value="Financeiro" <?php echo in_array('Financeiro', $recursos_array) ? 'checked' : ''; ?>> Financeiro</label>
                    <label style="font-weight: normal;"><input type="checkbox" name="recursos[]" value="Físico" <?php echo in_array('Físico', $recursos_array) ? 'checked' : ''; ?>> Físico</label>
                    <label style="font-weight: normal;"><input type="checkbox" name="recursos[]" value="Humano" <?php echo in_array('Humano', $recursos_array) ? 'checked' : ''; ?>> Humano</label>
                </div>
                <textarea name="detalhamento_recursos" rows="2" placeholder="Detalhamento dos Recursos Necessários..." required><?php echo htmlspecialchars($projeto['detalhamento_recursos']); ?></textarea>
            </div>

            <div class="grid-2" style="margin-top: 20px;">
                <div class="full"><label>Cronograma de Execução</label><textarea name="cronograma" rows="3" required><?php echo htmlspecialchars($projeto['cronograma']); ?></textarea></div>
                <div class="full"><label>Resultados Esperados</label><textarea name="resultados_esperados" rows="3" required><?php echo htmlspecialchars($projeto['resultados_esperados']); ?></textarea></div>
            </div>

            <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Salvar e Reenviar Projeto</button>
        </form>
    </main>

    <script src="assets/js/painel.js"></script>
    <script>
        function toggleAnterior() {
            var val = document.getElementById('projeto_anterior').value;
            var div = document.getElementById('div_nome_anterior');
            var input = document.getElementById('nome_projeto_anterior');
            if(val == '1') {
                div.style.display = 'block';
                input.setAttribute('required', 'required');
            } else {
                div.style.display = 'none';
                input.removeAttribute('required');
                input.value = '';
            }
        }

        function calcularHoras() {
            var aula = parseFloat(document.getElementById('horas_aula').value) || 0;
            var especifica = parseFloat(document.getElementById('horas_especificas').value) || 0;
            
            var atividade = aula * 0.5; // 50%
            document.getElementById('horas_atividade').value = atividade.toFixed(1);
            
            var totalSemanal = aula + atividade + especifica;
            document.getElementById('total_semanal').value = totalSemanal.toFixed(1);
            
            var totalMensal = totalSemanal * 4.5;
            var inputMensal = document.getElementById('total_mensal');
            inputMensal.value = totalMensal.toFixed(1);
            
            if(totalMensal > 200) {
                inputMensal.style.color = 'red';
                inputMensal.style.borderColor = 'red';
            } else {
                inputMensal.style.color = 'inherit';
                inputMensal.style.borderColor = '#ddd';
            }
        }
    </script>
</body>
</html>