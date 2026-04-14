<?php
// Alterar para o Host do Transaction Pooler (disponível no painel do Supabase)
$host     = 'db.fofiwziwsabcxflguqva.supabase.co'; 
$port     = '6543'; // Porta do Pooler é mais resiliente
$dbname   = 'postgres';
$user     = 'postgres.fofiwziwsabcxflguqva'; // Use o usuário completo para o Pooler
$password = 'CEI/CFAP/SGP';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require"; // Adicionado sslmode
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ];

    $pdo = new PDO($dsn, $user, $password, $options);

} catch (PDOException $e) {
    error_log("Erro de Conexão: " . $e->getMessage());
    exit("Erro interno no servidor."); 
}
