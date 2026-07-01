<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_funcao'] !== 'Professor') {
    header("Location: painel.php");
    exit;
}

$erro = "";
$sucesso = "";

// ==============================================================================
// BUSCA AS CATEGORIAS DO BANCO
// ==============================================================================
$stmt_cat = $pdo->query("SELECT nome FROM categorias_projeto ORDER BY nome ASC");
$categorias_db = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);
$categorias_padrao = ['Acadêmico', 'Administrativo', 'Extensão à comunidade'];

// BUSCA OS COORDENADORES PARA O DROPDOWN
$stmt_coords = $pdo->query("SELECT id, nome FROM usuarios WHERE funcao = 'Coordenador' ORDER BY nome ASC");
$lista_coordenadores = $stmt_coords->fetchAll(PDO::FETCH_ASSOC);

// LÓGICA DE CLONAGEM
$clone_id = isset($_GET['clone_id']) ? (int) $_GET['clone_id'] : 0;
$clone = null;

if ($clone_id > 0) {
    $stmt_clone = $pdo->prepare("SELECT * FROM solicitacoes_hae WHERE id = ? AND professor_id = ?");
    $stmt_clone->execute([$clone_id, $_SESSION['usuario_id']]);
    $clone = $stmt_clone->fetch(PDO::FETCH_ASSOC);
}

$c_semestre = $clone['semestre'] ?? '';
$c_qtd_horas = $clone['quantidade_horas'] ?? '';
$c_titulo = $clone['titulo_projeto'] ?? '';
$c_proj_anterior = $clone ? 'Sim' : 'Não';
$c_nome_anterior = $clone ? $clone['titulo_projeto'] : '';
$c_obj_escola = $clone['objetivos_escola'] ?? '';
$c_horas_aula = $clone['horas_aula'] ?? '0';
$c_horas_esp = $clone['horas_especificas'] ?? '0';
$c_categoria = $clone['categoria'] ?? '';
$c_justificativa = $clone['justificativa'] ?? '';
$c_objetivo = $clone['objetivo'] ?? '';
$c_metodologia = $clone['metodologia'] ?? '';
$c_envolvidos = $clone['envolvidos'] ?? '';
$c_detalhamento = $clone['detalhamento_recursos'] ?? '';
$c_cronograma = $clone['cronograma'] ?? '';
$c_resultados = $clone['resultados_esperados'] ?? '';
$c_coordenador_alvo = $clone['coordenador_alvo_id'] ?? '';

$recursos_array = [];
if ($clone && !empty($clone['recursos_necessarios'])) {
    $recursos_array = array_map('trim', explode(',', $clone['recursos_necessarios']));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $professor_id = $_SESSION['usuario_id'];
    $semestre = $_POST['semestre'];
    $quantidade_horas = $_POST['quantidade_horas'];
    $titulo_projeto = $_POST['titulo_projeto'];
    $projeto_anterior = $_POST['projeto_anterior'] == 'Sim' ? 1 : 0;
    $nome_projeto_anterior = $projeto_anterior ? $_POST['nome_projeto_anterior'] : null;
    $objetivos_escola = $_POST['objetivos_escola'];

    $horas_aula = $_POST['horas_aula'];
    $horas_atividade = $_POST['horas_atividade'];
    $horas_especificas = $_POST['horas_especificas'];
    $total_semanal = $_POST['total_semanal'];
    $total_mensal = $_POST['total_mensal'];

    $categoria = trim($_POST['categoria']);
    $justificativa = $_POST['justificativa'];
    $objetivo = $_POST['objetivo'];
    $metodologia = $_POST['metodologia'];
    $envolvidos = $_POST['envolvidos'];

    $recursos_necessarios = isset($_POST['recursos']) ? implode(',', $_POST['recursos']) : '';
    $detalhamento_recursos = $_POST['detalhamento_recursos'];
    $cronograma = $_POST['cronograma'];
    $resultados_esperados = $_POST['resultados_esperados'];

    // Pega o coordenador escolhido
    $coordenador_alvo_id = !empty($_POST['coordenador_alvo_id']) ? $_POST['coordenador_alvo_id'] : null;

    if ($total_mensal > 200) {
        $erro = "O total mensal não pode ultrapassar 200 horas.";
    } else {
        try {
            $sql = "INSERT INTO solicitacoes_hae 
                    (professor_id, coordenador_alvo_id, semestre, quantidade_horas, titulo_projeto, projeto_anterior, nome_projeto_anterior, 
                    objetivos_escola, horas_aula, horas_atividade, horas_especificas, total_semanal, total_mensal, 
                    categoria, justificativa, objetivo, metodologia, envolvidos, recursos_necessarios, detalhamento_recursos, 
                    cronograma, resultados_esperados) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $professor_id,
                $coordenador_alvo_id,
                $semestre,
                $quantidade_horas,
                $titulo_projeto,
                $projeto_anterior,
                $nome_projeto_anterior,
                $objetivos_escola,
                $horas_aula,
                $horas_atividade,
                $horas_especificas,
                $total_semanal,
                $total_mensal,
                $categoria,
                $justificativa,
                $objetivo,
                $metodologia,
                $envolvidos,
                $recursos_necessarios,
                $detalhamento_recursos,
                $cronograma,
                $resultados_esperados
            ]);

            $sucesso = "Solicitação de HAE enviada com sucesso para análise da coordenação!";
            $clone = null;
            $c_titulo = '';
            $c_obj_escola = '';
            $c_justificativa = '';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border-top: 4px solid var(--fatec-red); }
        .form-section { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .form-section h3 { color: var(--fatec-red); margin-bottom: 15px; font-size: 16px; border-left: 3px solid var(--fatec-red); padding-left: 10px; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .full-width { grid-column: 1 / -1; }
        
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #444; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px; border: 1.5px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; transition: 0.3s; }
        input:focus, select:focus, textarea:focus { border-color: var(--fatec-red); }
        textarea { resize: vertical; min-height: 80px; }
        
        .checkbox-group { display: flex; gap: 15px; align-items: center; }
        .checkbox-group label { display: flex; align-items: center; font-weight: normal; font-size: 14px; gap: 5px; cursor: pointer; }
        
        .calc-box { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; }
        .calc-box input[readonly] { background: #e9ecef; color: #495057; cursor: not-allowed; font-weight: bold; }
        
        .btn-submit { background: var(--fatec-red); color: white; padding: 12px 25px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; float: right; transition: 0.3s;}
        .btn-submit:hover { background: #8a0000; }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; }
        
        .alerta-horas { color: #b20000; font-size: 12px; font-weight: bold; margin-top: 5px; display: none; }
        .aviso-clone { background: #fff3cd; color: #856404; padding: 15px; border-radius: 6px; border-left: 4px solid #f39c12; margin-bottom: 20px; font-size: 14px; }
        
        /* ESTILOS DO DROPDOWN INTELIGENTE DE CATEGORIA */
        .dropdown-container { position: relative; width: 100%; }
        .custom-dropdown { 
            display: none; 
            position: absolute; 
            top: 100%; 
            left: 0; 
            width: 100%; 
            background: #fff; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            max-height: 200px; 
            overflow-y: auto; 
            z-index: 1000; 
            list-style: none; 
            margin-top: 5px;
        }
        .custom-dropdown li { 
            padding: 10px 15px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid #f5f5f5; 
            cursor: pointer; 
            font-size: 14px; 
            color: #444; 
            transition: 0.2s;
        }
        .custom-dropdown li:hover { background-color: #f4fbf7; color: #1e824c; }
        .btn-excluir-cat { 
            color: #ccc; 
            background: none; 
            border: none; 
            cursor: pointer; 
            padding: 5px; 
            font-size: 14px; 
            transition: 0.2s; 
        }
        .btn-excluir-cat:hover { color: #e74c3c; }

        /* Nova box do alvo de coordenador */
        .box-encaminhamento {
            background-color: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            border-left: 4px solid #3498db;
            margin-bottom: 30px;
        }
        .box-encaminhamento h3 { color: #2c3e50; border-left: none; padding-left: 0; margin-top: 0; }
        .box-encaminhamento p { font-size: 13px; color: #666; margin-bottom: 15px; }

        /* RESPONSIVIDADE - ÁREA DA CARGA HORÁRIA E CAMPOS DE FORMULÁRIO */
        @media (max-width: 768px) {
            .form-card { padding: 20px; }
            .grid-2, .grid-3 { grid-template-columns: 1fr; gap: 15px; } /* Quebra os campos lado a lado em uma coluna */
            .calc-box { padding: 15px; } /* Mantém a caixa de cálculo mais confortável */
            .checkbox-group { flex-direction: column; align-items: flex-start; gap: 10px; } /* Melhora as opções de recursos */
        }
    </style>
</head>
<body>

    <?php $pagina_atual = basename($_SERVER['PHP_SELF']); ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="painel.php" class="brand">
                <img src="img/cps_fatecgarca_logo.jfif" alt="Logo Fatec">
                <h2 class="brand-text">HAE</h2>
            </a>
            <button class="collapse-btn" id="collapse-btn" title="Minimizar Menu"><i class="fa-solid fa-bars-staggered"></i></button>
        </div>
        <nav class="menu">
            <div class="menu-title">Navegação</div>
            <ul>
                <li><a href="painel.php"><i class="fa-solid fa-chart-pie"></i> <span class="menu-text">Dashboard</span></a></li>
                <li><a href="nova_solicitacao.php" class="active"><i class="fa-solid fa-file-circle-plus"></i> <span class="menu-text">Nova Solicitação</span></a></li>
                <li><a href="meus_projetos.php"><i class="fa-solid fa-folder-open"></i> <span class="menu-text">Meus Projetos</span></a></li>
                <li><a href="enviar_relatorio.php"><i class="fa-solid fa-calendar-check"></i> <span class="menu-text">Enviar Relatório</span></a></li>
                <li><a href="meus_rascunhos.php"><i class="fa-solid fa-file-pen"></i> <span class="menu-text">Meus Rascunhos</span></a></li>
                <li><a href="perfil.php"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">Meu Perfil</span></a></li>
                <li><a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> <span class="menu-text">Sair do Sistema</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-top">
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <h1>Solicitação de HAE</h1>
            </div>
            <div class="user-info">Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></div>
        </header>

        <?php if ($clone): ?>
                <div class="aviso-clone">
                    <i class="fa-solid fa-clone"></i> <strong>Modo Clonagem:</strong> O formulário foi preenchido com as informações do seu projeto antigo. Atualize o Semestre e faça as alterações necessárias antes de enviar.
                </div>
        <?php endif; ?>

        <?php if ($sucesso)
            echo "<div class='alert-success'>$sucesso</div>"; ?>
        <?php if ($erro)
            echo "<div class='alert-success' style='background:#fee2e2; color:#b91c1c; border-color:#b91c1c;'>$erro</div>"; ?>

        <div class="form-card">
            <form method="POST">
                
                <div class="form-section">
                    <h3>Dados do Projeto</h3>
                    <div class="grid-2">
                        <div><label>Semestre / Ano (Ex: 1/2026)</label><input type="text" name="semestre" value="<?php echo htmlspecialchars($c_semestre); ?>" required placeholder="Ex: 1/2026"></div>
                        <div><label>Quantidade de HAE Solicitada</label><input type="number" name="quantidade_horas" value="<?php echo htmlspecialchars($c_qtd_horas); ?>" required min="1"></div>
                        <div class="full-width"><label>Título do Projeto</label><input type="text" name="titulo_projeto" value="<?php echo htmlspecialchars($c_titulo); ?>" required></div>
                        <div>
                            <label>Relacionado a projeto anterior?</label>
                            <select name="projeto_anterior" id="projeto_anterior" onchange="toggleProjetoAnterior()" required>
                                <option value="Não" <?php echo $c_proj_anterior == 'Não' ? 'selected' : ''; ?>>Não</option>
                                <option value="Sim" <?php echo $c_proj_anterior == 'Sim' ? 'selected' : ''; ?>>Sim</option>
                            </select>
                        </div>
                        <div id="div_nome_anterior" style="display: none;">
                            <label>Nome do Projeto Anterior</label>
                            <input type="text" name="nome_projeto_anterior" id="nome_projeto_anterior" value="<?php echo htmlspecialchars($c_nome_anterior); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section calc-box">
                    <h3>Carga Horária Semanal (Cálculo Automático)</h3>
                    <div class="grid-3">
                        <div><label>Horas-aula</label><input type="number" name="horas_aula" id="horas_aula" value="<?php echo $c_horas_aula; ?>" min="0" onfocus="if(this.value=='0') this.value='';" onblur="if(this.value=='') this.value='0'; calcularHoras();" oninput="calcularHoras()"></div>
                        <div><label>Hora Atividade (50%)</label><input type="number" name="horas_atividade" id="horas_atividade" value="0" readonly></div>
                        <div><label>HAE do Projeto</label><input type="number" name="horas_especificas" id="horas_especificas" value="<?php echo $c_horas_esp; ?>" min="0" onfocus="if(this.value=='0') this.value='';" onblur="if(this.value=='') this.value='0'; calcularHoras();" oninput="calcularHoras()"></div>
                    </div>
                    <br>
                    <div class="grid-2">
                        <div><label>Total Semanal</label><input type="number" name="total_semanal" id="total_semanal" value="0" readonly></div>
                        <div>
                            <label>Total Mensal (Semanas x 4,5)</label>
                            <input type="number" name="total_mensal" id="total_mensal" value="0" readonly>
                            <div id="alerta_horas" class="alerta-horas">Atenção: O total não pode ultrapassar 200 horas!</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Detalhamento Acadêmico</h3>
                    <div class="grid-2">
                        <div class="full-width"><label>Objetivos/Meta(s) da Escola vinculada(s)</label><textarea name="objetivos_escola" required><?php echo htmlspecialchars($c_obj_escola); ?></textarea></div>
                        
                        <div class="full-width">
                            <label>Categoria do Projeto</label>
                            <div class="dropdown-container">
                                <input type="text" name="categoria" id="categoria" autocomplete="off" value="<?php echo htmlspecialchars($c_categoria); ?>" required placeholder="Selecione na lista ou digite uma nova categoria para salvar...">
                                <ul id="lista_categorias" class="custom-dropdown">
                                    <?php foreach ($categorias_db as $cat): ?>
                                            <li onmousedown="event.preventDefault(); selecionarCategoria('<?php echo htmlspecialchars($cat); ?>')">
                                                <span class="cat-text" style="flex:1;"><?php echo htmlspecialchars($cat); ?></span>
                                            
                                                <?php if (!in_array($cat, $categorias_padrao)): ?>
                                                        <button type="button" class="btn-excluir-cat" title="Remover da lista" onmousedown="event.preventDefault(); excluirCategoria(event, '<?php echo htmlspecialchars($cat); ?>', this.parentElement)">
                                                            <i class="fa-solid fa-xmark"></i>
                                                        </button>
                                                <?php endif; ?>
                                            </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="full-width"><label>Justificativa</label><textarea name="justificativa" required><?php echo htmlspecialchars($c_justificativa); ?></textarea></div>
                        <div class="full-width"><label>Objetivo</label><textarea name="objetivo" required><?php echo htmlspecialchars($c_objetivo); ?></textarea></div>
                        <div class="full-width"><label>Metodologia</label><textarea name="metodologia" required><?php echo htmlspecialchars($c_metodologia); ?></textarea></div>
                        <div class="full-width"><label>Envolvidos no projeto</label><textarea name="envolvidos" required><?php echo htmlspecialchars($c_envolvidos); ?></textarea></div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Recursos e Cronograma</h3>
                    <div class="full-width" style="margin-bottom: 15px;">
                        <label>Recursos Necessários</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="recursos[]" value="Financeiro" <?php echo in_array('Financeiro', $recursos_array) ? 'checked' : ''; ?>> Financeiro</label>
                            <label><input type="checkbox" name="recursos[]" value="Físico" <?php echo in_array('Físico', $recursos_array) ? 'checked' : ''; ?>> Físico</label>
                            <label><input type="checkbox" name="recursos[]" value="Humano" <?php echo in_array('Humano', $recursos_array) ? 'checked' : ''; ?>> Humano</label>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="full-width"><label>Detalhamento dos Recursos</label><textarea name="detalhamento_recursos"><?php echo htmlspecialchars($c_detalhamento); ?></textarea></div>
                        <div class="full-width"><label>Cronograma de Execução</label><textarea name="cronograma" required placeholder="Ex: Março a Julho de 2026"><?php echo htmlspecialchars($c_cronograma); ?></textarea></div>
                        <div class="full-width"><label>Resultados Esperados</label><textarea name="resultados_esperados" required><?php echo htmlspecialchars($c_resultados); ?></textarea></div>
                    </div>
                </div>

                <div class="box-encaminhamento">
                    <h3><i class="fa-solid fa-user-check"></i> Encaminhamento da Solicitação</h3>
                    <p>Indique o coordenador principal para analisar este projeto. A solicitação continuará visível para toda a equipe de coordenação e direção.</p>
                    <div class="grid-2">
                        <div class="full-width">
                            <label>Coordenador Responsável pela Análise (Opcional)</label>
                            <select name="coordenador_alvo_id">
                                <option value="">-- Selecionar Coordenador --</option>
                                <?php foreach ($lista_coordenadores as $coord): ?>
                                        <option value="<?php echo $coord['id']; ?>" <?php echo ($c_coordenador_alvo == $coord['id']) ? 'selected' : ''; ?>>
                                             <?php echo htmlspecialchars($coord['nome']); ?>
                                        </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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

        function calcularHoras() {
            let horasAula = parseFloat(document.getElementById('horas_aula').value) || 0;
            let horasEspecificas = parseFloat(document.getElementById('horas_especificas').value) || 0;
            let horasAtividade = horasAula / 2;
            document.getElementById('horas_atividade').value = horasAtividade.toFixed(1);
            let totalSemanal = horasAula + horasAtividade + horasEspecificas;
            document.getElementById('total_semanal').value = totalSemanal.toFixed(1);
            let totalMensal = totalSemanal * 4.5;
            document.getElementById('total_mensal').value = totalMensal.toFixed(1);
            
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

        // =========================================================================
        // LÓGICA DO DROPDOWN INTELIGENTE DE CATEGORIAS
        // =========================================================================
        const inputCat = document.getElementById('categoria');
        const listaCat = document.getElementById('lista_categorias');

        inputCat.addEventListener('focus', () => {
            listaCat.style.display = 'block';
        });

        // Esconde a lista ao clicar fora e SALVA silenciosamente
        inputCat.addEventListener('blur', () => {
            setTimeout(() => {
                listaCat.style.display = 'none';
                
                let valor = inputCat.value.trim();
                if (valor !== '') {
                    fetch('api_categoria.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'acao=salvar&categoria=' + encodeURIComponent(valor)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.sucesso && data.nova) {
                            let li = document.createElement('li');
                            li.onmousedown = function(e) { e.preventDefault(); selecionarCategoria(valor); };
                            li.innerHTML = `<span class="cat-text" style="flex:1;">${valor}</span>
                                            <button type="button" class="btn-excluir-cat" title="Remover da lista" onmousedown="event.preventDefault(); excluirCategoria(event, '${valor}', this.parentElement)">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>`;
                            listaCat.appendChild(li);
                        }
                    }).catch(err => console.error(err));
                }
            }, 200);
        });

        // Filtra a lista ao digitar
        inputCat.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const items = listaCat.querySelectorAll('li');
            items.forEach(item => {
                const text = item.querySelector('.cat-text').textContent.toLowerCase();
                if (text.includes(filter)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        window.selecionarCategoria = function(nome) {
            inputCat.value = nome;
            listaCat.style.display = 'none';
        };

        window.excluirCategoria = function(event, nome, liElement) {
            event.stopPropagation();
            
            fetch('api_categoria.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'acao=excluir&categoria=' + encodeURIComponent(nome)
            }).then(() => {
                liElement.remove(); 
            }).catch(err => console.error('Erro ao excluir:', err));
        };

        document.addEventListener("DOMContentLoaded", function() {
            toggleProjetoAnterior();
            calcularHoras();
        });
    </script>
</body>
</html>