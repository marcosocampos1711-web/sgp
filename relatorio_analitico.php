<?php
require 'auth.php';
require 'bd.php';

if ($_SESSION['usuario']['perfil'] !== 'admin') { header("Location: relatorio.php"); exit; }

date_default_timezone_set('America/Maceio');

$inicio = $_GET['data_inicio'] ?? date('Y-m-01'); 
$fim = $_GET['data_fim'] ?? date('Y-m-d');
$turma = $_GET['turma'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT * FROM passes WHERE data_solicitacao BETWEEN ? AND ?";
$params = [$inicio . " 00:00:00", $fim . " 23:59:59"];

if ($turma) { $sql .= " AND nome_turma = ?"; $params[] = $turma; }
if ($status) { $sql .= " AND status = ?"; $params[] = $status; }

$sql .= " ORDER BY data_solicitacao ASC, nome_guerra ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$turmas = $pdo->query("SELECT DISTINCT nome_turma FROM passes ORDER BY nome_turma")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SGP - Relatório Analítico Detalhado</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --pm-blue: #1a3c6d; --pm-gold: #c5a059; }
        body { background-color: #f8f9fa; font-size: 0.9rem; }
        .header-print { background: var(--pm-blue); color: white; border-bottom: 4px solid var(--pm-gold); }
        @media print { 
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            body { background: white; }
        }
        .status-badge { font-size: 0.75rem; padding: 4px 8px; }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="card shadow-sm mb-4 no-print">
        <div class="card-header header-print p-3">
            <h5 class="mb-0"><i class="bi bi-search me-2"></i>Filtros do Relatório Analítico</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">INÍCIO</label>
                    <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= $inicio ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">FIM</label>
                    <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= $fim ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">TURMA</label>
                    <select name="turma" class="form-select form-select-sm">
                        <option value="">Todas as Turmas</option>
                        <?php foreach($turmas as $t): ?>
                            <option value="<?= $t ?>" <?= $turma == $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">STATUS</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="PENDENTE" <?= $status == 'PENDENTE' ? 'selected' : '' ?>>PENDENTE</option>
                        <option value="AUTORIZADO" <?= $status == 'AUTORIZADO' ? 'selected' : '' ?>>AUTORIZADO</option>
                        <option value="NEGADO" <?= $status == 'NEGADO' ? 'selected' : '' ?>>NEGADO</option>
                        <option value="FINALIZADO" <?= $status == 'FINALIZADO' ? 'selected' : '' ?>>FINALIZADO</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filtrar</button>
                    <button type="button" onclick="window.print()" class="btn btn-dark btn-sm"><i class="bi bi-printer"></i></button>
                    <a href="relatorio.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white p-3 rounded shadow-sm border">
        <div class="text-center mb-4">
            <h4 class="mb-0">RELATÓRIO ANALÍTICO DE PASSES ESCOLARES</h4>
            <p class="text-muted small">Período: <?= date('d/m/Y', strtotime($inicio)) ?> a <?= date('d/m/Y', strtotime($fim)) ?></p>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light text-center small">
                    <tr>
                        <th>Passe/Solicitação</th>
                        <th>Aluno</th>
                        <th>Turma/Pel</th>
                        <th>Destino</th>
                        <th>Status</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (!$registros): ?>
                        <tr><td colspan="6" class="text-center py-4">Nenhum registro detalhado encontrado.</td></tr>
                    <?php else: foreach($registros as $r): 
                        $badge = match($r['status']) {
                            'PENDENTE' => 'bg-warning text-dark',
                            'AUTORIZADO' => 'bg-info text-white',
                            'NEGADO' => 'bg-danger text-white',
                            'FINALIZADO' => 'bg-success text-white',
                            default => 'bg-secondary'
                        };
                        
                        $idPasseFormatado = str_pad($r['id'], 5, '0', STR_PAD_LEFT);
                    ?>
                    <tr>
                        <td class="text-center">
                            <?= $idPasseFormatado ?> / <?= date('d/m/y H:i', strtotime($r['data_solicitacao'])) ?>
                        </td>
                        <td>
                            <div class="text-uppercase"><?= $r['nome_completo'] ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                <?= $r['matricula'] ?> - <span class="fw-bold text-dark"><?= $r['nome_guerra'] ?></span>
                            </div>
                        </td>
                        <td class="text-center"><?= $r['nome_turma']?> <span class="badge bg-light text-dark border"><?= $r['pelotao'] ?></span></td>
                        <td style="max-width: 200px;"><?= $r['destino'] ?></td>
                        <td class="text-center"><span class="badge status-badge <?= $badge ?>"><?= $r['status'] ?></span></td>
                        <td class="fst-italic text-muted" style="font-size: 0.75rem;">
                            <?= $r['fundamentacao'] ?: ($r['status'] == 'FINALIZADO' ? 'Retorno confirmado com comprovante.' : '-') ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3 small text-end text-muted">
            Relatório gerado em: <?= date('d/m/Y H:i:s') ?>
        </div>
    </div>
</div>

</body>
</html>