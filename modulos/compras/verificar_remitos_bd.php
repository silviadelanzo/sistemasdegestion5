<?php
require_once '../../config/config.php';

echo "🔍 Verificando remitos en la base de datos:\n";

try {
    $pdo = conectarDB();
    
    // Contar total de remitos
    $total = $pdo->query("SELECT COUNT(*) FROM remitos")->fetchColumn();
    echo "📊 Total de remitos en la BD: $total\n\n";
    
    // Listar todos los remitos
    $remitos = $pdo->query("SELECT id, codigo, numero_remito_proveedor, estado, fecha_entrega, proveedor_id, codigo_proveedor FROM remitos ORDER BY id")->fetchAll();
    
    echo "📋 Listado completo de remitos:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    foreach ($remitos as $r) {
        echo "🆔 ID: {$r['id']} | 📝 Código: {$r['codigo']} | 🔢 Número: {$r['numero_remito_proveedor']} | 📅 Fecha: {$r['fecha_entrega']} | ⚡ Estado: {$r['estado']} | 🏭 Proveedor ID: {$r['proveedor_id']} | 📋 Código Prov: {$r['codigo_proveedor']}\n";
    }
    
    echo "\n📊 Conteo por estado:\n";
    $estados = $pdo->query("SELECT estado, COUNT(*) as cantidad FROM remitos GROUP BY estado")->fetchAll();
    foreach ($estados as $estado) {
        echo "  {$estado['estado']}: {$estado['cantidad']} remitos\n";
    }
    
    // Verificar detalles
    echo "\n📦 Productos por remito:\n";
    $detalles = $pdo->query("
        SELECT r.id, r.codigo, COUNT(rd.id) as productos, SUM(rd.cantidad) as total_cantidad
        FROM remitos r 
        LEFT JOIN remito_detalles rd ON r.id = rd.remito_id 
        GROUP BY r.id
    ")->fetchAll();
    
    foreach ($detalles as $d) {
        echo "  Remito {$d['codigo']}: {$d['productos']} productos, {$d['total_cantidad']} unidades\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
