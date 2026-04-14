<?php
// 1. Dados de Conexão via TRANSACTION POOLER (Porta 6543)
//postgresql://postgres:[YOUR-PASSWORD]@db.fofiwziwsabcxflguqva.supabase.co:5432/postgres
$host     = 'db.fofiwziwsabcxflguqva.supabase.co'; // EXEMPLO: Verifique o seu no painel do Supabase
$port     = '5432'; 
$dbname   = 'postgres';
$user     = 'postgres'; 
$password = 'CEI/CFAP/SGP';

try {
    // 2. DSN com SSL obrigatório e modo de transação
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10 
    ];

    $pdo = new PDO($dsn, $user, $password, $options);

} catch (PDOException $e) {
    // Temporariamente, vamos logar o erro real para você ver o que falta
    // Mas sem dar 'echo' para não quebrar os headers do auth.php
    error_log("Falha crítica no BD: " . $e->getMessage());
    die("Erro de conexão: Verifique as credenciais no painel do Supabase.");
}
