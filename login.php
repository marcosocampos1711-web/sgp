<?php
session_start();
require_once 'bd.php'; // Carrega a conexão com o Supabase

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = $_POST['matricula'] ?? '';
    $senha = $_POST['senha'] ?? '';

    try {
        // No PostgreSQL (Supabase), a consulta permanece similar, 
        // mas garantimos que a conexão venha do $pdo definido em bd.php
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE matricula = ?");
        $stmt->execute([$matricula]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($senha, $user['senha'])) {
            // Salva os dados do usuário na sessão
            $_SESSION['usuario'] = $user;
            header("Location: index.php");
            exit;
        } else {
            $erro = "Matrícula ou Senha Incorretos!";
        }
    } catch (PDOException $e) {
        $erro = "Erro no banco de dados. Contate o administrador.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP - Login</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-container">
    <div class="login-card">
        <div class="shield-icon-container">
            <img src="brasao_cei.png" alt="Brasão CEI" width="50">
            <img src="brasao_pmse.png" alt="Brasão PMSE" width="60">
            <img src="brasao_cfap.png" alt="Brasão CFAP" width="50">
        </div>

        <div class="text-center">
            <h2 class="system-title">Acesso ao Sistema</h2>
            <p class="system-subtitle">SGP - Sistema Gerenciador de Passe Escolar</p>
        </div>

        <?php if(isset($erro)): ?>
            <div class="alert alert-danger alert-custom d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($erro) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="matricula" class="form-label">Matrícula</label>
                <input type="text" name="matricula" id="matricula" 
                       placeholder="0000000000-00" 
                       class="form-control border-primary" 
                       pattern="\d{10}-\d{2}" 
                       required>
            </div>
            
            <div class="mb-4">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" 
                       placeholder="Digite sua senha" required>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100">
                <i class="bi bi-box-arrow-in-right"></i> ENTRAR
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Focar no campo matrícula ao carregar
        document.addEventListener('DOMContentLoaded', () => {
            const inputMatricula = document.getElementById('matricula');
            setTimeout(() => inputMatricula.focus(), 300);

            // Máscara de Matrícula (0000000000-00)
            inputMatricula.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) {
                    value = value.replace(/^(\d{10})(\d{2}).*/, '$1-$2');
                }
                e.target.value = value;
            });
        });
    </script>
</body>
</html>