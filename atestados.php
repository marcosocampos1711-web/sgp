<?php
require_once 'auth.php';
require_once 'bd.php'; // Usa a conexão PDO configurada para o Supabase

// 1. SEGURANÇA
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['perfil'], ['admin', 'usuario'])) {
    header("Location: index.php");
    exit;
}

// 2. API DE BUSCA DINÂMICA
if (isset($_GET['busca_aluno'])) {
    $termo = $_GET['busca_aluno'];

    // Busca exata ou parcial para validar a existência do aluno
    $st = $pdo->prepare("SELECT matricula, nome_completo, nome_guerra, nr_aluno, pelotao, perfil FROM alunos 
                     WHERE matricula ILIKE ? OR nome_completo ILIKE ? OR nome_guerra ILIKE ? LIMIT 1");

    $st->execute(["%$termo%", "%$termo%", "%$termo%"]);
    $res = $st->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    if ($res) {
        $perfil = strtolower($res['perfil'] ?? '');
        if (in_array($perfil, ['admin', 'usuario'])) {
            echo json_encode(['valido' => false, 'nome' => 'ACESSO NEGADO: PERFIL ADM']);
        } else {
            echo json_encode([
                'valido' => true, 
                'nome' => mb_strtoupper($res['nome_completo']),
                'matricula' => $res['matricula'] // Retorna a matrícula original do banco
            ]);
        }
    } else {
        echo json_encode(['valido' => false, 'nome' => 'ALUNO NÃO ENCONTRADO']);
    }
    exit;
}

// 3. EXCLUSÃO
if (isset($_GET['excluir'])) {
    $id_excluir = (int)$_GET['excluir'];
    $pdo->prepare("DELETE FROM atestados WHERE id = ?")->execute([$id_excluir]);
    header("Location: atestados.php?msg=deleted");
    exit;
}

// 4. SALVAMENTO (CREATE / UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_salvar'])) {
    $id_edicao = $_POST['id_edicao'];
    // IMPORTANTE: Usamos a matrícula vinda do campo oculto (preenchido pela busca) 
    // para garantir que bata com a FK do banco de dados.
    $matricula = $_POST['matricula_real']; 
    $motivo = $_POST['motivo'];
    $dias = (int)$_POST['dias_afastamento'];
    $data_ini = $_POST['data_inicio'];
    $homologado = $_POST['homologado'];
    
    // Cálculo da data fim
    $data_fim = date('Y-m-d', strtotime($data_ini . " + " . ($dias - 1) . " days"));

    $arquivos = [];
    if (!empty($_FILES['documentos']['name'][0])) {
        $dir = "uploads/atestados/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        foreach ($_FILES['documentos']['tmp_name'] as $k => $tmp) {
            if ($_FILES['documentos']['error'][$k] === 0) {
                $ext = pathinfo($_FILES['documentos']['name'][$k], INFO_EXTENSION);
                $nome = time() . "_" . uniqid() . "." . $ext;
                if (move_uploaded_file($tmp, $dir . $nome)) $arquivos[] = $nome;
            }
        }
    }

    try {
        if (!empty($id_edicao)) {
            if (!empty($arquivos)) {
                $sql = "UPDATE atestados SET matricula=?, motivo_atestado=?, homologado=?, dias_afastamento=?, data_inicio=?, data_fim=?, imagens=? WHERE id=?";
                $params = [$matricula, $motivo, $homologado, $dias, $data_ini, $data_fim, json_encode($arquivos), $id_edicao];
            } else {
                $sql = "UPDATE atestados SET matricula=?, motivo_atestado=?, homologado=?, dias_afastamento=?, data_inicio=?, data_fim=? WHERE id=?";
                $params = [$matricula, $motivo, $homologado, $dias, $data_ini, $data_fim, $id_edicao];
            }
        } else {
            $sql = "INSERT INTO atestados (matricula, motivo_atestado, homologado, dias_afastamento, data_inicio, data_fim, imagens) VALUES (?,?,?,?,?,?,?)";
            $params = [$matricula, $motivo, $homologado, $dias, $data_ini, $data_fim, json_encode($arquivos)];
        }
        
        $pdo->prepare($sql)->execute($params);
        header("Location: atestados.php?msg=ok");
    } catch (PDOException $e) {
        // Caso ainda ocorra erro de FK, exibe de forma amigável
        die("Erro ao salvar: Verifique se a matrícula existe no cadastro de alunos. Detalhes: " . $e->getMessage());
    }
    exit;
}

// 5. FILTROS E LISTAGEM
$where = ["1=1"];
$p = [];

if (!empty($_GET['f_nome'])) { 
    $where[] = "(al.nome_completo ILIKE ? OR al.nome_guerra ILIKE ?)"; 
    $p[] = "%".$_GET['f_nome']."%"; 
    $p[] = "%".$_GET['f_nome']."%"; 
}
if (!empty($_GET['f_mat'])) { 
    $where[] = "a.matricula LIKE ?"; 
    $p[] = "%".preg_replace('/\D/','',$_GET['f_mat'])."%"; 
}
if (!empty($_GET['f_status'])) { 
    $where[] = "a.homologado = ?"; 
    $p[] = $_GET['f_status']; 
}

if (!empty($_GET['f_data_ini'])) { 
    $where[] = "a.data_inicio >= ?"; 
    $p[] = $_GET['f_data_ini']; 
}
if (!empty($_GET['f_data_fim'])) { 
    $where[] = "a.data_inicio <= ?"; 
    $p[] = $_GET['f_data_fim']; 
}

$sql_lista = "SELECT a.*, al.nome_completo, al.nome_guerra, al.nr_aluno, al.pelotao 
              FROM atestados a 
              LEFT JOIN alunos al ON a.matricula = al.matricula
              WHERE " . implode(" AND ", $where) . " 
              ORDER BY a.id DESC";

$lista = $pdo->prepare($sql_lista);
$lista->execute($p);
$atestados = $lista->fetchAll(PDO::FETCH_ASSOC);

function formatarMatricula($val) {
    $val = preg_replace('/\D/', '', $val);
    if (strlen($val) <= 10) return $val;
    return substr($val, 0, 10) . '-' . substr($val, 10);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP - Gestão de Atestados</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --pm-azul: #1a3a63; --sidebar-w: 280px; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .wrapper { display: flex; }
        #sidebar { width: var(--sidebar-w); background: white; border-right: 1px solid #ddd; min-height: 100vh; flex-shrink: 0; }
        #content { flex-grow: 1; padding: 25px; }
        .table-custom thead { background: var(--pm-azul); color: white; }
        .preview-box { background: #eef2f7; border: 1px dashed #abc; border-radius: 8px; }
        @media print { .no-print { display: none !important; } }
        .card-header-pmse { background-color: #1a3a63; color: white; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>
<div class="container-fluid my-2 no-print">
    <?php include 'navbar.php'; ?>        
</div>

<div class="wrapper">
    <nav id="sidebar" class="p-4 no-print">
        <h6 class="fw-bold mb-3 text-primary text-uppercase small"><i class="bi bi-filter"></i> Filtros Avançados</h6>
        <form method="GET" class="row g-3">
            <div class="col-12">
                <label class="small fw-bold">Nome do Aluno</label>
                <input type="text" name="f_nome" class="form-control form-control-sm" value="<?= $_GET['f_nome'] ?? '' ?>">
            </div>
            <div class="col-12">
                <label class="small fw-bold">Matrícula</label>
                <input type="text" name="f_mat" class="form-control form-control-sm" value="<?= $_GET['f_mat'] ?? '' ?>">
            </div>
            <div class="col-6">
                <label class="small fw-bold">Data Início</label>
                <input type="date" name="f_data_ini" class="form-control form-control-sm" value="<?= $_GET['f_data_ini'] ?? '' ?>">
            </div>
            <div class="col-6">
                <label class="small fw-bold">Data Fim</label>
                <input type="date" name="f_data_fim" class="form-control form-control-sm" value="<?= $_GET['f_data_fim'] ?? '' ?>">
            </div>
            <div class="col-12">
                <label class="small fw-bold">Homologado</label>
                <select name="f_status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="Sim" <?= (@$_GET['f_status']=='Sim')?'selected':'' ?>>Sim</option>
                    <option value="Não" <?= (@$_GET['f_status']=='Não')?'selected':'' ?>>Não</option>
                </select>
            </div>
            <div class="col-12 pt-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">APLICAR FILTROS</button>
                <a href="atestados.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">LIMPAR</a>
            </div>
        </form>
    </nav>

    <main id="content">
        <div class="d-flex justify-content-between align-items-center mb-1 rounded card-header-pmse p-2">
            <h5 class="mb-0"><i class="bi bi-file-earmark-medical me-2"></i>Atestados Médicos</h5>
            <button class="btn btn-success fw-bold shadow-sm no-print" onclick="novoAtestado()"><i class="bi bi-plus-lg"></i> NOVO REGISTRO</button>
        </div>

        <div class="card border-0 p-0 shadow-sm rounded-3">
            <div class="table-responsive rounded">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th class="ps-4">Aluno / Matrícula / Pelotão</th>
                            <th>Período</th>
                            <th class="text-center">Dias</th>
                            <th>Homologado</th>
                            <th class="text-center">Documentos</th>
                            <th class="text-center no-print">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($atestados)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum registro encontrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach($atestados as $a): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-uppercase text-danger" style="font-size: 0.85rem;">
                                    AL <?= $a['nr_aluno'] ?? 'S/N' ?> <?= $a['nome_guerra'] ?? 'NÃO ENCONTRADO' ?>
                                </div>
                                <div class="text-muted small">
                                    <code><?= formatarMatricula($a['matricula']) ?></code> - <?= $a['pelotao'] ?? '?' ?>º PEL
                                </div>
                            </td>
                            <td class="small fw-bold">
                                <?= date('d/m/Y', strtotime($a['data_inicio'])) ?> <i class="bi bi-arrow-right text-muted"></i> <?= date('d/m/Y', strtotime($a['data_fim'])) ?>
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark border"><?= $a['dias_afastamento'] ?></span></td>
                            <td>
                                <span class="badge <?= $a['homologado']=='Sim'?'bg-success':'bg-danger' ?> px-3">
                                    <?= $a['homologado'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php 
                                $docs = json_decode($a['imagens'] ?? '[]'); 
                                if(!empty($docs) && is_array($docs)): 
                                ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-paperclip"></i></button>
                                        <ul class="dropdown-menu shadow">
                                            <?php foreach($docs as $idx => $d): ?>
                                                <li><a class="dropdown-item small" href="uploads/atestados/<?= $d ?>" target="_blank">Ver Anexo <?= $idx+1 ?></a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: echo "-"; endif; ?>
                            </td>
                            <td class="text-center no-print">
                                <button class="btn btn-sm btn-outline-primary rounded-pill me-1" onclick='editar(<?= json_encode($a) ?>)' title="Editar"><i class="bi bi-pencil"></i></button>
                                <a href="?excluir=<?= $a['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Tem certeza que deseja excluir este registro?')" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="modalAtestado" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formAtestado" class="modal-content border-0 shadow-lg" enctype="multipart/form-data">
            <input type="hidden" name="id_edicao" id="id_edicao">
            <input type="hidden" name="matricula_real" id="matricula_real">
            
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold" id="modalTitulo">Lançar Atestado</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold">BUSCAR ALUNO (MATRÍCULA OU NOME)</label>
                    <input type="text" id="campo_busca" class="form-control fw-bold border-2" required placeholder="Digite a matrícula ou nome...">
                    <div id="status_aluno" class="mt-2 small p-2 rounded d-none"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">MOTIVO / CID</label>
                    <input type="text" name="motivo" id="motivo" class="form-control" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-7">
                        <label class="form-label small fw-bold text-uppercase">Data Início</label>
                        <input type="date" name="data_inicio" id="data_ini" class="form-control" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label small fw-bold text-uppercase">Dias</label>
                        <input type="number" name="dias_afastamento" id="dias" class="form-control fw-bold" min="1" required>
                    </div>
                </div>

                <div class="preview-box p-3 mb-3 text-center">
                    <small class="fw-bold text-muted d-block" style="font-size: 0.9rem;">DATA FIM</small>
                    <span id="label_fim" class="fs-4 fw-bold text-primary">--/--/----</span>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase">Documentos (PDF/IMG)</label>
                    <input type="file" name="documentos[]" class="form-control" multiple>
                </div>

                <div class="mb-4 text-center">
                    <label class="form-label small fw-bold d-block text-uppercase mb-2">Homologado?</label>
                    <div class="btn-group w-100">
                        <input type="radio" class="btn-check" name="homologado" id="h_sim" value="Sim">
                        <label class="btn btn-outline-success" for="h_sim">Sim</label>
                        <input type="radio" class="btn-check" name="homologado" id="h_nao" value="Não" checked>
                        <label class="btn btn-outline-danger" for="h_nao">Não</label>
                    </div>
                </div>

                <button type="submit" name="btn_salvar" id="btnSalvar" class="btn btn-primary w-100 py-2 fw-bold" disabled>GRAVAR DADOS</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('modalAtestado'));
const btnSalvar = document.getElementById('btnSalvar');
const inputMatriculaReal = document.getElementById('matricula_real');

// Busca dinâmica de aluno
document.getElementById('campo_busca').addEventListener('input', function(e) {
    let v = e.target.value;
    const status = document.getElementById('status_aluno');

    if (v.length > 3) {
        fetch(`atestados.php?busca_aluno=${encodeURIComponent(v)}`)
            .then(r => r.json())
            .then(data => {
                status.classList.remove('d-none');
                status.innerText = data.nome;
                if (data.valido) {
                    status.className = 'mt-2 small p-2 rounded bg-success-subtle text-success fw-bold border border-success';
                    btnSalvar.disabled = false;
                    // Salva a matrícula exata do banco no campo oculto
                    inputMatriculaReal.value = data.matricula;
                } else {
                    status.className = 'mt-2 small p-2 rounded bg-danger-subtle text-danger fw-bold border border-danger';
                    btnSalvar.disabled = true;
                    inputMatriculaReal.value = '';
                }
            });
    }
});

function formatarMatriculaJS(v) {
    if(!v) return "";
    v = v.replace(/\D/g, "");
    if (v.length > 10) v = v.substring(0, 10) + "-" + v.substring(10, 12);
    return v;
}

// Cálculo dinâmico da data final
function calc() {
    const ini = document.getElementById('data_ini').value;
    const dias = parseInt(document.getElementById('dias').value);
    if (ini && !isNaN(dias)) {
        let d = new Date(ini);
        d.setUTCDate(d.getUTCDate() + (dias - 1));
        document.getElementById('label_fim').innerText = d.toLocaleDateString('pt-BR', {timeZone: 'UTC'});
    }
}
document.getElementById('data_ini').addEventListener('change', calc);
document.getElementById('dias').addEventListener('input', calc);

function novoAtestado() {
    document.getElementById('id_edicao').value = '';
    inputMatriculaReal.value = '';
    document.getElementById('formAtestado').reset();
    document.getElementById('modalTitulo').innerText = 'Lançar Novo Atestado';
    document.getElementById('status_aluno').classList.add('d-none');
    document.getElementById('label_fim').innerText = '--/--/----';
    btnSalvar.disabled = true;
    modal.show();
}

function editar(data) {
    document.getElementById('id_edicao').value = data.id;
    document.getElementById('modalTitulo').innerText = 'Editar Atestado';
    
    // Na edição, preenchemos o campo de busca e o oculto com a matrícula existente
    document.getElementById('campo_busca').value = formatarMatriculaJS(data.matricula);
    inputMatriculaReal.value = data.matricula;
    
    document.getElementById('motivo').value = data.motivo_atestado;
    document.getElementById('data_ini').value = data.data_inicio;
    document.getElementById('dias').value = data.dias_afastamento;
    document.getElementById('h_sim').checked = (data.homologado === 'Sim');
    document.getElementById('h_nao').checked = (data.homologado === 'Não');
    
    calc();

    const status = document.getElementById('status_aluno');
    status.classList.remove('d-none');
    status.innerText = (data.nome_guerra || data.nome_completo || "ALUNO VINCULADO");
    status.className = 'mt-2 small p-2 rounded bg-primary-subtle text-primary fw-bold border border-primary';
    
    btnSalvar.disabled = false;
    modal.show();
}
</script>
</body>
</html>