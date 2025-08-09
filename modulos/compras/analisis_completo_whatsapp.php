<?php
require_once '../../config/config.php';

try {
    $pdo = conectarDB();
    
    echo "<h2>🌍 ANÁLISIS COMPLETO DE GEOGRAFÍA Y WHATSAPP</h2>";
    echo "<hr>";
    
    // Verificar todas las tablas relacionadas
    $tablas_analizar = ['proveedores', 'clientes', 'paises', 'provincias', 'ciudades'];
    
    foreach($tablas_analizar as $tabla) {
        echo "<h3>📋 TABLA: " . strtoupper($tabla) . "</h3>";
        
        try {
            $stmt = $pdo->prepare("DESCRIBE $tabla");
            $stmt->execute();
            $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Análisis de campos específicos
            $campos_interes = [];
            foreach($campos as $campo) {
                $nombre_campo = strtolower($campo['Field']);
                if (
                    strpos($nombre_campo, 'whatsapp') !== false ||
                    strpos($nombre_campo, 'telefono') !== false ||
                    strpos($nombre_campo, 'celular') !== false ||
                    strpos($nombre_campo, 'bandera') !== false ||
                    strpos($nombre_campo, 'flag') !== false ||
                    strpos($nombre_campo, 'codigo') !== false ||
                    strpos($nombre_campo, 'iso') !== false
                ) {
                    $campos_interes[] = $campo;
                }
            }
            
            if (!empty($campos_interes)) {
                echo "<table border='1' style='border-collapse: collapse; margin-bottom: 15px;'>";
                echo "<tr style='background-color: #e8f5e8;'>";
                echo "<th>Campo</th><th>Tipo</th><th>Descripción</th>";
                echo "</tr>";
                
                foreach($campos_interes as $campo) {
                    echo "<tr>";
                    echo "<td><strong>" . $campo['Field'] . "</strong></td>";
                    echo "<td>" . $campo['Type'] . "</td>";
                    
                    $descripcion = "";
                    $nombre = strtolower($campo['Field']);
                    if (strpos($nombre, 'whatsapp') !== false) $descripcion = "📱 Campo WhatsApp";
                    elseif (strpos($nombre, 'telefono') !== false) $descripcion = "📞 Campo Teléfono";
                    elseif (strpos($nombre, 'celular') !== false) $descripcion = "📱 Campo Celular";
                    elseif (strpos($nombre, 'bandera') !== false || strpos($nombre, 'flag') !== false) $descripcion = "🏳️ Bandera del país";
                    elseif (strpos($nombre, 'codigo') !== false) $descripcion = "🔢 Código del país";
                    elseif (strpos($nombre, 'iso') !== false) $descripcion = "🌍 Código ISO país";
                    
                    echo "<td>" . $descripcion . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: #888;'>No se encontraron campos de WhatsApp, teléfono o bandera.</p>";
            }
            
            // Mostrar muestra de datos para paises
            if ($tabla == 'paises') {
                echo "<h4>📊 Muestra de datos (primeros 3):</h4>";
                $stmt = $pdo->prepare("SELECT * FROM $tabla LIMIT 3");
                $stmt->execute();
                $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($datos)) {
                    echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
                    echo "<tr style='background-color: #f0f0f0;'>";
                    foreach(array_keys($datos[0]) as $col) {
                        echo "<th>" . $col . "</th>";
                    }
                    echo "</tr>";
                    
                    foreach($datos as $fila) {
                        echo "<tr>";
                        foreach($fila as $valor) {
                            echo "<td>" . htmlspecialchars(substr($valor, 0, 30)) . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
            
        } catch(Exception $e) {
            echo "<p style='color: red;'>❌ Error consultando tabla $tabla: " . $e->getMessage() . "</p>";
        }
        
        echo "<hr>";
    }
    
    // Verificar si existe una tabla específica para códigos de país/teléfono
    echo "<h3>🔍 BÚSQUEDA DE TABLAS ADICIONALES</h3>";
    $stmt = $pdo->prepare("SHOW TABLES LIKE '%telefon%' OR SHOW TABLES LIKE '%whatsapp%' OR SHOW TABLES LIKE '%codigo%'");
    try {
        $stmt->execute();
        $tablas_extra = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($tablas_extra)) {
            echo "<p>📋 Tablas adicionales encontradas:</p>";
            foreach($tablas_extra as $tabla) {
                echo "<li>" . array_values($tabla)[0] . "</li>";
            }
        } else {
            echo "<p>No se encontraron tablas específicas para teléfonos/WhatsApp.</p>";
        }
    } catch(Exception $e) {
        // Intentar método alternativo
        $stmt = $pdo->prepare("SHOW TABLES");
        $stmt->execute();
        $todas_tablas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>📋 Todas las tablas en la base de datos:</p>";
        echo "<ul>";
        foreach($todas_tablas as $tabla) {
            $nombre_tabla = array_values($tabla)[0];
            echo "<li>" . $nombre_tabla;
            
            // Resaltar si tiene nombres relevantes
            $nombre_lower = strtolower($nombre_tabla);
            if (strpos($nombre_lower, 'telefon') !== false || 
                strpos($nombre_lower, 'whatsapp') !== false || 
                strpos($nombre_lower, 'codigo') !== false ||
                strpos($nombre_lower, 'pais') !== false) {
                echo " <strong style='color: blue;'>⭐ RELEVANTE</strong>";
            }
            echo "</li>";
        }
        echo "</ul>";
    }
    
} catch(Exception $e) {
    echo "❌ Error general: " . $e->getMessage();
}
?>
