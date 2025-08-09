<?php
require_once 'config/config.php';

echo "<h2>✅ Estado del Sistema de Remitos</h2>";

try {
    $pdo = conectarDB();
    
    echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>📋 Verificación Completa</h3>";
    
    // 1. Verificar tablas de remitos
    $tablas_remitos = ['remitos', 'remito_detalles'];
    $tablas_ok = 0;
    
    echo "<h4>🏗️ Tablas de Remitos:</h4>";
    foreach ($tablas_remitos as $tabla) {
        $result = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($result->rowCount() > 0) {
            echo "✅ <strong>$tabla</strong> existe<br>";
            $tablas_ok++;
        } else {
            echo "❌ <strong>$tabla</strong> NO existe<br>";
        }
    }
    
    // 2. Verificar datos en remitos
    if ($tablas_ok === 2) {
        $remitos_count = $pdo->query("SELECT COUNT(*) as count FROM remitos")->fetch();
        echo "<h4>📊 Datos en Remitos:</h4>";
        echo "Remitos en tabla remitos: <strong>{$remitos_count['count']}</strong><br>";
        
        if ($remitos_count['count'] > 0) {
            $remitos = $pdo->query("SELECT codigo, estado, fecha_creacion FROM remitos ORDER BY fecha_creacion DESC LIMIT 5")->fetchAll();
            echo "<ul>";
            foreach ($remitos as $remito) {
                echo "<li>{$remito['codigo']} - {$remito['estado']} - {$remito['fecha_creacion']}</li>";
            }
            echo "</ul>";
        }
    }
    
    // 3. Verificar que no hay remitos en compras
    $remitos_en_compras = $pdo->query("SELECT COUNT(*) as count FROM compras WHERE codigo LIKE 'REMI-%'")->fetch();
    echo "<h4>🧹 Limpieza de Compras:</h4>";
    if ($remitos_en_compras['count'] == 0) {
        echo "✅ No hay remitos en la tabla compras<br>";
    } else {
        echo "⚠️ Aún hay <strong>{$remitos_en_compras['count']}</strong> remitos en compras<br>";
    }
    
    // 4. Verificar formulario
    echo "<h4>📝 Formulario:</h4>";
    if (file_exists('modulos/compras/compras_form.php')) {
        echo "✅ Formulario existe<br>";
        
        $form_content = file_get_contents('modulos/compras/compras_form.php');
        if (strpos($form_content, 'action="guardar_remito.php"') !== false) {
            echo "✅ Formulario apunta a guardar_remito.php<br>";
        } else {
            echo "❌ Formulario NO apunta a guardar_remito.php<br>";
        }
    }
    
    // 5. Verificar archivo de guardado
    echo "<h4>💾 Archivo de Guardado:</h4>";
    if (file_exists('modulos/compras/guardar_remito.php')) {
        echo "✅ guardar_remito.php existe<br>";
        
        $guardar_content = file_get_contents('modulos/compras/guardar_remito.php');
        if (strpos($guardar_content, 'INSERT INTO remitos') !== false) {
            echo "✅ Guarda en tabla remitos<br>";
        } else {
            echo "❌ NO guarda en tabla remitos<br>";
        }
    }
    
    echo "</div>";
    
    // Resultado final
    if ($tablas_ok === 2 && $remitos_en_compras['count'] == 0) {
        echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>🎉 ¡SISTEMA CONFIGURADO CORRECTAMENTE!</h3>";
        echo "<p><strong>✅ Estado:</strong> Los remitos ahora se guardan en sus propias tablas</p>";
        echo "<p><strong>✅ Separación:</strong> Remitos y compras están completamente separados</p>";
        echo "<p><strong>✅ Limpieza:</strong> No hay remitos duplicados en compras</p>";
        echo "<br>";
        echo "<p><strong>🚀 Próximo paso:</strong> Cuando quieras, puedes desarrollar la página <code>remitos.php</code> para ver el listado de remitos.</p>";
        echo "</div>";
        
        echo "<div style='text-align: center; margin: 30px 0;'>";
        echo "<a href='modulos/compras/compras_form.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 10px; display: inline-block;'>";
        echo "🆕 Crear Nuevo Remito";
        echo "</a>";
        echo "<a href='modulos/compras/compras.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 10px; display: inline-block;'>";
        echo "📋 Ver Compras (sin remitos)";
        echo "</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px;'>";
        echo "<h3>⚠️ Configuración Incompleta</h3>";
        echo "<p>Hay algunos problemas que necesitan resolverse antes de continuar.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
