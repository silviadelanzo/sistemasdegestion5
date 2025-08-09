<?php
require_once '../../config/config.php';

try {
    $pdo = conectarDB();
    
    echo "<h2>📊 ANÁLISIS DE ESTRUCTURA DE TABLAS</h2>";
    echo "<hr>";
    
    // 1. Estructura tabla proveedores
    echo "<h3>1️⃣ TABLA PROVEEDORES</h3>";
    $stmt = $pdo->prepare("DESCRIBE proveedores");
    $stmt->execute();
    $campos_proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    $tiene_whatsapp_proveedores = false;
    foreach($campos_proveedores as $campo) {
        echo "<tr>";
        echo "<td><strong>" . $campo['Field'] . "</strong></td>";
        echo "<td>" . $campo['Type'] . "</td>";
        echo "<td>" . $campo['Null'] . "</td>";
        echo "<td>" . $campo['Key'] . "</td>";
        echo "<td>" . $campo['Default'] . "</td>";
        echo "<td>" . $campo['Extra'] . "</td>";
        echo "</tr>";
        
        if (stripos($campo['Field'], 'whatsapp') !== false) {
            $tiene_whatsapp_proveedores = true;
        }
    }
    echo "</table>";
    
    echo "<p><strong>¿Tiene campo WhatsApp?</strong> " . ($tiene_whatsapp_proveedores ? "✅ SÍ" : "❌ NO") . "</p>";
    
    echo "<hr>";
    
    // 2. Estructura tabla clientes
    echo "<h3>2️⃣ TABLA CLIENTES</h3>";
    $stmt = $pdo->prepare("DESCRIBE clientes");
    $stmt->execute();
    $campos_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    $tiene_whatsapp_clientes = false;
    foreach($campos_clientes as $campo) {
        echo "<tr>";
        echo "<td><strong>" . $campo['Field'] . "</strong></td>";
        echo "<td>" . $campo['Type'] . "</td>";
        echo "<td>" . $campo['Null'] . "</td>";
        echo "<td>" . $campo['Key'] . "</td>";
        echo "<td>" . $campo['Default'] . "</td>";
        echo "<td>" . $campo['Extra'] . "</td>";
        echo "</tr>";
        
        if (stripos($campo['Field'], 'whatsapp') !== false) {
            $tiene_whatsapp_clientes = true;
        }
    }
    echo "</table>";
    
    echo "<p><strong>¿Tiene campo WhatsApp?</strong> " . ($tiene_whatsapp_clientes ? "✅ SÍ" : "❌ NO") . "</p>";
    
    echo "<hr>";
    
    // 3. Tabla países (para verificar si hay campo bandera)
    echo "<h3>3️⃣ TABLA PAÍSES</h3>";
    $stmt = $pdo->prepare("DESCRIBE paises");
    $stmt->execute();
    $campos_paises = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    $tiene_bandera = false;
    $tiene_codigo_pais = false;
    foreach($campos_paises as $campo) {
        echo "<tr>";
        echo "<td><strong>" . $campo['Field'] . "</strong></td>";
        echo "<td>" . $campo['Type'] . "</td>";
        echo "<td>" . $campo['Null'] . "</td>";
        echo "<td>" . $campo['Key'] . "</td>";
        echo "<td>" . $campo['Default'] . "</td>";
        echo "<td>" . $campo['Extra'] . "</td>";
        echo "</tr>";
        
        if (stripos($campo['Field'], 'bandera') !== false || stripos($campo['Field'], 'flag') !== false) {
            $tiene_bandera = true;
        }
        if (stripos($campo['Field'], 'codigo') !== false) {
            $tiene_codigo_pais = true;
        }
    }
    echo "</table>";
    
    echo "<p><strong>¿Tiene campo bandera?</strong> " . ($tiene_bandera ? "✅ SÍ" : "❌ NO") . "</p>";
    echo "<p><strong>¿Tiene código país?</strong> " . ($tiene_codigo_pais ? "✅ SÍ" : "❌ NO") . "</p>";
    
    echo "<hr>";
    
    // 4. Muestra de datos de países
    echo "<h3>4️⃣ MUESTRA DE PAÍSES (primeros 5)</h3>";
    $stmt = $pdo->prepare("SELECT * FROM paises LIMIT 5");
    $stmt->execute();
    $paises_muestra = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($paises_muestra)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        foreach(array_keys($paises_muestra[0]) as $columna) {
            echo "<th>" . $columna . "</th>";
        }
        echo "</tr>";
        
        foreach($paises_muestra as $pais) {
            echo "<tr>";
            foreach($pais as $valor) {
                echo "<td>" . htmlspecialchars($valor) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    
    // 5. Resumen de análisis
    echo "<h3>📋 RESUMEN DEL ANÁLISIS</h3>";
    echo "<div style='background-color: #f9f9f9; padding: 15px; border-left: 4px solid #007cba;'>";
    echo "<p><strong>1. Proveedores:</strong> " . ($tiene_whatsapp_proveedores ? "✅ Tiene WhatsApp" : "❌ Falta campo WhatsApp") . "</p>";
    echo "<p><strong>2. Clientes:</strong> " . ($tiene_whatsapp_clientes ? "✅ Tiene WhatsApp" : "❌ Falta campo WhatsApp") . "</p>";
    echo "<p><strong>3. Países:</strong> " . ($tiene_bandera ? "✅ Tiene bandera" : "❌ Falta campo bandera") . " | " . ($tiene_codigo_pais ? "✅ Tiene código" : "❌ Falta código país") . "</p>";
    echo "</div>";
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
