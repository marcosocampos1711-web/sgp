<?php
require 'auth.php';
require 'bd.php'; // Conexão via Supabase/PostgreSQL

if ($_SESSION['usuario']['perfil'] !== 'admin') { header("Location: relatorio.php"); exit; }

$inicio = $_GET['data_inicio'] ?? date('Y-m-01'); 
$fim = $_GET['data_fim'] ?? date('Y-m-d');

// Ajuste na query para PostgreSQL
$stmt = $pdo->prepare("SELECT status, COUNT(*) as total FROM passes WHERE data_solicitacao BETWEEN ? AND ? GROUP BY status");
$stmt->execute([$inicio . " 00:00:00", $fim . " 23:59:59"]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_periodo = array_sum(array_column($stats, 'total'));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SGP - Estatísticas de Desempenho</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">PERÍODO INICIAL</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?= $inicio ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">PERÍODO FINAL</label>
                        <input type="date" name="data_fim" class="form-control" value="<?= $fim ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filtrar</button>
                    </div>
                    <div class="col-md-2">
                        <a href="relatorio.php" class="btn btn-outline-secondary w-100">Voltar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-12">
                <h4 class="mb-3 text-muted">Resultados do Período: <span class="text-primary"><?= $total_periodo ?> solicitações</span></h4>
            </div>
            <?php 
            $status_map = [
                'PENDENTE' => ['color' => 'warning', 'icon' => 'clock-history'],
                'AUTORIZADO' => ['color' => 'info', 'icon' => 'check-circle'],
                'NEGADO' => ['color' => 'danger', 'icon' => 'x-octagon'],
                'FINALIZADO' => ['color' => 'success', 'icon' => 'flag-fill']
            ];
            foreach($stats as $s): 
                $cfg = $status_map[$s['status']] ?? ['color' => 'secondary', 'icon' => 'question'];
            ?>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <i class="bi bi-<?= $cfg['icon'] ?> text-<?= $cfg['color'] ?> fs-1"></i>
                        <h2 class="mt-2 fw-bold"><?= $s['total'] ?></h2>
                        <span class="badge bg-<?= $cfg['color'] ?> text-uppercase"><?= $s['status'] ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>