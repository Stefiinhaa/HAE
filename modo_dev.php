<?php
session_start();

// Trava de Segurança Máxima: Só funciona se for o SEU usuário administrador
$nomes_admin = ['Administrador', 'Administrador MD5'];

if (!isset($_SESSION['usuario_nome']) || !in_array($_SESSION['usuario_nome'], $nomes_admin)) {
    die("Acesso restrito a desenvolvedores.");
}

// Verifica qual perfil você clicou para simular
if (isset($_GET['simular'])) {
    $nova_funcao = $_GET['simular'];
    $funcoes_validas = ['Professor', 'Coordenador', 'Diretor'];
    
    if (in_array($nova_funcao, $funcoes_validas)) {
        // Sobrescreve a função atual na sessão
        $_SESSION['usuario_funcao'] = $nova_funcao;
    }
}

// Devolve você para o painel com o perfil novo já carregado
header("Location: painel.php");
exit;
?>