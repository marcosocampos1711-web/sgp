<?php
require 'auth.php'; // Proteção contra acesso não autorizado
require 'bd.php';   // Carrega a conexão $pdo do Supabase configurada em bd.php

if ($_SESSION['usuario']['perfil'] === 'admin' OR $_SESSION['usuario']['perfil'] === 'usuario'):

date_default_timezone_set('America/Maceio');

// Processamento da Edição dos Dados do Passe
if (isset($_POST['update_edit'])) {
    $id = $_POST['edit_id'];
    $motivo = $_POST['motivo'];
    $destino = $_POST['destino'];
    $data_saida = $_POST['data_saida'];
    $hora_saida = $_POST['hora_saida'];
    $data_retorno = $_POST['data_retorno'];
    $hora_retorno = $_POST['hora_retorno'];

    $sql_edit = "UPDATE passes SET motivo = ?, destino = ?, data_saida = ?, hora_saida = ?, data_retorno = ?, hora_retorno = ? WHERE id = ?";
    $stmt_edit = $pdo->prepare($sql_edit);
    $stmt_edit->execute([$motivo, $destino, $data_saida, $hora_saida, $data_retorno, $hora_retorno, $id]);
    exit;
}

// Processamento da atualização de status (AJAX)
if (isset($_POST['update_id'])) {
    $id = $_POST['update_id'];
    $matricula_admin = $_SESSION['usuario']['matricula'] ?? 'SISTEMA';
    $status = $_POST['status'];
    $fundamentacao = $_POST['fund'] ?? '';

    $stmt_check = $pdo->prepare("SELECT numero_passe FROM passes WHERE id = ?");
    $stmt_check->execute([$id]);
    $registro_atual = $stmt_check->fetch(PDO::FETCH_ASSOC);
    $numero_existente = $registro_atual['numero_passe'] ?? null;

    $update_sql = "UPDATE passes SET status = ?, fundamentacao = ?, autorizacao = ?";
    $params = [$status, $fundamentacao, $matricula_admin];

    if ($status === 'AUTORIZADO') {
        if (empty($numero_existente)) {
            // No PostgreSQL, garantimos que o retorno de MAX seja tratado como inteiro
            $stmt_seq = $pdo->query("SELECT COALESCE(MAX(numero_passe), 0) as max_num FROM passes");
            $res = $stmt_seq->fetch(PDO::FETCH_ASSOC);
            $novo_numero = $res['max_num'] + 1;
            $update_sql .= ", numero_passe = ?";
            $params[] = $novo_numero;
        }
    } else {
        $update_sql .= ", numero_passe = NULL";
    }

    $update_sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($update_sql);
    $stmt->execute($params);
    exit;
}

// Lógica de Filtros (Adaptada para PostgreSQL)
$where = ["1=1"];
$params = [];

if (empty($_REQUEST['status'])) {
    $where[] = "status != 'FINALIZADO'";
}

$filtros = [
    'matricula'     => 'matricula ILIKE ?', // ILIKE para PostgreSQL (ignore case)
    'nome_guerra'   => 'nome_guerra ILIKE ?',
    'nome_completo' => 'nome_completo ILIKE ?',
    'status'        => 'status = ?',
];

foreach ($filtros as $campo => $sql_part) {
    if (!empty($_REQUEST[$campo])) {
        $where[] = $sql_part;
        $params[] = ($campo === 'status') ? $_REQUEST[$campo] : "%" . $_REQUEST[$campo] . "%";
    }
}

if (!empty($_REQUEST['data_inicio']) && !empty($_REQUEST['data_fim'])) {
    $where[] = "data_solicitacao BETWEEN ? AND ?";
    $params[] = $_REQUEST['data_inicio'] . " 00:00:00";
    $params[] = $_REQUEST['data_fim'] . " 23:59:59";
}

$sql = "SELECT * FROM passes WHERE " . implode(" AND ", $where) . " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$passes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Resposta AJAX para a tabela
if (isset($_GET['ajax'])) {
    if($passes) {
        foreach($passes as $p) {
            
            if ($p['status'] === 'FINALIZADO') {
                $label_data = "FINALIZADO EM";
                $cor_data = "text-success";
                $data_exibicao = !empty($p['data_retorno_real']) ? date('d/m/Y H:i', strtotime($p['data_retorno_real'])) : "---";
            } else {
                $label_data = "EMISSÃO";
                $cor_data = "text-primary";
                $data_exibicao = date('d/m/Y H:i', strtotime($p['data_solicitacao']));
            }

            // Formatação do número do passe com 5 dígitos
            $id_formatado = !empty($p['numero_passe']) ? str_pad($p['numero_passe'], 5, '0', STR_PAD_LEFT) : "---";

            echo "<tr>
                    <td class='ps-4 fw-bold text-muted'>{$id_formatado}</td>
                    <td><span class='badge bg-light text-dark border'>{$p['matricula']}</span></td>
                    <td>
                        <div class='fw-bold text-uppercase'>{$p['nome_guerra']}</div>
                        <div class='small text-muted' style='font-size: 0.7rem;'>{$p['nome_completo']}</div>
                    </td>
                    <td>
                        <div class='fw-bold'>{$p['nome_turma']}</div>
                        <div class='small text-muted'>{$p['pelotao']}º PEL</div>
                    </td>
                    <td>
                        <div class='small fw-bold {$cor_data}' style='font-size: 0.7rem;'>{$label_data}</div>
                        <div class='small'>{$data_exibicao}</div>
                    </td>
                    <td><div class='text-truncate' style='max-width:150px;' title='{$p['destino']}'><i class='bi bi-geo-alt text-danger me-1'></i>{$p['destino']}</div></td>
                    <td>
                        <select onchange='salvarStatus({$p['id']}, this.value)' class='form-select form-select-sm shadow-sm' ".($p['status']=='FINALIZADO'?'disabled':'').">
                            <option value='PENDENTE' ".($p['status']=='PENDENTE'?'selected':'').">PENDENTE</option>
                            <option value='AUTORIZADO' ".($p['status']=='AUTORIZADO'?'selected':'').">AUTORIZADO</option>
                            <option value='NEGADO' ".($p['status']=='NEGADO'?'selected':'').">NEGADO</option>
                            <option value='FINALIZADO' ".($p['status']=='FINALIZADO'?'selected':'')." disabled>FINALIZADO</option>
                        </select>
                    </td>
                    <td class='text-center'>
                        <div class='btn-group shadow-sm'>";
                            
                            if ($p['status'] !== 'FINALIZADO') {
                                // Sanitização para JSON safe
                                $json_dados = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                                echo "<button onclick='abrirModalEditar({$json_dados})' class='btn btn-sm btn-outline-primary' title='Editar Dados'><i class='bi bi-pencil'></i></button>";
                                echo "<a href='analisar_passe.php?id={$p['id']}' class='btn btn-sm btn-outline-warning' title='Análise'> <i class='bi bi-search'></i></a>";
                            }
                            
                            echo "<a href='imprimir.php?id={$p['id']}' class='btn btn-sm btn-outline-secondary' title='Imprimir'><i class='bi bi-printer'></i></a>";
                            
                            if($p['status'] == 'AUTORIZADO') {
                                echo "<button onclick='abrirModalFinalizar({$p['id']})' class='btn btn-sm btn-outline-success' title='Finalizar Retorno'><i class='bi bi-check-lg'></i></button>";
                            }
                            
                            if(!empty($p['comprovante'])) {
                                echo "<a href='uploads/{$p['comprovante']}' target='_blank' class='btn btn-sm btn-outline-info text-white' title='Ver Comprovante'><i class='bi bi-file-earmark-text'></i></a>";
                            }
            echo "      </div>
                    </td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='8' class='text-center py-5 text-muted'><i class='bi bi-search me-2'></i>Nenhum registro encontrado.</td></tr>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP - Painel de Relatórios</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

<?php include 'header.php'; ?>
<div class="container-fluid my-2">
    <?php include 'navbar.php'; ?>

    <div class="loading-overlay" id="loadingOverlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 9999; align-items: center; justify-content: center;">
        <div class="spinner-border text-primary"></div>
    </div>

    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body p-4">
            <form id="filterForm" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">MATRÍCULA</label>
                    <input type="text" name="matricula" class="form-control" placeholder="Ex: 123456-7">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">NOME DE GUERRA</label>
                    <input type="text" name="nome_guerra" class="form-control" placeholder="Digite o nome...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">STATUS</label>
                    <select name="status" class="form-select">
                        <option value="">Todos (Ativos)</option>
                        <option value="PENDENTE">PENDENTE</option>
                        <option value="AUTORIZADO">AUTORIZADO</option>
                        <option value="NEGADO">NEGADO</option>
                        <option value="FINALIZADO">FINALIZADO</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">INÍCIO</label>
                    <input type="date" name="data_inicio" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">FIM</label>
                    <input type="date" name="data_fim" class="form-control">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" onclick="location.href='relatorio.php'" class="btn btn-outline-secondary w-100"><i class="bi bi-eraser"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 bg-white">
                <thead class="table-dark">
                    <tr class="small">
                        <th class="ps-4">PASSE</th>
                        <th>MATRÍCULA</th>
                        <th>ALUNO</th>
                        <th>TURMA/PEL</th>
                        <th>DATA/HORA</th>
                        <th>DESTINO</th>
                        <th style="width: 153px;">STATUS</th>
                        <th class="text-center" style="width: 130px;">Ações</th>
                    </tr>
                </thead>
                <tbody class="text-uppercase" id="tableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formEditarPasse" class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Dados do Passe</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <input type="hidden" name="update_edit" value="1">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">MOTIVO</label>
                        <textarea name="motivo" id="edit_motivo" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">DESTINO</label>
                        <input type="text" name="destino" id="edit_destino" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">DATA SAÍDA</label>
                        <input type="date" name="data_saida" id="edit_data_saida" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">HORA SAÍDA</label>
                        <input type="time" name="hora_saida" id="edit_hora_saida" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">DATA RETORNO</label>
                        <input type="date" name="data_retorno" id="edit_data_retorno" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">HORA RETORNO</label>
                        <input type="time" name="hora_retorno" id="edit_hora_retorno" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalFinalizar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="finalizar_passe.php" method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-cloud-upload me-2"></i>Finalizar Passe</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_passe" id="id_passe_finalizar">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase">Descrição do Retorno / Observações</label>
                    <textarea name="descricao_retorno" class="form-control" rows="3" placeholder="Descreva detalhes sobre o retorno..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase">Comprovante (Opcional)</label>
                    <input type="file" name="comprovante" class="form-control" accept=".pdf,image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success px-4">Confirmar e Finalizar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const form = document.getElementById('filterForm');
    const tableBody = document.getElementById('tableBody');
    const loadingOverlay = document.getElementById('loadingOverlay');
    let timer;

    function realizarBusca() {
        loadingOverlay.style.display = 'flex';
        const params = new URLSearchParams(new FormData(form));
        params.append('ajax', '1');
        fetch('relatorio.php?' + params.toString())
            .then(r => r.text())
            .then(html => {
                tableBody.innerHTML = html;
                loadingOverlay.style.display = 'none';
            })
            .catch(err => {
                console.error(err);
                loadingOverlay.style.display = 'none';
            });
    }

    function abrirModalEditar(dados) {
        document.getElementById('edit_id').value = dados.id;
        document.getElementById('edit_motivo').value = dados.motivo;
        document.getElementById('edit_destino').value = dados.destino;
        document.getElementById('edit_data_saida').value = dados.data_saida;
        document.getElementById('edit_hora_saida').value = dados.hora_saida;
        document.getElementById('edit_data_retorno').value = dados.data_retorno;
        document.getElementById('edit_hora_retorno').value = dados.hora_retorno;
        new bootstrap.Modal(document.getElementById('modalEditar')).show();
    }

    document.getElementById('formEditarPasse').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fetch('relatorio.php', { method: 'POST', body: fd })
            .then(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide();
                realizarBusca();
            });
    });

    function salvarStatus(id, status) {
        let fund = "";
        if (status === 'NEGADO') {
            fund = prompt("Informe a fundamentação da negativa:");
            if (!fund) { realizarBusca(); return; }
        }
        const fd = new FormData();
        fd.append('update_id', id);
        fd.append('status', status);
        fd.append('fund', fund);
        fetch('relatorio.php', { method: 'POST', body: fd }).then(() => realizarBusca());
    }

    function abrirModalFinalizar(id) {
        document.getElementById('id_passe_finalizar').value = id;
        new bootstrap.Modal(document.getElementById('modalFinalizar')).show();
    }

    form.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(realizarBusca, 300);
    });

    window.onload = realizarBusca;
</script>
</body>
</html>
<?php 
else: 
    header("Location: index.php"); 
endif; 
?>