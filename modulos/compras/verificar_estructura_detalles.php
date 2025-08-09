<?php
require_once '../../config/config.php';

echo "🔍 Verificando estructura de remito_detalles:\n";

try {
    $pdo = conectarDB();
    
    $cols = $pdo->query('DESCRIBE remito_detalles')->fetchAll();
    echo "📋 Columnas de remito_detalles:\n";
    foreach ($cols as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
