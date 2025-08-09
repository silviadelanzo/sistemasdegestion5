<?php
require_once '../../config/config.php';

echo "<h2>🔧 Ejecutando sistema_impuestos_monedas_completo.sql</h2>";

try {
    $pdo = conectarDB();
    
    // Leer el archivo SQL
    $sql_content = file_get_contents('sistema_impuestos_monedas_completo.sql');
    
    if ($sql_content === false) {
        throw new Exception("No se pudo leer el archivo SQL");
    }
    
    echo "<h3>📋 Ejecutando script SQL...</h3>";
    
    // Dividir por declaraciones individuales (evitar DELIMITER issues)
    $statements = explode(';', $sql_content);
    $success_count = 0;
    $total_statements = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Saltar statements vacíos y comentarios
        if (empty($statement) || 
            substr($statement, 0, 2) === '--' || 
            substr($statement, 0, 9) === 'DELIMITER') {
            continue;
        }
        
        try {
            $total_statements++;
            $pdo->exec($statement);
            $success_count++;
            echo "✅ Ejecutado correctamente<br>";
        } catch (PDOException $e) {
            // Mostrar errores pero continuar
            echo "⚠️ Warning: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h3>📊 Resultado:</h3>";
    echo "✅ <strong>$success_count/$total_statements</strong> declaraciones ejecutadas<br><br>";
    
    // Verificar tablas creadas
    echo "<h3>🔍 Verificando estructura creada...</h3>";
    
    $tablas = ['monedas', 'impuestos', 'productos'];
    
    foreach ($tablas as $tabla) {
        $result = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($result->rowCount() > 0) {
            echo "✅ Tabla <strong>$tabla</strong> disponible<br>";
            
            // Mostrar conteo
            $count = $pdo->query("SELECT COUNT(*) as total FROM $tabla")->fetch();
            echo "<small>   Registros: {$count['total']}</small><br>";
        } else {
            echo "❌ Tabla <strong>$tabla</strong> NO encontrada<br>";
        }
    }
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🎉 ¡Base de datos preparada!</h4>";
    echo "El sistema de impuestos/monedas está listo.<br>";
    echo "<strong>Próximo paso:</strong> Crear el nuevo producto_form.php con 6 pestañas.";
    echo "</div>";
    
    echo "<p><a href='modulos/Inventario/producto_form.php' class='btn btn-primary' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🆕 Ir al Nuevo Formulario</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ Error</h4>";
    echo $e->getMessage();
    echo "</div>";
}
?>
