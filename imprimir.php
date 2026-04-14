<?php
require 'auth.php'; // Proteção contra acesso não autorizado
require 'bd.php';   // Inclui a conexão com o Supabase ($pdo)

if ($_SESSION['usuario']['perfil'] === 'admin' OR $_SESSION['usuario']['perfil'] === 'usuario'):

date_default_timezone_set('America/Maceio');

// Captura e validação básica do ID
$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID inválido");
}

try {
    // Consulta preparada para PostgreSQL (Supabase)
    $stmt = $pdo->prepare("SELECT * FROM passes WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        die("Registro não encontrado.");
    }
} catch (PDOException $e) {
    die("Erro ao consultar o banco de dados: " . $e->getMessage());
}

function fData($d) { return date('d/m/Y', strtotime($d)); }

// Verificação de segurança: Status de autorização
if ($dados['status'] !== 'AUTORIZADO') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SGP | Aviso de Restrição</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <style>
            body { background-color: #f8f9fa; }
            .modal-header-pmse { background-color: #003366; color: white; }
        </style>
    </head>
    <body>

    <div class="modal fade" id="modalRestricao" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header modal-header-pmse">
                    <h5 class="modal-title" id="modalLabel">
                        <i class="bi bi-shield-lock-fill me-2"></i>Controle de Impressão
                    </h5>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-exclamation-octagon text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="text-dark">Passe Não Autorizado</h4>
                    <p class="text-muted">
                        Este documento ainda não foi <strong>AUTORIZADO</strong> pelo Comando do Corpo de Alunos (CA) 
                        e/ou não possui numeração oficial para emissão.
                    </p>
                </div>
                <div class="modal-footer flex-column border-0">
                    <a href="relatorio.php" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-list-check me-2"></i>Voltar ao Painel de Passes
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-house-door me-2"></i>Ir para a Home
                    </a>
                </div>
                <div class="text-center pb-3">
                    <small class="text-muted font-monospace" style="font-size: 10px;">SGP - Sistema Gerenciador de Passes</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onload = function() {
            var myModal = new bootstrap.Modal(document.getElementById('modalRestricao'));
            myModal.show();
        };
    </script>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP - Sistema Gerenciador de Passe Escolar | Impressão de Passes</title>
    <link rel="shortcut icon" type="imagex/png" href="brasao_cei.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light" onload="window.print()">
    
<div class="container-fluid my-2 sticky-top no-print">
    <div class="row border card-header-pmse text-center m-0 rounded">
        <div class="col-2"><img class="img-fluid float-start w-50 img" src="brasao_cei.png"></div>
        <div class="col-8">
            <h3>POLÍCIA MILITAR DO ESTADO DE SERGIPE</h3>
            <h4>3º SEÇÃO DO ESTADO MAIOR GERAL</h4>
            <h5>CENTRO DE ENSINO E INSTRUÇÃO</h5>
            <h6>CENTRO DE FORMAÇÃO E APERFEIÇOAMENTO DE PRAÇAS</h6>
            <h5>CORPO DE ALUNOS</h5>
        </div>
        <div class="col-2"><img class="img-fluid float-end w-50 img" src="brasao_cfap.png"></div>

        <nav class="navbar navbar-expand-lg navbar-pm shadow-sm mb-2 p-2">
            <div class="container-fluid">
                <a class="navbar-brand text-secondary fw-bold" href="#">
                    <small><i class="bi bi-shield-check text-primary me-2"></i>SGP | Impressão Passe</small>
                </a>
                <div class="navbar-nav ms-auto">
                    <a class="btn btn-outline-secondary bi bi-arrow-left-square-fill" href="relatorio.php"> Painel de Passes</a>
                </div>
            </div>
        </nav>
    </div>
</div>

<div class="container-fluid p-2">
    <img class="watermark1" src="brasao_pmse.png">
    <img class="watermark2" src="brasao_pmse.png">
    <?php for($i=1; $i<=2; $i++): ?>
        <div class="border border-2 border-black mb-1 rounded">
            <div class="row border text-center m-1 p-1 lh-1 rounded">
                <div class="col-2"><img class="img-fluid float-start w-75 img" src="brasao_cei.png" width="50" height="50"></div>
                <div class="col-8">
                    <strong>POLÍCIA MILITAR DO ESTADO DE SERGIPE</strong><br>
                    <strong>3º SEÇÃO DO ESTADO MAIOR GERAL</strong><br>
                    <strong>CENTRO DE ENSINO E INSTRUÇÃO</strong><br>
                    <strong class="small">CENTRO DE FORMAÇÃO E APERFEIÇOAMENTO DE PRAÇAS</strong><br>
                    <strong>CORPO DE ALUNOS</strong>
                </div>
                <div class="col-2"><img class="img-fluid float-end w-75 img" src="brasao_cfap.png" width="50" height="50"></div>
            </div>

            <div class="row border text-center bg-light lh-1 m-1 rounded">
                <div class="p-1"><strong>PASSE ESCOLAR Nº <?= str_pad($dados['numero_passe'],5,'0',STR_PAD_LEFT)." - Mat. ".$dados['matricula']?><br><small><?=$i?>ª via emitida em <?=date('d/m/Y H:i:s',strtotime($dados['data_solicitacao']))?></small></strong></div>
            </div>
    
            <div class="row text-center lh-1 m-1" style="font-size: 11px;">
                <div class="col-6 border p-1 rounded"><strong>SOLICITANTE</strong><br><small class="text-uppercase font-dados"><?=$dados['nome_completo']?></small></div>
                <div class="col-3 border p-1 rounded"><strong>NOME DE GUERRA</strong><br><small class="text-uppercase font-dados"><?=$dados['nome_guerra']?></small></div>
                <div class="col-3 border p-1 rounded"><strong>TURMA <?=$dados['nome_turma']?></strong><br><small class="font-dados">Aluno(a) <?=$dados['pelotao']?>º PEL Nº <?=$dados['nr_aluno']?></small></div>
            </div>

            <div class="row text-center lh-1 m-1" style="font-size: 11px;">
                <div class="col-6 border p-1 rounded"><strong>MOTIVO</strong><br><small class="font-dados"><?= htmlspecialchars($dados['motivo'])?></small></div>
                <div class="col-3 border p-1 rounded"><strong>PREVISÃO SAÍDA</strong><br><small class="font-dados"><?= fData($dados['data_saida'])?> | <?= $dados['hora_saida']?></small></div>
                <div class="col-3 border p-1 rounded"><strong>PREVISÃO RETORNO</strong><br><small class="font-dados"><?= fData($dados['data_retorno'])?> | <?= $dados['hora_retorno']?></small></div>
            </div>

            <div class="row text-center lh-1 m-1" style="font-size: 11px;">
                <div class="col-6 border p-1 rounded"><strong>DESTINO</strong><br><small class="font-dados"><?= htmlspecialchars($dados['destino'])?></small></div>
                <div class="col-6 border p-1 rounded"><strong>STATUS DE LIBERAÇÃO</strong><br><small class="font-dados">| <?= $dados['status']." por ".$_SESSION['usuario']['matricula']." | ".$dados['fundamentacao']?></small></div>
            </div>

            <div class="row mt-5 text-center m-1">
                <div class="col-6 border-top"><small><strong>Assinatura do Solicitante em <?=date('d/m/Y H:i:s');?></strong></small></div>
                <div class="col-1"></div>
                <div class="col-5 border-top"><small><strong>Comandante do Corpo de Alunos</strong></small></div>
            </div>

            <?php if($i==1){?>
                <div class="row bg-light text-center m-1 mt-4 lh-1" style="font-size: 8px;">
                    <div class="col-4 border rounded"><p class="m-2">Declaro ter recebido a 2ª via do Passe Escolar</p></div>
                    <div class="col-8 border rounded"><p class="m-2">Anexar documento comprobatório da solicitação (Marcação, Protocolo, Relatório ou Print de conversa no Whatsapp)</p></div>
                </div>
            <?php } else { ?>
                <div class="row bg-light text-center m-1 border rounded" style="font-size: 7px;">
                    <div class="text-center"><p class="m-2">ESTOU CIENTE QUE MINHA AUSÊNCIA ÀS AULAS NÃO PODE ULTRAPASSAR 20% DA CARGA HORÁRIA TOTAL DAS DISCIPLINAS, POIS, SENDO ESTE O CASO, ENSEJARÁ EM REPROVAÇÃO POR FALTAS</p></div>
                    <div class="border border-dotted w-100 my-1"></div>
                    <div class="row text-center"><p class="m-2" style="font-size: 6px;">CONTROLE DE COMPARECIMENTO DO SOLICITANTE AO LOCAL DE DESTINO. ANEXAR DOCUMENTO COMPROBATÓRIO DA SOLICITAÇÃO (MARCAÇÃO, PROTOCOLO, RELATÓRIO OU PRINT DE CONVERSA NO WHATSAPP)</p></div>
                </div>
                <div class="row text-center m-1"><span style="font-size: 10px;">Na impossibilidade de fornecimento de documento comprobatório de comparecimento ao destino, solicitar o preenchimento da declaração abaixo</span></div>
                <div class="border rounded m-1 px-1">
                    <div class="text-center"><strong>DECLARAÇÃO</strong></div>
                    <div><span class="small" style="font-size: 12px; text-align: justify;">Eu, ________________________________________, CARGO / FUNÇÃO ________________________________________ da EMPRESA / ÓRGÃO _______________________________________  declaro que o solicitante compareceu a este local para tratar de assunto, conforme acima citado. (LOCAL / DATA / HORA) ______________________, ____/____/_______ às ____:____, sendo liberado às ____:____.</span></div>
                    <div class="text-center"><span class="small"><strong>APRESENTAÇÃO DO ALUNO NA UNIDADE ESCOLA</strong></span></div>
                    <div class="row text-center mt-3" style="font-size: 12px;">
                        <div class="col-6"><span class="small">RUBRICA - POSTO/GRADUAÇÃO OU CARIMBO</span></div>
                        <div class="col-3"><span class="small">DATA: ____/____/_______</span></div>
                        <div class="col-3"><span class="small">HORÁRIO: ____:____</span></div>
                    </div>
                </div>
            <?php }?>

            <small class="row font-monospace text-center" style="font-size: 7px;"><strong>Developer © 2026 - <span class="fst-italic">1º TENENTE</span> QOAPM <span class="fst-italic">MARCOS ANTÔNIO</span> OLIVEIRA CAMPOS</strong></small>
        </div>
        <?php if ($i == 1) {?>
            <div class="border border-dotted w-100 mt-4 mb-4"></div>
        <?php }?>
    <?php endfor; ?>
</div>
</body>
</html>

<?php else:
    header("Location: index.php");
endif; ?>