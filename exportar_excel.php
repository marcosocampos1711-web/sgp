<?php
require 'auth.php';
require 'bd.php';

if ($_SESSION['usuario']['perfil'] !== 'admin') { header("Location: relatorio.php"); exit; }

$filename = "Relatorio_Passes_" . date('Y-m-d') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$query = $pdo->query("SELECT id, matricula, nome_guerra, nome_completo, nome_turma, pelotao, destino, status, data_solicitacao FROM passes ORDER BY id DESC");

echo pack("CCC", 0xef, 0xbb, 0xbf); 
echo "<table>";
echo "<tr>";
echo "<th>ID</th><th>MATRÍCULA</th><th>NOME DE GUERRA</th><th>NOME COMPLETO</th><th>TURMA</th><th>PELOTÃO</th><th>DESTINO</th><th>STATUS</th><th>DATA SOLICITAÇÃO</th>";
echo "</tr>";

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td>" . htmlspecialchars($value) . "</td>";
    }
    echo "</tr>";
}
echo "</table>";
exit;