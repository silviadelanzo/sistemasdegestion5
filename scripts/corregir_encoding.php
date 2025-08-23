<?php
require_once '../config/config.php';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "=== CORRIGIENDO ENCODING ===\n";
    
    // Corregir el nombre con caracteres extraños
    $stmt = $pdo->prepare("UPDATE proveedores SET razon_social = ? WHERE id = ?");
    $stmt->execute(['Tecnología Avanzada S.A.', 16]);
    
    echo "Corregido encoding de 'Tecnología Avanzada S.A.'\n";
    
    echo "=== VERIFICANDO RESULTADOS ===\n";
    $stmt = $pdo->query("SELECT id, codigo, razon_social FROM proveedores ORDER BY razon_social");
    $proveedores = $stmt->fetchAll();
    
    foreach ($proveedores as $p) {
        echo "ID: {$p['id']}, Código: {$p['codigo']}, Razón: {$p['razon_social']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
