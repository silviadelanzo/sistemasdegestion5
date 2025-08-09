<?php
// Script para verificar y crear tablas de monedas e impuestos
require_once 'config/config.php';

echo "<h1>🔧 Verificación y Creación de Tablas del Sistema</h1>";

try {
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
            echo "<p style='color: red;'>❌ Tabla '$tabla' NO existe</p>";
        }
    }
    
    // Crear tablas faltantes
    if (count($tablas_existentes) < count($tablas_requeridas)) {
        echo "<h3>🔨 Creando tablas faltantes...</h3>";
        
        // Crear tabla países si no existe
        if (!in_array('paises', $tablas_existentes)) {
            $sql_paises = "
            CREATE TABLE IF NOT EXISTS paises (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                codigo_iso VARCHAR(3) NOT NULL UNIQUE,
                activo TINYINT(1) DEFAULT 1,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($sql_paises);
            echo "<p style='color: green;'>✅ Tabla 'paises' creada</p>";
            
            // Insertar datos básicos
            $pdo->exec("INSERT IGNORE INTO paises (nombre, codigo_iso) VALUES 
                       ('Argentina', 'ARG'), ('Uruguay', 'URY'), ('Brasil', 'BRA'), ('Paraguay', 'PRY')");
        }
        
        // Crear tabla monedas si no existe
        if (!in_array('monedas', $tablas_existentes)) {
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
            echo "<p style='color: green;'>✅ Tabla 'monedas' creada</p>";
            
            // Insertar monedas básicas
            $pdo->exec("INSERT IGNORE INTO monedas (pais_id, nombre, codigo_iso, simbolo, es_principal) VALUES 
                       (1, 'Peso Argentino', 'ARS', '$', 1),
                       (2, 'Peso Uruguayo', 'UYU', '$U', 1),
                       (3, 'Real Brasileño', 'BRL', 'R$', 1),
                       (4, 'Guaraní Paraguayo', 'PYG', '₲', 1)");
        }
        
        // Crear tabla impuestos si no existe
        if (!in_array('impuestos', $tablas_existentes)) {
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
            echo "<p style='color: green;'>✅ Tabla 'impuestos' creada</p>";
            
            // Insertar impuestos básicos
            $pdo->exec("INSERT IGNORE INTO impuestos (pais_id, nombre, porcentaje, tipo) VALUES 
                       (1, 'IVA Argentina 21%', 21.00, 'iva'),
                       (1, 'IVA Argentina 10.5%', 10.50, 'iva'),
                       (2, 'IVA Uruguay 22%', 22.00, 'iva'),
                       (3, 'ICMS Brasil 18%', 18.00, 'iva'),
                       (4, 'IVA Paraguay 10%', 10.00, 'iva')");
        }
        
        // Verificar si la tabla productos necesita actualizarse
        echo "<h3>🔄 Verificando estructura de tabla productos...</h3>";
        try {
            $stmt = $pdo->query("DESCRIBE productos");
            $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $columnas_nuevas = ['moneda_id', 'impuesto_id', 'redondeo_decimales', 'tipo_redondeo', 
                               'utilidad_minorista', 'utilidad_mayorista', 'usar_control_stock', 
                               'usar_alerta_vencimiento', 'fecha_vencimiento', 'alerta_vencimiento_dias'];
            
            foreach ($columnas_nuevas as $columna) {
                if (!in_array($columna, $columnas)) {
                    switch ($columna) {
                        case 'moneda_id':
                            $pdo->exec("ALTER TABLE productos ADD COLUMN moneda_id INT NULL, ADD FOREIGN KEY (moneda_id) REFERENCES monedas(id)");
                            echo "<p style='color: green;'>✅ Columna 'moneda_id' agregada</p>";
                            break;
                        case 'impuesto_id':
                            $pdo->exec("ALTER TABLE productos ADD COLUMN impuesto_id INT NULL, ADD FOREIGN KEY (impuesto_id) REFERENCES impuestos(id)");
                            echo "<p style='color: green;'>✅ Columna 'impuesto_id' agregada</p>";
                            break;
                        case 'redondeo_decimales':
                            $pdo->exec("ALTER TABLE productos ADD COLUMN redondeo_decimales INT DEFAULT 2");
                            echo "<p style='color: green;'>✅ Columna 'redondeo_decimales' agregada</p>";
                            break;
                        case 'tipo_redondeo':
                            $pdo->exec("ALTER TABLE productos ADD COLUMN tipo_redondeo ENUM('centavo', 'peso', 'cinco_pesos') DEFAULT 'centavo'");
                            echo "<p style='color: green;'>✅ Columna 'tipo_redondeo' agregada</p>";
                            break;
                        case 'utilidad_minorista':
                            $pdo->exec("ALTER TABLE productos ADD COLUMN utilidad_minorista DECIMAL(5,2) DEFAULT 0.00");
                            echo "<p style='color: green;'>✅ Columna 'utilidad_minorista' agregada</p>";
                            break;
                        case 'utilidad_mayorista':
                            $pdo->exec("ALTER TABLE productos ADD COLUMN utilidad_mayorista DECIMAL(5,2) DEFAULT 0.00");
                            echo "<p style='color: green;'>✅ Columna 'utilidad_mayorista' agregada</p>";
                            break;
                        case 'usar_control_stock':
                            $pdo->exec("ALTER TABLE productos ADD COLUMN usar_control_stock TINYINT(1) DEFAULT 1");
                            echo "<p style='color: green;'>✅ Columna 'usar_control_stock' agregada</p>";
                            break;
                        case 'usar_alerta_vencimiento':
                            $pdo->exec("ALTER TABLE productos ADD COLUMN usar_alerta_vencimiento TINYINT(1) DEFAULT 0");
                            echo "<p style='color: green;'>✅ Columna 'usar_alerta_vencimiento' agregada</p>";
                            break;
                        case 'fecha_vencimiento':
                            $pdo->exec("ALTER TABLE productos ADD COLUMN fecha_vencimiento DATE NULL");
                            echo "<p style='color: green;'>✅ Columna 'fecha_vencimiento' agregada</p>";
                            break;
                        case 'alerta_vencimiento_dias':
                            $pdo->exec("ALTER TABLE productos ADD COLUMN alerta_vencimiento_dias INT DEFAULT 30");
                            echo "<p style='color: green;'>✅ Columna 'alerta_vencimiento_dias' agregada</p>";
                            break;
                    }
                } else {
                    echo "<p style='color: blue;'>ℹ️ Columna '$columna' ya existe</p>";
                }
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error al verificar productos: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>🎉 Proceso completado exitosamente</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ Sistema listo para usar</h4>";
    echo "<p>Todas las tablas necesarias han sido creadas y configuradas.</p>";
    echo "<p><a href='modulos/Inventario/producto_form.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Ir al Formulario de Productos</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error en la base de datos</h3>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error general</h3>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
