<?php
require_once '../../config/config.php';

echo "<h2>🔧 ACTUALIZADOR DE WHATSAPP Y BANDERAS</h2>";
echo "<hr>";

if (isset($_POST['ejecutar_actualizacion'])) {
    try {
        $pdo = conectarDB();
        
        echo "<div style='background-color: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<h3>⚠️ EJECUTANDO ACTUALIZACIÓN...</h3>";
        echo "<p>Por favor espere mientras se actualizan las tablas...</p>";
        echo "</div>";
        
        // Leer el archivo SQL
        $sql_content = file_get_contents('../../actualizacion_whatsapp_banderas.sql');
        
        // Dividir en statements individuales
        $statements = explode(';', $sql_content);
        
        $ejecutados = 0;
        $errores = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            
            // Saltar comentarios y líneas vacías
            if (empty($statement) || 
                strpos($statement, '--') === 0 || 
                strpos($statement, '/*') === 0 ||
                strpos($statement, 'SELECT ') === 0) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
                $ejecutados++;
                echo "<p style='color: green;'>✅ Ejecutado: " . substr($statement, 0, 60) . "...</p>";
            } catch (Exception $e) {
                $errores++;
                echo "<p style='color: orange;'>⚠️ Ya existe o error: " . substr($statement, 0, 60) . "... - " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<div style='background-color: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin-top: 20px;'>";
        echo "<h3>📊 RESUMEN DE ACTUALIZACIÓN</h3>";
        echo "<p><strong>Statements ejecutados:</strong> $ejecutados</p>";
        echo "<p><strong>Warnings/Errores:</strong> $errores</p>";
        echo "</div>";
        
        // Verificar resultados
        echo "<hr>";
        echo "<h3>🔍 VERIFICACIÓN DE RESULTADOS</h3>";
        
        // Verificar campos en proveedores
        echo "<h4>Tabla Proveedores:</h4>";
        $stmt = $pdo->prepare("DESCRIBE proveedores");
        $stmt->execute();
        $campos_prov = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $tiene_whatsapp_prov = false;
        foreach ($campos_prov as $campo) {
            if (stripos($campo['Field'], 'whatsapp') !== false) {
                $tiene_whatsapp_prov = true;
                echo "<p style='color: green;'>✅ Campo WhatsApp encontrado: " . $campo['Field'] . " (" . $campo['Type'] . ")</p>";
            }
        }
        if (!$tiene_whatsapp_prov) {
            echo "<p style='color: red;'>❌ Campo WhatsApp NO encontrado en proveedores</p>";
        }
        
        // Verificar campos en clientes
        echo "<h4>Tabla Clientes:</h4>";
        $stmt = $pdo->prepare("DESCRIBE clientes");
        $stmt->execute();
        $campos_cli = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $tiene_whatsapp_cli = false;
        foreach ($campos_cli as $campo) {
            if (stripos($campo['Field'], 'whatsapp') !== false) {
                $tiene_whatsapp_cli = true;
                echo "<p style='color: green;'>✅ Campo WhatsApp encontrado: " . $campo['Field'] . " (" . $campo['Type'] . ")</p>";
            }
        }
        if (!$tiene_whatsapp_cli) {
            echo "<p style='color: red;'>❌ Campo WhatsApp NO encontrado en clientes</p>";
        }
        
        // Verificar campos en países
        echo "<h4>Tabla Países:</h4>";
        $stmt = $pdo->prepare("DESCRIBE paises");
        $stmt->execute();
        $campos_paises = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $campos_nuevos = ['bandera_emoji', 'codigo_iso2', 'codigo_iso3', 'codigo_telefono'];
        foreach ($campos_nuevos as $campo_buscar) {
            $encontrado = false;
            foreach ($campos_paises as $campo) {
                if ($campo['Field'] == $campo_buscar) {
                    $encontrado = true;
                    echo "<p style='color: green;'>✅ Campo $campo_buscar encontrado: " . $campo['Type'] . "</p>";
                    break;
                }
            }
            if (!$encontrado) {
                echo "<p style='color: red;'>❌ Campo $campo_buscar NO encontrado</p>";
            }
        }
        
        // Mostrar países con banderas
        echo "<h4>Países con Banderas:</h4>";
        $stmt = $pdo->prepare("SELECT nombre, bandera_emoji, codigo_iso2, codigo_telefono FROM paises WHERE codigo_iso2 IS NOT NULL ORDER BY nombre");
        $stmt->execute();
        $paises_banderas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($paises_banderas)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>País</th><th>Bandera</th><th>ISO2</th><th>Código Tel.</th></tr>";
            foreach ($paises_banderas as $pais) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($pais['nombre']) . "</td>";
                echo "<td style='font-size: 20px;'>" . $pais['bandera_emoji'] . "</td>";
                echo "<td>" . $pais['codigo_iso2'] . "</td>";
                echo "<td>" . $pais['codigo_telefono'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No se encontraron países con banderas configuradas</p>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background-color: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h3>❌ ERROR</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
    // Mostrar formulario de confirmación
    echo "<div style='background-color: #d1ecf1; padding: 20px; border: 1px solid #bee5eb; border-radius: 5px;'>";
    echo "<h3>📋 ACTUALIZACIÓN PENDIENTE</h3>";
    echo "<p>Esta actualización agregará los siguientes campos:</p>";
    echo "<ul>";
    echo "<li><strong>Proveedores:</strong> Campo 'whatsapp' (VARCHAR 20)</li>";
    echo "<li><strong>Clientes:</strong> Campo 'whatsapp' (VARCHAR 20)</li>";
    echo "<li><strong>Países:</strong> Campos 'bandera_emoji', 'codigo_iso2', 'codigo_iso3', 'codigo_telefono'</li>";
    echo "<li><strong>Validación:</strong> Triggers para validar formato WhatsApp</li>";
    echo "<li><strong>Datos:</strong> Banderas y códigos para países principales</li>";
    echo "</ul>";
    echo "<p><strong>⚠️ IMPORTANTE:</strong> Esta operación modificará la estructura de la base de datos.</p>";
    echo "</div>";
    
    echo "<form method='POST' style='margin-top: 20px;'>";
    echo "<button type='submit' name='ejecutar_actualizacion' style='background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🚀 EJECUTAR ACTUALIZACIÓN</button>";
    echo "</form>";
}

echo "<hr>";
echo "<p><a href='analisis_completo_whatsapp.php'>🔍 Ver análisis actual de tablas</a></p>";
echo "<p><a href='test_proveedores.php'>🔙 Volver a test proveedores</a></p>";
?>
