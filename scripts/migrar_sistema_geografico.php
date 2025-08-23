<?php
require_once '../config/config.php';

header('Content-Type: text/html; charset=UTF-8');

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "<h1>🌍 MIGRACIÓN SISTEMA GEOGRÁFICO</h1>";
    echo "<hr>";
    
    // 1. VERIFICAR ESTADO ACTUAL
    echo "<h2>📊 Estado Actual</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM paises WHERE activo = 1");
    $paisesActuales = $stmt->fetch()['total'];
    echo "<p>✅ Países actuales: <strong>$paisesActuales</strong></p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE pais IS NOT NULL");
    $clientesConPais = $stmt->fetch()['total'];
    echo "<p>📋 Clientes con país: <strong>$clientesConPais</strong></p>";
    
    // 2. LISTA DE PAÍSES A AGREGAR
    $paisesNuevos = [
        ['Bolivia', 'BOL', '+591'],
        ['Colombia', 'COL', '+57'],
        ['Costa Rica', 'CRI', '+506'],
        ['Ecuador', 'ECU', '+593'],
        ['El Salvador', 'SLV', '+503'],
        ['Guatemala', 'GTM', '+502'],
        ['Honduras', 'HND', '+504'],
        ['México', 'MEX', '+52'],
        ['Nicaragua', 'NIC', '+505'],
        ['Panamá', 'PAN', '+507'],
        ['Paraguay', 'PRY', '+595'],
        ['Perú', 'PER', '+51'],
        ['República Dominicana', 'DOM', '+1'],
        ['Venezuela', 'VEN', '+58'],
        ['Japón', 'JPN', '+81'],
        ['Francia', 'FRA', '+33'],
        ['Italia', 'ITA', '+39'],
        ['Alemania', 'DEU', '+49']
    ];
    
    echo "<h2>🚀 Ejecutando Migración</h2>";
    
    // 3. INSERTAR PAÍSES NUEVOS
    $insertados = 0;
    $stmt = $pdo->prepare("INSERT INTO paises (nombre, codigo_iso, codigo_telefono, activo) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE codigo_telefono = VALUES(codigo_telefono), activo = 1");
    
    foreach($paisesNuevos as $pais) {
        try {
            $stmt->execute($pais);
            if($stmt->rowCount() > 0) {
                echo "<p>➕ Agregado: <strong>{$pais[0]}</strong> ({$pais[2]})</p>";
                $insertados++;
            } else {
                echo "<p>ℹ️ Ya existe: {$pais[0]}</p>";
            }
        } catch(Exception $e) {
            echo "<p>❌ Error con {$pais[0]}: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p><strong>✅ Países insertados: $insertados</strong></p>";
    
    // 4. AGREGAR COLUMNAS FK A CLIENTES (SI NO EXISTEN)
    echo "<h2>🔄 Modificando Tabla Clientes</h2>";
    
    try {
        $pdo->exec("ALTER TABLE clientes ADD COLUMN pais_id INT(11) NULL AFTER pais");
        echo "<p>✅ Columna pais_id agregada</p>";
    } catch(Exception $e) {
        echo "<p>ℹ️ Columna pais_id ya existe</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE clientes ADD COLUMN provincia_id INT(11) NULL AFTER provincia");
        echo "<p>✅ Columna provincia_id agregada</p>";
    } catch(Exception $e) {
        echo "<p>ℹ️ Columna provincia_id ya existe</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE clientes ADD COLUMN ciudad_id INT(11) NULL AFTER ciudad");
        echo "<p>✅ Columna ciudad_id agregada</p>";
    } catch(Exception $e) {
        echo "<p>ℹ️ Columna ciudad_id ya existe</p>";
    }
    
    // 5. MIGRAR DATOS EXISTENTES
    echo "<h2>📝 Migrando Datos Existentes</h2>";
    
    $stmt = $pdo->exec("
        UPDATE clientes c
        JOIN paises p ON LOWER(TRIM(c.pais)) = LOWER(p.nombre)
        SET c.pais_id = p.id
        WHERE c.pais_id IS NULL AND c.pais IS NOT NULL AND c.pais != ''
    ");
    echo "<p>✅ Migración automática: <strong>$stmt registros</strong></p>";
    
    // Casos especiales
    $casosEspeciales = [
        ["Argentina", "%argent%"],
        ["España", "%espa%"],
        ["Estados Unidos", "%estados%"],
        ["Brasil", "%brasil%"],
        ["México", "%mexico%"],
        ["Chile", "%chile%"]
    ];
    
    foreach($casosEspeciales as $caso) {
        $stmt = $pdo->prepare("
            UPDATE clientes SET pais_id = (SELECT id FROM paises WHERE nombre = ?) 
            WHERE LOWER(pais) LIKE ? AND pais_id IS NULL
        ");
        $stmt->execute([$caso[0], $caso[1]]);
        
        if($stmt->rowCount() > 0) {
            echo "<p>🔧 Caso especial {$caso[0]}: {$stmt->rowCount()} registros</p>";
        }
    }
    
    // 6. REPORTE FINAL
    echo "<h2>📊 Reporte Final</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM paises WHERE activo = 1");
    $paisesFinales = $stmt->fetch()['total'];
    echo "<p>🌍 <strong>Total países: $paisesFinales</strong></p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE pais_id IS NOT NULL");
    $clientesMigrados = $stmt->fetch()['total'];
    echo "<p>✅ <strong>Clientes migrados: $clientesMigrados</strong></p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE pais_id IS NULL AND pais IS NOT NULL AND pais != ''");
    $clientesPendientes = $stmt->fetch()['total'];
    echo "<p>⚠️ <strong>Clientes pendientes: $clientesPendientes</strong></p>";
    
    // 7. LISTA FINAL DE PAÍSES
    echo "<h2>🌎 Países Disponibles</h2>";
    
    $stmt = $pdo->query("
        SELECT nombre, codigo_iso, codigo_telefono 
        FROM paises 
        WHERE activo = 1 
        ORDER BY 
            CASE 
                WHEN nombre = 'Argentina' THEN 1
                WHEN nombre = 'España' THEN 2
                WHEN nombre = 'México' THEN 3
                ELSE 4
            END,
            nombre
    ");
    
    $paises = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0;'>";
    foreach($paises as $pais) {
        $flag = '';
        switch($pais['nombre']) {
            case 'Argentina': $flag = '🇦🇷'; break;
            case 'España': $flag = '🇪🇸'; break;
            case 'México': $flag = '🇲🇽'; break;
            case 'Colombia': $flag = '🇨🇴'; break;
            case 'Chile': $flag = '🇨🇱'; break;
            case 'Perú': $flag = '🇵🇪'; break;
            case 'Brasil': $flag = '🇧🇷'; break;
            case 'Estados Unidos': $flag = '🇺🇸'; break;
            case 'China': $flag = '🇨🇳'; break;
            case 'Japón': $flag = '🇯🇵'; break;
            case 'Francia': $flag = '🇫🇷'; break;
            case 'Italia': $flag = '🇮🇹'; break;
            case 'Alemania': $flag = '🇩🇪'; break;
            default: $flag = '🌍';
        }
        
        echo "<div style='padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<strong>$flag {$pais['nombre']}</strong><br>";
        echo "<small>{$pais['codigo_telefono']} ({$pais['codigo_iso']})</small>";
        echo "</div>";
    }
    echo "</div>";
    
    // 8. PRÓXIMOS PASOS
    echo "<h2>🎯 Próximos Pasos</h2>";
    echo "<ol>";
    echo "<li><strong>Modificar cliente_form.php</strong> - Cambiar array por consulta BD</li>";
    echo "<li><strong>Unificar modales</strong> - Aplicar mismo sistema que proveedores</li>";
    echo "<li><strong>Sistema telefónico</strong> - $paisesFinales países con banderas</li>";
    echo "<li><strong>Testing completo</strong> - Verificar funcionamiento</li>";
    echo "</ol>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 ¡Migración Completada!</h3>";
    echo "<p>El sistema ahora tiene <strong>$paisesFinales países</strong> unificados con enfoque en mercado hispano + potencias comerciales.</p>";
    echo "<p>Los modales pueden usar la misma base de datos para total consistencia.</p>";
    echo "</div>";
    
} catch(Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error en Migración</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
