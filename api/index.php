<?php
/**
 * SGP - Sistema Gerenciador de Passe Escolar
 * Versão adaptada para Supabase (PostgreSQL)
 * Developer © 2026 - 1º TENENTE QOAPM MARCOS ANTÔNIO
 */

// 1. Configurações de fuso horário
date_default_timezone_set('America/Maceio');

// 2. Gerenciamento de Sessão Seguro
// Esta verificação impede o erro "session already active" ao carregar o auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Conexão com o Banco de Dados
require_once 'bd.php'; 

try {
    // 4. Autenticação (Verifica se está logado via auth.php)
    require_once 'auth.php';
    
    // Redirecionamento de segurança caso a sessão não exista
    if (!isset($_SESSION['usuario'])) {
        header("Location: login.php");
        exit;
    }

    $user = $_SESSION['usuario'];
    $is_aluno = (isset($user['perfil']) && $user['perfil'] === 'aluno');
    $dados_aluno = $is_aluno ? $user : null;

    // 5. API Interna de busca (AJAX) - Isolada para não sujar o JSON com HTML
    if (isset($_GET['buscar_matricula'])) {
        header('Content-Type: application/json');
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE matricula = ? LIMIT 1");
        $stmt->execute([$_GET['buscar_matricula']]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($resultado ? $resultado : null);
        exit;
    }

    // 6. Lógica de finalização (Logout)
    $show_modal_finaliza = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finaliza'])) {
        session_destroy();
        $show_modal_finaliza = true;
    }

    // 7. Listar turmas para o formulário
    $lista_turmas = $pdo->query("SELECT * FROM turmas ORDER BY ano DESC, nome_turma ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Erro de processamento: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP - Sistema Gerenciador de Passe Escolar</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

    <div class="modal fade" id="modalFinaliza" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-top border-4 border-danger">
                <div class="modal-body text-center p-4">
                    <i class="bi bi-info-circle-fill text-danger" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">O sistema será finalizado!</h4>
                    <p class="text-muted"><small>Developer © 2026 - 1º TENENTE QOAPM MARCOS ANTÔNIO</small></p>
                    <div class="spinner-border text-danger mt-2" role="status">
                        <span class="visually-hidden">Saindo...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'header.php'; ?>

    <div class="container-fluid my-2">
        <?php include 'navbar.php'; ?>
        
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 rounded">
                <form id="formPasse" action="processar.php" method="POST" enctype="multipart/form-data">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">MATRÍCULA</label>
                            <input type="text" name="matricula" id="matricula" placeholder="Ex: 1991050158-49" class="form-control border-primary" pattern="\d{10}-\d{2}" required <?= $is_aluno ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Nº ALUNO</label>
                            <input type="text" name="nr_aluno" id="nr_aluno" class="form-control" placeholder="Ex: 999" pattern="\d{3}" required <?= $is_aluno ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">NOME DE GUERRA</label>
                            <input type="text" name="nome_guerra" id="nome_guerra" class="form-control text-uppercase" required <?= $is_aluno ? 'readonly' : '' ?>>
                        </div>                    

                        <div class="col-md-2">
                            <label class="form-label fw-bold small">TURMA</label>
                            <select name="nome_turma" id="select_turma" class="form-select border-primary" required <?= $is_aluno ? 'style="pointer-events: none; background-color: #e9ecef;"' : '' ?>>
                                <option value="">Selecione...</option>
                                <?php foreach ($lista_turmas as $t): ?>
                                    <option value="<?= htmlspecialchars($t['nome_turma']); ?>"><?= htmlspecialchars($t['nome_turma']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-1">
                            <label class="form-label fw-bold small">PELOTÃO</label>
                            <input type="number" name="pelotao" id="pelotao" class="form-control" value="1" required <?= $is_aluno ? 'readonly' : '' ?>>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-bold small">NOME COMPLETO</label>
                            <input type="text" name="nome_completo" id="nome_completo" class="form-control text-uppercase" required <?= $is_aluno ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">TELEFONE</label>
                            <input type="text" name="telefone" id="telefone" class="form-control" placeholder="(00) 00000-0000" <?= $is_aluno ? 'readonly' : '' ?>>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small">MOTIVO</label>
                            <div class="input-group">
                                <textarea name="motivo" id="motivo" class="form-control" rows="1" required></textarea>
                                <button type="button" class="btn btn-secondary btn-sm" id="btn-ia" title="Sugestão com IA">
                                    <i class="bi bi-cpu"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">DESTINO</label>
                            <input type="text" name="destino" id="destino" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">COMPROVANTE (PDF/JPG)</label>
                            <input type="file" name="agendamento" id="agendamento" class="form-control" accept=".pdf,.jpg,.jpeg">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold small">DATA SAÍDA</label>
                            <input type="date" name="data_saida" id="data_saida" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">PREVISÃO SAÍDA</label>
                            <input type="time" name="hora_saida" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">DATA RETORNO</label>
                            <input type="date" name="data_retorno" id="data_retorno" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">PREVISÃO RETORNO</label>
                            <input type="time" name="hora_retorno" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mt-4 mb-2">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary px-5 shadow-sm">
                                <i class="bi bi-ticket-perforated me-2"></i>GERAR PASSE
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const elMatricula = document.getElementById('matricula');
            const elTelefone = document.getElementById('telefone');

            // Máscaras de Input
            if (elMatricula && !elMatricula.readOnly) {
                elMatricula.addEventListener('input', (e) => {
                    let x = e.target.value.replace(/\D/g, '').match(/(\d{0,10})(\d{0,2})/);
                    e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2];
                });
            }

            if (elTelefone) {
                elTelefone.addEventListener('input', (e) => {
                    let x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
                    e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
                });
            }

            // Preenchimento Automático (Perfil Aluno)
            <?php if ($is_aluno && $dados_aluno): ?>
                document.getElementById('matricula').value = "<?= addslashes($dados_aluno['matricula'] ?? '') ?>";
                document.getElementById('nr_aluno').value = "<?= addslashes($dados_aluno['nr_aluno'] ?? '') ?>";
                document.getElementById('nome_guerra').value = "<?= addslashes($dados_aluno['nome_guerra'] ?? '') ?>";
                document.getElementById('nome_completo').value = "<?= addslashes($dados_aluno['nome_completo'] ?? '') ?>";
                document.getElementById('pelotao').value = "<?= addslashes($dados_aluno['pelotao'] ?? '1') ?>";
                document.getElementById('telefone').value = "<?= addslashes($dados_aluno['telefone'] ?? '') ?>";
                document.getElementById('select_turma').value = "<?= addslashes($dados_aluno['nome_turma'] ?? '') ?>";
                setTimeout(() => { document.getElementById('motivo').focus(); }, 300);
            <?php endif; ?>

            // Lógica de Datas
            const dataSaida = document.getElementById('data_saida');
            const dataRetorno = document.getElementById('data_retorno');
            const hoje = new Date().toISOString().split('T')[0];

            dataSaida.value = hoje;
            dataRetorno.value = hoje;
            dataRetorno.min = hoje;

            dataSaida.addEventListener('change', function() {
                dataRetorno.min = this.value;
                if (dataRetorno.value < this.value) dataRetorno.value = this.value;
            });

            // Busca AJAX (Perfil Admin)
            if (elMatricula && !elMatricula.readOnly) {
                elMatricula.addEventListener('blur', function() {
                    if (!this.value) return;
                    fetch(`index.php?buscar_matricula=${encodeURIComponent(this.value)}`)
                        .then(r => r.json())
                        .then(data => {
                            if(data) {
                                document.getElementById('nr_aluno').value = data.nr_aluno || '';
                                document.getElementById('nome_guerra').value = data.nome_guerra || '';
                                document.getElementById('nome_completo').value = data.nome_completo || '';
                                document.getElementById('pelotao').value = data.pelotao || '';
                                document.getElementById('telefone').value = data.telefone || '';
                                document.getElementById('select_turma').value = data.nome_turma || '';
                            }
                        });
                });
            }

            // Lógica do Botão IA
            document.getElementById('btn-ia').addEventListener('click', function() {
                const btn = this;
                const destino = document.getElementById('destino').value;
                const campoMotivo = document.getElementById('motivo');
        
                if (destino.length < 3) return alert("Preencha o destino primeiro.");

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
                fetch('api_ia.php?destino=' + encodeURIComponent(destino))
                    .then(r => r.json())
                    .then(data => { campoMotivo.value = data.motivo; })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-cpu"></i>';
                    });
            });
        });

        <?php if ($show_modal_finaliza): ?>
            window.onload = function() {
                const modalFinaliza = new bootstrap.Modal(document.getElementById('modalFinaliza'));
                modalFinaliza.show();
                setTimeout(() => { window.location.href = 'https://www.google.com/'; }, 2500);
            };
        <?php endif; ?>
    </script>
</body>
</html>
