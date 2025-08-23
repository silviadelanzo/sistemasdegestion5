<?php
require_once '../config/config.php';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "🔄 MIGRACIÓN CÓDIGOS PROVEEDORES\n";
    echo "================================\n\n";
    
    // Ver códigos actuales
    echo "📋 CÓDIGOS ACTUALES:\n";
    $stmt = $pdo->query("SELECT id, codigo, razon_social FROM proveedores WHERE codigo NOT LIKE 'PROV-%' ORDER BY id");
    $proveedoresViejos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($proveedoresViejos as $p) {
        echo "- ID: {$p['id']} | Código: {$p['codigo']} | Razón: {$p['razon_social']}\n";
    }
    
    if(empty($proveedoresViejos)) {
        echo "✅ No hay códigos antiguos para migrar\n";
    } else {
        echo "\n🔄 MIGRANDO CÓDIGOS:\n";
        
        foreach($proveedoresViejos as $p) {
            // Extraer número del código viejo
            preg_match('/(\d+)/', $p['codigo'], $matches);
            $numero = isset($matches[1]) ? intval($matches[1]) : $p['id'];
            
            // Generar nuevo código con 7 dígitos
            $nuevoCodigo = 'PROV-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
            
            // Actualizar en base de datos
            $stmt = $pdo->prepare("UPDATE proveedores SET codigo = ? WHERE id = ?");
            $stmt->execute([$nuevoCodigo, $p['id']]);
            
            echo "✅ {$p['codigo']} → $nuevoCodigo ({$p['razon_social']})\n";
        }
    }
    
    // Mostrar resultado final
    echo "\n📊 CÓDIGOS FINALES:\n";
    $stmt = $pdo->query("SELECT codigo, razon_social FROM proveedores ORDER BY codigo");
    $proveedoresFinales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($proveedoresFinales as $p) {
        echo "- {$p['codigo']} | {$p['razon_social']}\n";
    }
    
    // Próximo código
    echo "\n🔢 PRÓXIMO CÓDIGO DISPONIBLE:\n";
    $sql_code = "SELECT codigo FROM proveedores WHERE codigo LIKE 'PROV-%' ORDER BY CAST(SUBSTRING(codigo, 6) AS UNSIGNED) DESC, codigo DESC LIMIT 1";
    $stmt_code = $pdo->query($sql_code);
    $ultimo_codigo = $stmt_code->fetchColumn();
    $numero = $ultimo_codigo ? intval(substr($ultimo_codigo, 5)) + 1 : 1;
    $proximoCodigo = 'PROV-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
    
    echo "➡️ $proximoCodigo\n";
    
    echo "\n✅ MIGRACIÓN COMPLETADA\n";
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
