<?php
require 'auth.php'; 
require 'bd.php'; // Usa a conexão do Supabase

if ($_SESSION['usuario']['perfil'] !== 'admin' && $_SESSION['usuario']['perfil'] !== 'usuario') {
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$id) {
    die("ID de passe inválido ou não fornecido.");
}

try {
    // Processamento do Formulário de Decisão
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_decisao'])) {
        $status = $_POST['acao_decisao']; 
        $fundamentacao = mb_strtoupper($_POST['fundamentacao'], 'UTF-8');
        $numero_passe = ($status === 'AUTORIZADO') ? $_POST['numero_passe'] : null;
        $autorizador = $_SESSION['usuario']['nome_guerra'];

        $sql = "UPDATE passes SET 
                status = :status, 
                fundamentacao = :fund, 
                numero_passe = :num, 
                autorizacao = :aut 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':status' => $status,
            ':fund'   => $fundamentacao,
            ':num'    => $numero_passe,
            ':aut'    => $autorizador,
            ':id'     => $id
        ]);

        header("Location: relatorio.php?status=atualizado");
        exit;
    }

    // Busca dados para exibição
    $stmt = $pdo->prepare("SELECT * FROM passes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        die("Passe não encontrado no sistema.");
    }

} catch (PDOException $e) {
    die("Erro na base de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SGP | Analisar Documentação</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .viewer-container { height: 80vh; background: #525659; border-radius: 8px; }
        embed { width: 100%; height: 100%; border-radius: 8px; }
    </style>
</head>
<body class="bg-light">
<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<div class="container-fluid py-3">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-dark text-white">Informações do Passe</div>
                <div class="card-body small">
                    <p><strong>ALUNO:</strong> <?= htmlspecialchars($dados['nome_completo']) ?> (<?= htmlspecialchars($dados['nome_guerra']) ?>)</p>
                    <p><strong>MATRÍCULA:</strong> <?= htmlspecialchars($dados['matricula']) ?></p>
                    <p><strong>DESTINO:</strong> <?= htmlspecialchars($dados['destino']) ?></p>
                    <hr>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">NÚMERO DO PASSE ESCOLAR</label>
                            <input type="number" name="numero_passe" class="form-control form-control-sm" placeholder="Obrigatório para autorizar">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">FUNDAMENTAÇÃO</label>
                            <textarea name="fundamentacao" class="form-control form-control-sm" rows="3" required></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="acao_decisao" value="AUTORIZADO" class="btn btn-success">AUTORIZAR</button>
                            <button type="submit" name="acao_decisao" value="NEGADO" class="btn btn-danger">NEGAR</button>
                            <a href="relatorio.php" class="btn btn-secondary">VOLTAR</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8 text-center bg-dark rounded">
            <h6 class=" text-light mt-2"><i class="bi bi-paperclip"></i> COMPROVANTE ANEXADO</h6>
            <div class="viewer-container shadow mb-2">
                <?php if (!empty($dados['agendamento']) && file_exists($dados['agendamento'])): ?>
                    <?php $ext = pathinfo($dados['agendamento'], PATHINFO_EXTENSION); ?>
                    <?php if (strtolower($ext) === 'pdf'): ?>
                        <embed src="<?= $dados['agendamento'] ?>" type="application/pdf">
                    <?php else: ?>
                        <img src="<?= $dados['agendamento'] ?>" class="img-fluid p-2" style="max-height: 100%;">
                    <?php endif; ?>
                <?php else: ?>
                    <div class="h-100 d-flex align-items-center justify-content-center text-white flex-column">
                        <i class="bi bi-file-earmark-x" style="font-size: 3rem;"></i>
                        <p>Nenhum documento digitalizado foi enviado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>