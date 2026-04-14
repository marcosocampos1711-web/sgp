<?php
require 'auth.php'; // Proteção contra acesso não autorizado
require 'bd.php';   // Importa a conexão PDO ($pdo) com o Supabase/PostgreSQL

date_default_timezone_set('America/Maceio');
$upload_dir = 'uploads/agendamentos/';

// Criar pasta de uploads se não existir
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    // A variável $pdo já está disponível através do require 'bd.php'

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
        $data_saida = $_POST['data_saida'];
        $hora_saida = $_POST['hora_saida'];
        $data_retorno = $_POST['data_retorno'];
        $hora_retorno = $_POST['hora_retorno'];

        // Tratamento de dados para Caixa Alta (Maiúsculas)
        $nome_completo = mb_strtoupper($_POST['nome_completo'], 'UTF-8');
        $nome_guerra = mb_strtoupper($_POST['nome_guerra'], 'UTF-8');

        // Lógica de Upload
        $caminho_arquivo = null;
        if (isset($_FILES['agendamento']) && $_FILES['agendamento']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['agendamento']['name'], PATHINFO_EXTENSION));
            $formatos_permitidos = ['pdf', 'jpg', 'jpeg'];

            if (in_array($extensao, $formatos_permitidos)) {
                $novo_nome = bin2hex(random_bytes(8)) . '_' . time() . '.' . $extensao;
                $caminho_destino = $upload_dir . $novo_nome;

                if (move_uploaded_file($_FILES['agendamento']['tmp_name'], $caminho_destino)) {
                    $caminho_arquivo = $caminho_destino;
                }
            }
        }

        // 1. Criar timestamps para comparação precisa
        $timestamp_saida = strtotime("$data_saida $hora_saida");
        $timestamp_retorno = strtotime("$data_retorno $hora_retorno");

        // 2. Validação no Servidor
        if ($timestamp_retorno <= $timestamp_saida) {
            die("
                <div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
                    <h3 style='color:red;'>Erro de Validação</h3>
                    <p>A data/hora de retorno deve ser posterior à data/hora de saída.</p>
                    <a href='index.php'>Voltar ao Formulário</a>
                </div>
            ");
        }
        
        // Verifica se o aluno já existe pela matrícula (PostgreSQL)
        $stmt_check = $pdo->prepare("SELECT id FROM alunos WHERE matricula = ?");
        $stmt_check->execute([$_POST['matricula']]);
        $aluno_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($aluno_existente) {
            $sql_aluno = "UPDATE alunos SET 
                            nr_aluno = :nr, 
                            nome_completo = :nome, 
                            nome_guerra = :guerra, 
                            pelotao = :pel, 
                            nome_turma = :turma, 
                            telefone = :tel 
                          WHERE matricula = :mat";
        } else {
            $sql_aluno = "INSERT INTO alunos (matricula, nr_aluno, nome_completo, nome_guerra, pelotao, nome_turma, telefone) 
                          VALUES (:mat, :nr, :nome, :guerra, :pel, :turma, :tel)";
        }

        $stmt_aluno = $pdo->prepare($sql_aluno);
        $stmt_aluno->execute([
            ':mat'    => $_POST['matricula'], 
            ':nr'     => $_POST['nr_aluno'],
            ':nome'   => $nome_completo,
            ':guerra' => $nome_guerra,
            ':pel'    => $_POST['pelotao'],
            ':turma'  => $_POST['nome_turma'],
            ':tel'    => $_POST['telefone']
        ]);

        // Inserção do Passe com RETURNING id para garantir captura no PostgreSQL
        $sql_passe = "INSERT INTO passes (nome_turma, pelotao, nome_guerra, matricula, nr_aluno, nome_completo, destino, motivo, telefone, data_saida, hora_saida, data_retorno, hora_retorno, agendamento) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id";
        
        $stmt_passe = $pdo->prepare($sql_passe);
        $stmt_passe->execute([
            $_POST['nome_turma'], 
            $_POST['pelotao'], 
            $nome_guerra,
            $_POST['matricula'],
            $_POST['nr_aluno'],
            $nome_completo,
            $_POST['destino'],
            $_POST['motivo'],
            $_POST['telefone'],
            $_POST['data_saida'], 
            $_POST['hora_saida'], 
            $_POST['data_retorno'], 
            $_POST['hora_retorno'],
            $caminho_arquivo
        ]);

        // Captura o ID gerado pelo PostgreSQL
        $result_passe = $stmt_passe->fetch(PDO::FETCH_ASSOC);
        $lastId = $result_passe['id'];

        // Renderização do Modal de Sucesso
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Processando Passe</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">

            <div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Sucesso!</h5>
                        </div>
                        <div class="modal-body text-center">
                            <p class="fs-5">Passe Escolar gerado com Sucesso!</p>
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-center">
                            <button type="button" class="btn btn-success" onclick="redirect()">Imprimir Passe agora</button>
                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                const myModal = new bootstrap.Modal(document.getElementById('successModal'));
                myModal.show();
                function redirect() { window.location.href = "imprimir.php?id=<?php echo $lastId; ?>"; }
                setTimeout(redirect, 3000);
            </script>
        </body>
        </html>
        <?php
        exit;
    }
} catch (PDOException $e) { 
    die("Erro no Banco de Dados: " . $e->getMessage()); 
} catch (Exception $e) {
    die("Erro Geral: " . $e->getMessage());
}
?>