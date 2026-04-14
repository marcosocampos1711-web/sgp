<?php
// Verifica se já existe uma sessão ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

/**
 * Função para verificar se o usuário tem permissão para acessar certas páginas
 * @param string $nivel_necessario O perfil exigido (ex: 'admin')
 */
function verificarAcesso($nivel_necessario = 'usuario') {
    if ($nivel_necessario === 'admin' && ($_SESSION['usuario']['perfil'] !== 'admin')) {
        echo "
        <script>
            alert('Usuário não Autorizado! Acesso restrito a administradores.');
            window.location.href = 'index.php';
        </script>";
        exit;
    }
}
?>