<?php
require_once 'config/config.php';

echo "<h2>🧹 Limpiando remitos de la tabla compras...</h2>";

try {
    $pdo = conectarDB();
    
    // Primero ver qué remitos hay en compras
    echo "<h3>🔍 Remitos encontrados en tabla compras:</h3>";
    $remitos_compras = $pdo->query("SELECT codigo, estado, fecha_creacion FROM compras WHERE codigo LIKE 'REMI-%'")->fetchAll();
    
    if (empty($remitos_compras)) {
        echo "<p>✅ No hay remitos en la tabla compras. Todo está limpio.</p>";
    } else {
        echo "<ul>";
        foreach ($remitos_compras as $remito) {
            echo "<li><strong>{$remito['codigo']}</strong> - {$remito['estado']} - {$remito['fecha_creacion']}</li>";
        }
        echo "</ul>";
        
        echo "<h3>🗑️ Eliminando remitos de compras...</h3>";
        
        $pdo->beginTransaction();
        
        // Eliminar detalles primero
        $stmt1 = $pdo->prepare("DELETE FROM compra_detalles WHERE compra_id IN (SELECT id FROM compras WHERE codigo LIKE 'REMI-%')");
        $detalles_eliminados = $stmt1->execute();
        $detalles_count = $stmt1->rowCount();
        
        // Eliminar remitos de compras
        $stmt2 = $pdo->prepare("DELETE FROM compras WHERE codigo LIKE 'REMI-%'");
        $remitos_eliminados = $stmt2->execute();
        $remitos_count = $stmt2->rowCount();
        
        $pdo->commit();
        
        echo "✅ <strong>$detalles_count</strong> detalles de remitos eliminados<br>";
        echo "✅ <strong>$remitos_count</strong> remitos eliminados de la tabla compras<br>";
        
        // Verificar que se eliminaron
        $verificacion = $pdo->query("SELECT COUNT(*) as count FROM compras WHERE codigo LIKE 'REMI-%'")->fetch();
        
        if ($verificacion['count'] == 0) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>🎉 ¡Limpieza completada!</h4>";
            echo "Todos los remitos han sido eliminados de la tabla compras.<br>";
            echo "Ahora los nuevos remitos se guardarán solo en las tablas de remitos.";
            echo "</div>";
        } else {
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
            echo "⚠️ Aún quedan {$verificacion['count']} remitos en la tabla compras.";
            echo "</div>";
        }
    }
    
    // Verificar estado de compras después de limpieza
    echo "<h3>📊 Estado actual de la tabla compras:</h3>";
    $compras_restantes = $pdo->query("SELECT COUNT(*) as count FROM compras")->fetch();
    echo "Compras totales restantes: <strong>{$compras_restantes['count']}</strong><br>";
    
    $compras_por_tipo = $pdo->query("
        SELECT 
            CASE 
                WHEN codigo LIKE 'COMP-%' THEN 'Compras'
                WHEN codigo LIKE 'REMI-%' THEN 'Remitos'
                ELSE 'Otros'
            END as tipo,
            COUNT(*) as cantidad
        FROM compras 
        GROUP BY tipo
    ")->fetchAll();
    
    foreach ($compras_por_tipo as $tipo) {
        echo "- {$tipo['tipo']}: {$tipo['cantidad']}<br>";
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<br><p><a href='modulos/compras/compras.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📋 Ver Lista de Compras</a></p>";
?>
