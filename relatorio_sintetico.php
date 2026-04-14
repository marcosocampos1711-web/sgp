<?php
require 'auth.php'; 
require 'bd.php';

if ($_SESSION['usuario']['perfil'] !== 'admin') { header("Location: relatorio.php"); exit; }

date_default_timezone_set('America/Maceio');

$inicio = $_GET['data_inicio'] ?? date('Y-m-01'); 
$fim = $_GET['data_fim'] ?? date('Y-m-d');

$sql = "SELECT nome_turma, status, COUNT(*) as total 
        FROM passes 
        WHERE data_solicitacao BETWEEN ? AND ? 
        GROUP BY nome_turma, status 
        ORDER BY nome_turma ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$inicio . " 00:00:00", $fim . " 23:59:59"]);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$relatorio = [];
foreach ($dados as $linha) {
    $relatorio[$linha['nome_turma']][$linha['status']] = $linha['total'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SGP - Relatório Sintético</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --pm-blue: #1a3c6d; --pm-gold: #c5a059; }
        body { background-color: #f4f7f6; }
        .card-header-pmse { background: var(--pm-blue); color: white; border-bottom: 4px solid var(--pm-gold); }
        .table-sintetica thead { background: #2d3436; color: white; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header-pmse p-3 rounded-top text-center">
            <h5 class="mb-0 text-uppercase">Relatório Sintético de Passes Escolares</h5>
        </div>
        <div class="card-body bg-white p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">DATA INICIAL</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?= $inicio ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">DATA FINAL</label>
                    <input type="date" name="data_fim" class="form-control" value="<?= $fim ?>">
                </div>
                <div class="col-md-2 no-print">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="bi bi-search me-1"></i> FILTRAR
                    </button>
                </div>
                <div class="col-md-2 no-print">
                    <a href="relatorio.php" class="btn btn-outline-secondary w-100">VOLTAR</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive shadow-sm rounded-3">
        <table class="table table-bordered table-hover bg-white mb-0 table-sintetica">
            <thead>
                <tr class="text-center align-middle">
                    <th rowspan="2" class="text-start ps-4">TURMA / CURSO</th>
                    <th colspan="4">STATUS DOS PASSES</th>
                    <th rowspan="2" class="bg-light">TOTAL</th>
                </tr>
                <tr class="text-center small">
                    <th class="text-warning">PENDENTES</th>
                    <th class="text-info">AUTORIZADOS</th>
                    <th class="text-danger">NEGADOS</th>
                    <th class="text-success">FINALIZADOS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($relatorio)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum dado encontrado para este período.</td></tr>
                <?php else: 
                    $geral_pendente = $geral_autorizado = $geral_negado = $geral_finalizado = 0;
                    foreach ($relatorio as $turma => $status_counts): 
                        $t_pendente = $status_counts['PENDENTE'] ?? 0;
                        $t_autorizado = $status_counts['AUTORIZADO'] ?? 0;
                        $t_negado = $status_counts['NEGADO'] ?? 0;
                        $t_finalizado = $status_counts['FINALIZADO'] ?? 0;
                        $t_soma = $t_pendente + $t_autorizado + $t_negado + $t_finalizado;
                        
                        $geral_pendente += $t_pendente; $geral_autorizado += $t_autorizado;
                        $geral_negado += $t_negado; $geral_finalizado += $t_finalizado;
                ?>
                    <tr class="text-center align-middle">
                        <td class="text-start ps-4 fw-bold"><?= $turma ?></td>
                        <td><?= $t_pendente ?></td>
                        <td><?= $t_autorizado ?></td>
                        <td><?= $t_negado ?></td>
                        <td><?= $t_finalizado ?></td>
                        <td class="fw-bold bg-light"><?= $t_soma ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-secondary fw-bold text-center">
                <tr>
                    <td class="text-end pe-4">TOTAL GERAL</td>
                    <td><?= $geral_pendente ?></td>
                    <td><?= $geral_autorizado ?></td>
                    <td><?= $geral_negado ?></td>
                    <td><?= $geral_finalizado ?></td>
                    <td class="bg-dark text-white"><?= ($geral_pendente + $geral_autorizado + $geral_negado + $geral_finalizado) ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
    <div class="mt-3 text-end no-print">
        <button onclick="window.print()" class="btn btn-sm btn-light border shadow-sm">
            <i class="bi bi-printer me-1"></i> Imprimir Relatório
        </button>
    </div>
</div>
</body>
</html>