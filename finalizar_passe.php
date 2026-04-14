<?php
require 'auth.php';
require 'bd.php';

// Verifica permissão de acesso
if ($_SESSION['usuario']['perfil'] === 'admin' || $_SESSION['usuario']['perfil'] === 'usuario') {
    
    // Configura o fuso horário para a região especificada
    date_default_timezone_set('America/Maceio');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_passe'])) {
        $id = $_POST['id_passe'];
        $descricao = $_POST['descricao_retorno'] ?? '';
        $data_retorno_real = date('Y-m-d H:i:s');
        $arquivo_nome = null;

        // Gerenciamento do Upload de Comprovante
        if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            
            // Cria o diretório caso não exista
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $ext = pathinfo($_FILES['comprovante']['name'], PATHINFO_EXTENSION);
            $arquivo_nome = "COMP_" . $id . "_" . date('Ymd_His') . "." . $ext;
            $caminho_final = $upload_dir . $arquivo_nome;

            if (!move_uploaded_file($_FILES['comprovante']['tmp_name'], $caminho_final)) {
                $arquivo_nome = null; // Falha no upload, limpa a variável para o banco
            }
        }

        try {
            // Atualização no Banco de Dados
            // Importante: Certifique-se de que a coluna 'descricao_retorno' foi criada via SQL
            $sql = "UPDATE passes SET 
                    status = 'FINALIZADO', 
                    comprovante = :comp, 
                    data_retorno_real = :data_r, 
                    descricao_retorno = :desc 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':comp'   => $arquivo_nome, 
                ':data_r' => $data_retorno_real, 
                ':desc'   => $descricao, 
                ':id'     => $id
            ]);

            // Redirecionamento em caso de sucesso
            header("Location: relatorio.php?sucesso=1");
            exit;

        } catch (PDOException $e) {
            // Em caso de erro, interrompe e exibe a mensagem amigável
            die("Erro ao finalizar passe: " . $e->getMessage());
        }
    } else {
        // Se tentar acessar o script sem o ID do passe ou via GET indevido
        header("Location: relatorio.php");
        exit;
    }
} else {
    // Se não tiver perfil de admin ou usuário
    header("Location: index.php");
    exit;
}