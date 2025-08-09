<?php
require_once 'config/config.php';

echo "<h2>🔧 Creando tablas de remitos...</h2>";

try {
    $pdo = conectarDB();
    
    // Leer el archivo SQL
    $sql_content = file_get_contents('crear_tabla_remitos.sql');
    
    // Separar las consultas por punto y coma
    $queries = array_filter(array_map('trim', explode(';', $sql_content)));
    
    $success_count = 0;
    
    foreach ($queries as $query) {
        if (!empty($query) && !preg_match('/^\s*--/', $query)) {
            try {
                $pdo->exec($query);
                $success_count++;
                echo "✅ Ejecutado: " . substr($query, 0, 50) . "...<br>";
            } catch (Exception $e) {
                echo "❌ Error en consulta: " . substr($query, 0, 50) . "... - " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<h3>📊 Resultado:</h3>";
    echo "✅ <strong>$success_count</strong> consultas ejecutadas exitosamente<br><br>";
    
    // Verificar que las tablas se crearon
    echo "<h3>🔍 Verificando tablas creadas:</h3>";
    
    $tablas = ['remitos', 'remito_detalles'];
    $tablas_ok = 0;
    
    foreach ($tablas as $tabla) {
        $result = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($result->rowCount() > 0) {
            echo "✅ Tabla <strong>$tabla</strong> creada correctamente<br>";
            $tablas_ok++;
            
            // Mostrar estructura básica
            $columns = $pdo->query("SHOW COLUMNS FROM $tabla")->fetchAll();
            echo "<small>Columnas: ";
            $col_names = array_map(function($col) { return $col['Field']; }, $columns);
            echo implode(', ', $col_names);
            echo "</small><br><br>";
        } else {
            echo "❌ Tabla <strong>$tabla</strong> NO se pudo crear<br>";
        }
    }
    
    if ($tablas_ok === 2) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>🎉 ¡ÉXITO TOTAL!</h4>";
        echo "Las tablas de remitos se crearon correctamente.<br>";
        echo "Ahora los nuevos remitos se guardarán en las tablas correctas.<br><br>";
        echo "<strong>Próximo paso:</strong> Probar crear un nuevo remito desde el formulario.";
        echo "</div>";
        
        echo "<p><a href='modulos/compras/compras_form.php' class='btn btn-success' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🆕 Crear Nuevo Remito</a></p>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>⚠️ Problema</h4>";
        echo "No se pudieron crear todas las tablas necesarias.<br>";
        echo "Revisa los errores arriba o ejecuta el SQL manualmente en phpMyAdmin.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Error general:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
