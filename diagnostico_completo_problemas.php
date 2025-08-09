<?php
require_once 'config/config.php';

echo "<h1>🔍 DIAGNÓSTICO COMPLETO - PROBLEMAS REPORTADOS</h1>";

try {
    $pdo = conectarDB();
    
    echo "<h2>1️⃣ VERIFICACIÓN DE CÓDIGOS DE PRODUCTOS</h2>";
    
    // Obtener los últimos 10 productos
    $stmt = $pdo->query("SELECT id, codigo_interno, nombre FROM productos ORDER BY id DESC LIMIT 10");
    $productos = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Código Interno</th><th>Nombre</th></tr>";
    
    $ultimo_id = 0;
    foreach ($productos as $producto) {
        echo "<tr>";
        echo "<td><strong>{$producto['id']}</strong></td>";
        echo "<td>{$producto['codigo_interno']}</td>";
        echo "<td>{$producto['nombre']}</td>";
        echo "</tr>";
        if ($producto['id'] > $ultimo_id) {
            $ultimo_id = $producto['id'];
        }
    }
    echo "</table>";
    
    // Calcular el próximo código
    $proximo_id = $ultimo_id + 1;
    $proximo_codigo = 'PROD-' . str_pad($proximo_id, 7, '0', STR_PAD_LEFT);
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📊 ANÁLISIS DE SECUENCIA:</h3>";
    echo "<p><strong>Último ID en base:</strong> $ultimo_id</p>";
    echo "<p><strong>Próximo código correcto:</strong> <span style='color: red; font-size: 18px; font-weight: bold;'>$proximo_codigo</span></p>";
    echo "<p><strong>Código reportado por usuario:</strong> PROD-0000066</p>";
    
    if ($proximo_codigo === 'PROD-0000066') {
        echo "<p style='color: green; font-weight: bold;'>✅ EL CÓDIGO ES CORRECTO</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ EL CÓDIGO NO COINCIDE - Debería ser: $proximo_codigo</p>";
    }
    echo "</div>";
    
    echo "<h2>2️⃣ VERIFICACIÓN DEL NAVBAR</h2>";
    
    // Verificar si el archivo navbar_code.php existe y su contenido
    $navbar_path = 'config/navbar_code.php';
    if (file_exists($navbar_path)) {
        echo "<p style='color: green;'>✅ Archivo navbar_code.php existe</p>";
        
        $navbar_content = file_get_contents($navbar_path);
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>Contenido del navbar_code.php:</h4>";
        echo "<pre style='max-height: 200px; overflow-y: auto;'>" . htmlspecialchars($navbar_content) . "</pre>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ Archivo navbar_code.php NO existe en $navbar_path</p>";
    }
    
    // Verificar cómo se incluye en producto_form.php
    $form_path = 'modulos/Inventario/producto_form.php';
    if (file_exists($form_path)) {
        $form_content = file_get_contents($form_path);
        if (strpos($form_content, 'navbar_code.php') !== false) {
            echo "<p style='color: green;'>✅ producto_form.php incluye navbar_code.php</p>";
            
            // Extraer la línea de inclusión
            $lines = explode("\n", $form_content);
            foreach ($lines as $i => $line) {
                if (strpos($line, 'navbar_code.php') !== false) {
                    echo "<p><strong>Línea " . ($i+1) . ":</strong> <code>" . htmlspecialchars(trim($line)) . "</code></p>";
                    break;
                }
            }
        } else {
            echo "<p style='color: red;'>❌ producto_form.php NO incluye navbar_code.php</p>";
        }
    }
    
    echo "<h2>3️⃣ VERIFICACIÓN DE PESTAÑAS INDEPENDIENTES</h2>";
    
    // Verificar el JavaScript de las pestañas
    if (file_exists($form_path)) {
        $form_content = file_get_contents($form_path);
        
        if (strpos($form_content, 'bootstrap') !== false) {
            echo "<p style='color: green;'>✅ Bootstrap está incluido</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No se detecta Bootstrap</p>";
        }
        
        if (strpos($form_content, 'data-bs-toggle="tab"') !== false) {
            echo "<p style='color: green;'>✅ Pestañas con data-bs-toggle detectadas</p>";
        } else {
            echo "<p style='color: red;'>❌ No se detectan pestañas con data-bs-toggle</p>";
        }
        
        // Buscar JavaScript personalizado que pueda interferir
        if (strpos($form_content, 'tabClicked') !== false) {
            echo "<p style='color: red;'>❌ Se detecta JavaScript personalizado 'tabClicked' que puede interferir</p>";
        }
        
        if (strpos($form_content, 'preventDefault') !== false) {
            echo "<p style='color: red;'>❌ Se detecta preventDefault que puede bloquear las pestañas</p>";
        }
    }
    
    echo "<h2>🔧 SOLUCIONES RECOMENDADAS</h2>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Para el navbar:</h3>";
    echo "<p>1. Verificar que la ruta de inclusión sea correcta</p>";
    echo "<p>2. Usar comillas dobles en lugar de simples</p>";
    echo "<p>3. Verificar permisos del archivo</p>";
    echo "</div>";
    
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Para las pestañas:</h3>";
    echo "<p>1. Eliminar JavaScript personalizado que interfiera</p>";
    echo "<p>2. Usar solo Bootstrap nativo para las pestañas</p>";
    echo "<p>3. Asegurar que data-bs-toggle='tab' esté en cada pestaña</p>";
    echo "</div>";
    
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Para el código secuencial:</h3>";
    echo "<p>El código $proximo_codigo " . ($proximo_codigo === 'PROD-0000066' ? 'ES CORRECTO' : 'NECESITA CORRECCIÓN') . "</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ ERROR DE BASE DE DATOS:</h3>";
    echo "<p style='color: red; background: #f8d7da; padding: 10px;'>" . $e->getMessage() . "</p>";
}
?>
