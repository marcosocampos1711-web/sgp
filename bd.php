<?php
// 1. Dados de Conexão DIRETA
$host     = 'db.fofiwziwsabcxflguqva.supabase.co'; 
$port     = '5432'; 
$dbname   = 'postgres';
$user     = 'postgres'; // Na conexão direta (porta 5432), use apenas 'postgres'
$password = 'CEI/CFAP/SGP';

try {
    // 2. String de conexão simplificada
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    // 3. Opções para forçar o SSL (Obrigatório no Supabase)
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5 // Timeout de 5 segundos
    ];

    $pdo = new PDO($dsn, $user, $password, $options);


} catch (PDOException $e) {
    // Se ainda der erro, vamos imprimir o DSN para conferência (CUIDADO: remove isso depois)
    echo "❌ Erro de Conexão: " . $e->getMessage();
}