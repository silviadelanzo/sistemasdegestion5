<?php
require_once '../config/config.php';

try {
    $pdo = conectarDB();
    
    echo "=== VERIFICANDO PROVEEDORES ===\n";
    $stmt = $pdo->query("SELECT id, codigo, razon_social FROM proveedores ORDER BY razon_social");
    $proveedores = $stmt->fetchAll();
    
    foreach ($proveedores as $p) {
        echo "ID: {$p['id']}, Código: " . ($p['codigo'] ?: 'SIN CÓDIGO') . ", Razón: {$p['razon_social']}\n";
    }
    
    echo "\n=== CÓDIGOS FALTANTES ===\n";
    $stmt = $pdo->query("SELECT id, razon_social FROM proveedores WHERE codigo IS NULL OR codigo = ''");
    $sin_codigo = $stmt->fetchAll();
    
    foreach ($sin_codigo as $p) {
        $nuevo_codigo = 'PROV' . str_pad($p['id'], 3, '0', STR_PAD_LEFT);
        echo "ID: {$p['id']}, Razón: {$p['razon_social']}, Nuevo código: {$nuevo_codigo}\n";
        
        // Actualizar
        $update = $pdo->prepare("UPDATE proveedores SET codigo = ? WHERE id = ?");
        $update->execute([$nuevo_codigo, $p['id']]);
    }
    
    echo "\n=== CÓDIGOS ACTUALIZADOS ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
