<?php
require_once '../config/config.php';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "🔍 ANÁLISIS TABLA PROVEEDORES\n";
    echo "==============================\n\n";
    
    // Estructura
    echo "📋 ESTRUCTURA:\n";
    $stmt = $pdo->query("DESCRIBE proveedores");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columnas as $col) {
        echo "- {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }
    
    // Datos de ejemplo
    echo "\n📊 ÚLTIMOS 5 PROVEEDORES:\n";
    $stmt = $pdo->query("SELECT codigo, razon_social, pais_id FROM proveedores ORDER BY id DESC LIMIT 5");
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($proveedores as $p) {
        echo "- Código: {$p['codigo']} | Razón: {$p['razon_social']} | País ID: {$p['pais_id']}\n";
    }
    
    // Numeración actual
    echo "\n🔢 ANÁLISIS NUMERACIÓN:\n";
    $stmt = $pdo->query("SELECT codigo FROM proveedores WHERE codigo LIKE 'PROV%' ORDER BY codigo DESC LIMIT 10");
    $codigos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Códigos PROV existentes:\n";
    foreach($codigos as $codigo) {
        echo "- $codigo\n";
    }
    
    // Último número
    if(!empty($codigos)) {
        $ultimoCodigo = $codigos[0];
        preg_match('/PROV-?(\d+)/', $ultimoCodigo, $matches);
        $ultimoNumero = isset($matches[1]) ? intval($matches[1]) : 0;
        $proximoNumero = $ultimoNumero + 1;
        $proximoCodigo = "PROV-" . str_pad($proximoNumero, 6, '0', STR_PAD_LEFT);
        
        echo "\nÚltimo número: $ultimoNumero\n";
        echo "Próximo código: $proximoCodigo\n";
    } else {
        echo "\nNo hay códigos PROV, empezar con: PROV-000001\n";
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
