<?php
require 'auth.php';
require 'bd.php';

if ($_SESSION['usuario']['perfil'] !== 'admin') { header("Location: relatorio.php"); exit; }

// Query ajustada para sintaxe PostgreSQL (SUM com CASE WHEN)
$resumo = $pdo->query("SELECT pelotao, 
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('AUTORIZADO', 'FINALIZADO') THEN 1 ELSE 0 END) as aprovados,
    SUM(CASE WHEN status = 'NEGADO' THEN 1 ELSE 0 END) as negados
    FROM passes GROUP BY pelotao ORDER BY pelotao ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>SGP - Ranking por Pelotão</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
</head>
<body class="bg-light p-4">
    <div class="container">
        <div class="d-flex justify-content-between mb-4">
            <h3><i class="bi bi-people-fill text-primary"></i> Movimentação por Pelotão</h3>
            <a href="relatorio.php" class="btn btn-dark">Voltar ao Painel</a>
        </div>
        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Pelotão</th>
                        <th class="text-center">Total de Pedidos</th>
                        <th class="text-center text-info">Aprovados/Finalizados</th>
                        <th class="text-center text-danger">Negativas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($resumo as $r): ?>
                    <tr>
                        <td class="fw-bold fs-5"><?= $r['pelotao'] ?></td>
                        <td class="text-center"><?= (int)$r['total'] ?></td>
                        <td class="text-center text-success"><?= (int)$r['aprovados'] ?></td>
                        <td class="text-center text-danger"><?= (int)$r['negados'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>