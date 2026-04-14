<?php
require_once 'auth.php';
require_once 'bd.php'; // Integração com a conexão do Supabase

if ($_SESSION['usuario']['perfil'] === 'aluno') {
    header("Location: index.php");
    exit;
}

// --- LÓGICA CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['salvar'])) {
        $id = $_POST['id'] ?? null;
        $dados = [
            ':mat' => $_POST['matricula'],
            ':nr'  => $_POST['nr_aluno'],
            ':ng'  => mb_strtoupper($_POST['nome_guerra'], 'UTF-8'),
            ':nc'  => mb_strtoupper($_POST['nome_completo'], 'UTF-8'),
            ':tur' => $_POST['nome_turma'],
            ':pel' => $_POST['pelotao'],
            ':tel' => $_POST['telefone'],
            ':obs' => $_POST['obs'],
            ':per' => 'aluno'
        ];

        if (!empty($id)) {
            $sql = "UPDATE alunos SET matricula=:mat, nr_aluno=:nr, nome_guerra=:ng, nome_completo=:nc, 
                    nome_turma=:tur, pelotao=:pel, telefone=:tel, obs=:obs, perfil=:per WHERE id = :id";
            $dados[':id'] = $id;
        } else {
            $sql = "INSERT INTO alunos (matricula, nr_aluno, nome_guerra, nome_completo, nome_turma, pelotao, telefone, obs, perfil) 
                    VALUES (:mat, :nr, :ng, :nc, :tur, :pel, :tel, :obs, :per)";
        }
        $pdo->prepare($sql)->execute($dados);
    }
    
    if (isset($_POST['excluir'])) {
        $pdo->prepare("DELETE FROM alunos WHERE id = ?")->execute([$_POST['id']]);
    }
}

// --- FILTROS E LÓGICA DE EXIBIÇÃO ---
$lista_alunos = []; 
$turma_selecionada = !empty($_GET['f_turma']);

if ($turma_selecionada) {
    $query_where = ["perfil = 'aluno'"];
    $params = [];

    if (!empty($_GET['f_busca'])) {
        // ILIKE é ideal para PostgreSQL para buscas case-insensitive
        $query_where[] = "(nome_guerra ILIKE ? OR nome_completo ILIKE ? OR matricula ILIKE ?)";
        $busca = "%" . $_GET['f_busca'] . "%";
        array_push($params, $busca, $busca, $busca);
    }
    
    $query_where[] = "nome_turma = ?";
    $params[] = $_GET['f_turma'];

    if (!empty($_GET['f_pelotao'])) {
        $query_where[] = "pelotao = ?";
        $params[] = (int)$_GET['f_pelotao'];
    }

    $sql_final = "SELECT * FROM alunos WHERE " . implode(" AND ", $query_where) . " ORDER BY nome_turma ASC, nr_aluno ASC";
    $stmt = $pdo->prepare($sql_final);
    $stmt->execute($params);
    $lista_alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Carrega turmas do Supabase
$lista_turmas = $pdo->query("SELECT DISTINCT nome_turma FROM turmas ORDER BY nome_turma")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SGP - Gestão de Alunos</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .sidebar-label { font-size: 0.7rem; font-weight: bold; color: #1a3a63; }
        .card-header-pmse { background-color: #1a3a63; color: white; border-radius: 0.375rem 0.375rem 0 0; }
        .no-print { @media print { display: none; } }
    </style>
</head>
<body class="bg-light">

    <?php include 'header.php'; ?>

    <div class="container-fluid my-2">
        <?php include 'navbar.php'; ?>

        <div class="row g-3">
            <div class="col-lg-3 sidebar-column">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-dark text-white py-2 fw-bold small">FILTRAR REGISTROS</div>
                    <div class="card-body p-3">
                        <form method="GET">
                            <div class="mb-2">
                                <label class="sidebar-label">PESQUISAR</label>
                                <input type="text" name="f_busca" class="form-control form-control-sm" placeholder="Nome/Matrícula" value="<?= htmlspecialchars($_GET['f_busca'] ?? '') ?>">
                            </div>
                            <div class="mb-2">
                                <label class="sidebar-label">TURMA</label>
                                <select name="f_turma" class="form-select form-select-sm" required>
                                    <option value="">Selecione uma Turma</option>
                                    <?php foreach ($lista_turmas as $t): ?>
                                        <option value="<?= $t['nome_turma'] ?>" <?= (isset($_GET['f_turma']) && $_GET['f_turma'] == $t['nome_turma']) ? 'selected' : '' ?>><?= $t['nome_turma'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="sidebar-label">PELOTÃO</label>
                                <input type="number" name="f_pelotao" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['f_pelotao'] ?? '') ?>">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">APLICAR FILTRO</button>
                            <a href="alunos.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">LIMPAR</a>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-9 main-column">
                <div class="card shadow-sm border-0">
                    <div class="card-header-pmse p-2 d-flex justify-content-between align-items-center rounded">
                        <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Relação de Alunos</h5>
                        <button class="btn btn-light btn-sm fw-bold no-print" data-bs-toggle="modal" data-bs-target="#modalAluno" onclick="prepararNovo()">
                            <i class="bi bi-plus-lg"></i> CADASTRAR ALUNO
                        </button>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive rounded">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr class="table-dark">
                                        <th class="text-center" style="width: 50px;">Nº</th>
                                        <th>NOME DE GUERRA</th>
                                        <th>MATRÍCULA</th>
                                        <th>TURMA (PEL)</th>
                                        <th class="text-center no-print" style="width: 100px;">AÇÕES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($turma_selecionada && count($lista_alunos) > 0): ?>
                                        <?php foreach ($lista_alunos as $aluno): ?>
                                        <tr>
                                            <td class="text-center fw-bold text-primary"><?= str_pad($aluno['nr_aluno'], 3, '0', STR_PAD_LEFT) ?></td>
                                            <td class="text-uppercase fw-bold"><?= htmlspecialchars($aluno['nome_guerra']) ?></td>
                                            <td><?= htmlspecialchars($aluno['matricula']) ?></td>
                                            <td><?= htmlspecialchars($aluno['nome_turma']) ?> (<?= $aluno['pelotao'] ?>º PEL)</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary no-print" onclick='editarAluno(<?= json_encode($aluno) ?>)' title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Deseja excluir este registro?')">
                                                    <input type="hidden" name="id" value="<?= $aluno['id'] ?>">
                                                    <button type="submit" name="excluir" class="btn btn-sm btn-outline-danger no-print" title="Excluir">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php elseif (!$turma_selecionada): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted small"><i class="bi bi-info-circle me-1"></i> Por favor, selecione uma <b>Turma</b> no menu lateral para visualizar os alunos.</td></tr>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted small">Nenhum aluno encontrado para os filtros selecionados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAluno" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" class="modal-content border-top border-4 border-primary shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">FICHA DO ALUNO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <input type="hidden" name="id" id="form_id">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="fw-bold small">MATRÍCULA</label>
                            <input type="text" name="matricula" id="form_mat" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="fw-bold small">Nº</label>
                            <input type="number" name="nr_aluno" id="form_nr" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold small">GUERRA</label>
                            <input type="text" name="nome_guerra" id="form_ng" class="form-control text-uppercase" required>
                        </div>
                        <div class="col-md-12">
                            <label class="fw-bold small">NOME COMPLETO</label>
                            <input type="text" name="nome_completo" id="form_nc" class="form-control text-uppercase" required>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small">TURMA</label>
                            <select name="nome_turma" id="form_tur" class="form-select">
                                <?php foreach($lista_turmas as $t): ?>
                                    <option value="<?= $t['nome_turma'] ?>"><?= $t['nome_turma'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="fw-bold small">PELOTÃO</label>
                            <input type="number" name="pelotao" id="form_pel" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold small">CONTATO</label>
                            <input type="text" name="telefone" id="form_tel" class="form-control" placeholder="(00) 00000-0000">
                        </div>
                        <div class="col-md-12">
                            <label class="fw-bold small">OBSERVAÇÕES</label>
                            <textarea name="obs" id="form_obs" class="form-control" rows="2" placeholder="Informações adicionais..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="salvar" class="btn btn-primary px-5 fw-bold">SALVAR REGISTRO</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarAluno(aluno) {
            document.getElementById('form_id').value = aluno.id;
            document.getElementById('form_mat').value = aluno.matricula;
            document.getElementById('form_nr').value = aluno.nr_aluno;
            document.getElementById('form_ng').value = aluno.nome_guerra;
            document.getElementById('form_nc').value = aluno.nome_completo;
            document.getElementById('form_tur').value = aluno.nome_turma;
            document.getElementById('form_pel').value = aluno.pelotao;
            document.getElementById('form_tel').value = aluno.telefone;
            document.getElementById('form_obs').value = aluno.obs || "";
            new bootstrap.Modal(document.getElementById('modalAluno')).show();
        }
        function prepararNovo() {
            document.getElementById('form_id').value = "";
            document.querySelectorAll('#modalAluno input:not([type=hidden]), #modalAluno textarea').forEach(i => i.value = "");
            document.getElementById('form_pel').value = "1";
        }
    </script>
</body>
</html>