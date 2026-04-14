<?php
require 'auth.php'; // Proteção contra acesso não autorizado
require 'bd.php';   // Conexão com o Supabase (PostgreSQL)

if ($_SESSION['usuario']['perfil'] === 'admin'):

date_default_timezone_set('America/Maceio');

try {
    // Lógica para Salvar/Excluir
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['excluir'])) {
            // No PostgreSQL, o ID geralmente é serial, a lógica de exclusão permanece idêntica
            $stmt = $pdo->prepare("DELETE FROM turmas WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        } else {
            // Inserção de nova turma
            $nome_turma = mb_strtoupper($_POST['nome_turma'], 'UTF-8');
            $ano = $_POST['ano'];
            
            $stmt = $pdo->prepare("INSERT INTO turmas (nome_turma, ano) VALUES (?, ?)");
            $stmt->execute([$nome_turma, $ano]);
        }
        header("Location: turmas.php");
        exit;
    }

    // Consulta de turmas - PostgreSQL utiliza a mesma sintaxe padrão SQL
    $query = "SELECT id, nome_turma, ano FROM turmas ORDER BY ano DESC, nome_turma ASC";
    $turmas = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { 
    die("Erro na base de dados Supabase: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP - Sistema Gerenciador de Passe Escolar | Gerenciar Turmas</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

<?php include 'header.php'; ?>

<div class="container-fluid my-2">
    <?php include 'navbar.php'; ?>

    <div class="card-body p-2 rounded">
        <form method="POST" class="row g-3 mb-4 no-print">
            <div class="col-md-6">
                <label class="form-label fw-bold">NOME DA TURMA</label>
                <input type="text" name="nome_turma" id="nome_turma" class="form-control" placeholder="Ex: CFP / CFC" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">ANO</label>
                <input type="number" name="ano" id="ano" class="form-control" value="<?= date('Y') ?>" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary btn-md w-100">ADICIONAR</button>
            </div>
        </form>

        <hr>

        <div class="table-responsive rounded">
            <table class="table table-hover rounded">
                <thead class="table-dark">
                    <tr class="rounded">
                        <th>ID</th>
                        <th>TURMA</th>
                        <th>ANO</th>
                        <th class="text-center">AÇÕES</th>
                    </tr>
                </thead>
                <tbody class="rounded">
                    <?php if (empty($turmas)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Nenhuma turma cadastrada no Supabase.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($turmas as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td><?= htmlspecialchars($t['nome_turma']) ?></td>
                            <td><?= $t['ano'] ?></td>
                            <td class="text-center rounded">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" name="excluir" class="btn btn-outline-danger btn-sm" onclick="return confirm('Deseja realmente excluir esta turma?')">
                                        <i class="bi bi-trash"></i> Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const elAno = document.querySelector('input[name="ano"]');
        const elNomeTurma = document.querySelector('input[name="nome_turma"]');

        // Validação de entrada para o ano
        elAno.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
        });

        // Garantir que o nome da turma fique em caixa alta em tempo real
        elNomeTurma.addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase();
        });
    });
</script>
</body>
</html>

<?php else:
    header("Location: index.php");
endif; ?>