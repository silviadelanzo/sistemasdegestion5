<?php
// Script para crear las tablas de monedas e impuestos
require_once '../config/config.php';

echo "<h1>🔧 Creación de Tablas del Sistema</h1>";

try {
    // Usar la función de conexión correcta
    $pdo = conectarDB();
    
    echo "<h3>📊 Verificando tablas existentes...</h3>";
    
    // Verificar qué tablas existen
    $tablas_requeridas = ['paises', 'monedas', 'impuestos'];
    $tablas_existentes = [];
    
    foreach ($tablas_requeridas as $tabla) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $tabla LIMIT 1");
            $tablas_existentes[] = $tabla;
            echo "<p style='color: green;'>✅ Tabla '$tabla' existe</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Tabla '$tabla' NO existe - Será creada</p>";
        }
    }
    
    echo "<h3>🔨 Creando/Verificando tablas...</h3>";
    
    // Crear tabla países
    $sql_paises = "
    CREATE TABLE IF NOT EXISTS paises (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        codigo_iso VARCHAR(3) NOT NULL UNIQUE,
        activo TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_paises);
    echo "<p style='color: green;'>✅ Tabla 'paises' verificada/creada</p>";
    
    // Insertar países básicos
    $stmt_paises = $pdo->prepare("INSERT IGNORE INTO paises (nombre, codigo_iso) VALUES (?, ?)");
    $paises_data = [
        ['Argentina', 'ARG'],
        ['Uruguay', 'URY'], 
        ['Brasil', 'BRA'],
        ['Paraguay', 'PRY']
    ];
    
    foreach ($paises_data as $pais) {
        $stmt_paises->execute($pais);
    }
    echo "<p style='color: blue;'>ℹ️ Países básicos insertados</p>";
    
    // Crear tabla monedas
    $sql_monedas = "
    CREATE TABLE IF NOT EXISTS monedas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pais_id INT NOT NULL,
        nombre VARCHAR(50) NOT NULL,
        codigo_iso VARCHAR(3) NOT NULL,
        simbolo VARCHAR(5) NOT NULL,
        tasa_cambio DECIMAL(10,4) DEFAULT 1.0000,
        es_principal TINYINT(1) DEFAULT 0,
        activo TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pais_id) REFERENCES paises(id)
    )";
    $pdo->exec($sql_monedas);
    echo "<p style='color: green;'>✅ Tabla 'monedas' verificada/creada</p>";
    
    // Insertar monedas básicas
    $stmt_monedas = $pdo->prepare("INSERT IGNORE INTO monedas (pais_id, nombre, codigo_iso, simbolo, es_principal) VALUES (?, ?, ?, ?, ?)");
    $monedas_data = [
        [1, 'Peso Argentino', 'ARS', '$', 1],
        [2, 'Peso Uruguayo', 'UYU', '$U', 1],
        [3, 'Real Brasileño', 'BRL', 'R$', 1],
        [4, 'Guaraní Paraguayo', 'PYG', '₲', 1]
    ];
    
    foreach ($monedas_data as $moneda) {
        $stmt_monedas->execute($moneda);
    }
    echo "<p style='color: blue;'>ℹ️ Monedas básicas insertadas</p>";
    
    // Crear tabla impuestos
    $sql_impuestos = "
    CREATE TABLE IF NOT EXISTS impuestos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pais_id INT NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        porcentaje DECIMAL(5,2) NOT NULL,
        tipo ENUM('iva', 'ieps', 'otros') DEFAULT 'iva',
        activo TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pais_id) REFERENCES paises(id)
    )";
    $pdo->exec($sql_impuestos);
    echo "<p style='color: green;'>✅ Tabla 'impuestos' verificada/creada</p>";
    
    // Insertar impuestos básicos
    $stmt_impuestos = $pdo->prepare("INSERT IGNORE INTO impuestos (pais_id, nombre, porcentaje, tipo) VALUES (?, ?, ?, ?)");
    $impuestos_data = [
        [1, 'IVA Argentina 21%', 21.00, 'iva'],
        [1, 'IVA Argentina 10.5%', 10.50, 'iva'],
        [2, 'IVA Uruguay 22%', 22.00, 'iva'],
        [3, 'ICMS Brasil 18%', 18.00, 'iva'],
        [4, 'IVA Paraguay 10%', 10.00, 'iva']
    ];
    
    foreach ($impuestos_data as $impuesto) {
        $stmt_impuestos->execute($impuesto);
    }
    echo "<p style='color: blue;'>ℹ️ Impuestos básicos insertados</p>";
    
    // Verificar y actualizar tabla productos
    echo "<h3>🔄 Actualizando tabla productos...</h3>";
    
    // Obtener columnas existentes
    $stmt = $pdo->query("DESCRIBE productos");
    $columnas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $nuevas_columnas = [
        'moneda_id' => "ADD COLUMN moneda_id INT NULL",
        'impuesto_id' => "ADD COLUMN impuesto_id INT NULL", 
        'redondeo_decimales' => "ADD COLUMN redondeo_decimales INT DEFAULT 2",
        'tipo_redondeo' => "ADD COLUMN tipo_redondeo ENUM('centavo', 'peso', 'cinco_pesos') DEFAULT 'centavo'",
        'utilidad_minorista' => "ADD COLUMN utilidad_minorista DECIMAL(5,2) DEFAULT 0.00",
        'utilidad_mayorista' => "ADD COLUMN utilidad_mayorista DECIMAL(5,2) DEFAULT 0.00",
        'usar_control_stock' => "ADD COLUMN usar_control_stock TINYINT(1) DEFAULT 1",
        'usar_alerta_vencimiento' => "ADD COLUMN usar_alerta_vencimiento TINYINT(1) DEFAULT 0",
        'fecha_vencimiento' => "ADD COLUMN fecha_vencimiento DATE NULL",
        'alerta_vencimiento_dias' => "ADD COLUMN alerta_vencimiento_dias INT DEFAULT 30"
    ];
    
    foreach ($nuevas_columnas as $columna => $sql_alter) {
        if (!in_array($columna, $columnas_existentes)) {
            try {
                $pdo->exec("ALTER TABLE productos $sql_alter");
                echo "<p style='color: green;'>✅ Columna '$columna' agregada a productos</p>";
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>⚠️ Error agregando '$columna': " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Columna '$columna' ya existe en productos</p>";
        }
    }
    
    // Agregar foreign keys si no existen
    try {
        $pdo->exec("ALTER TABLE productos ADD CONSTRAINT fk_productos_moneda FOREIGN KEY (moneda_id) REFERENCES monedas(id)");
        echo "<p style='color: green;'>✅ Foreign key moneda_id agregada</p>";
    } catch (PDOException $e) {
        echo "<p style='color: blue;'>ℹ️ Foreign key moneda_id ya existe o no se pudo agregar</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE productos ADD CONSTRAINT fk_productos_impuesto FOREIGN KEY (impuesto_id) REFERENCES impuestos(id)");
        echo "<p style='color: green;'>✅ Foreign key impuesto_id agregada</p>";
    } catch (PDOException $e) {
        echo "<p style='color: blue;'>ℹ️ Foreign key impuesto_id ya existe o no se pudo agregar</p>";
    }
    
    echo "<h3>🎉 ¡PROCESO COMPLETADO EXITOSAMENTE!</h3>";
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4 style='color: #155724;'>✅ Sistema listo para usar</h4>";
    echo "<p><strong>Tablas creadas:</strong> paises, monedas, impuestos</p>";
    echo "<p><strong>Tabla actualizada:</strong> productos (con nuevas columnas)</p>";
    echo "<p><strong>Datos insertados:</strong> Países, monedas e impuestos de Argentina, Uruguay, Brasil y Paraguay</p>";
    echo "<br>";
    echo "<a href='modulos/Inventario/producto_form.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🚀 PROBAR FORMULARIO DE PRODUCTOS</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error de base de datos</h3>";
    echo "<p style='color: red; background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine();
    echo "</p>";
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error general</h3>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
