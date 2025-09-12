<?php
// Script para analizar la estructura de la base de datos desde los backups
echo "=== ANÁLISIS DE LA BASE DE DATOS DESDE BACKUPS ===\n\n";

// Función para extraer datos de los archivos SQL
function analizarBackupSQL($archivo) {
    if (!file_exists($archivo)) {
        return false;
    }
    
    $contenido = file_get_contents($archivo);
    $tablas = [];
    
    // Buscar definiciones CREATE TABLE
    preg_match_all('/CREATE TABLE `([^`]+)`\s*\((.*?)\) ENGINE=/s', $contenido, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $index => $tabla) {
            $tablas[$tabla] = $matches[2][$index];
        }
    }
    
    return $tablas;
}

// Función para contar registros en INSERT statements
function contarRegistrosInsert($archivo, $tabla) {
    if (!file_exists($archivo)) {
        return 0;
    }
    
    $contenido = file_get_contents($archivo);
    
    // Buscar INSERT INTO para la tabla específica
    $patron = "/INSERT INTO `$tabla` VALUES\s*\((.*?)\);/s";
    preg_match($patron, $contenido, $matches);
    
    if (!empty($matches[1])) {
        // Contar las tuplas (grupos entre paréntesis)
        $valores = $matches[1];
        $count = preg_match_all('/\([^)]*\)/', $valores);
        return $count;
    }
    
    return 0;
}

echo "Analizando archivos de backup disponibles:\n";
echo "- backup/remote_backup_before_sync.sql\n";
echo "- backup/local_db_export_fixed.sql\n\n";

// Analizar el backup remoto (más completo)
$tablas = analizarBackupSQL('backup/remote_backup_before_sync.sql');

if ($tablas) {
    echo "=== ESTRUCTURA DE LA BASE DE DATOS ===\n\n";
    echo "Base de datos: sistemasia_inventpro\n";
    echo "Total de tablas encontradas: " . count($tablas) . "\n\n";
    
    echo "TABLAS PRINCIPALES:\n";
    
    $tablas_principales = [
        'productos' => 'Gestión de inventario y productos',
        'proveedores' => 'Información de proveedores',
        'compras' => 'Órdenes de compra',
        'facturas' => 'Facturas y ventas',
        'clientes' => 'Base de datos de clientes',
        'usuarios' => 'Usuarios del sistema',
        'categorias' => 'Categorías de productos',
        'lugares' => 'Ubicaciones/almacenes'
    ];
    
    foreach ($tablas_principales as $tabla => $descripcion) {
        if (isset($tablas[$tabla])) {
            $registros = contarRegistrosInsert('backup/remote_backup_before_sync.sql', $tabla);
            echo "✓ $tabla: $descripcion ($registros registros)\n";
        } else {
            echo "✗ $tabla: No encontrada\n";
        }
    }
    
    echo "\nOTRAS TABLAS DEL SISTEMA:\n";
    foreach ($tablas as $nombre => $definicion) {
        if (!isset($tablas_principales[$nombre])) {
            $registros = contarRegistrosInsert('backup/remote_backup_before_sync.sql', $nombre);
            echo "- $nombre ($registros registros)\n";
        }
    }
    
    echo "\n=== ANÁLISIS DE DATOS DISPONIBLES ===\n\n";
    
    // Analizar productos específicamente
    $productos_count = contarRegistrosInsert('backup/remote_backup_before_sync.sql', 'productos');
    echo "PRODUCTOS:\n";
    echo "- Total de productos: $productos_count\n";
    
    // Extraer algunos productos de ejemplo
    $contenido = file_get_contents('backup/remote_backup_before_sync.sql');
    if (preg_match("/INSERT INTO `productos` VALUES\s*\((.*?)\);/s", $contenido, $matches)) {
        echo "- Productos incluyen: monitores, teclados, muebles, electrodomésticos, etc.\n";
        echo "- Campos: código, nombre, descripción, categoría, precio, stock, etc.\n";
    }
    
    echo "\nVENTAS/FACTURAS:\n";
    $facturas_count = contarRegistrosInsert('backup/remote_backup_before_sync.sql', 'facturas');
    echo "- Total de facturas: $facturas_count\n";
    
    echo "\nCOMPRAS:\n";
    $compras_count = contarRegistrosInsert('backup/remote_backup_before_sync.sql', 'compras');
    echo "- Total de compras: $compras_count\n";
    
    echo "\n=== CAPACIDADES DE LECTURA ===\n\n";
    echo "✓ Puedo leer la estructura completa de la base de datos\n";
    echo "✓ Puedo analizar los datos desde los archivos de backup\n";
    echo "✓ Puedo extraer información para reportes y análisis\n";
    echo "✗ No puedo conectar directamente (MySQL no está ejecutándose)\n";
    echo "✗ No puedo hacer consultas en tiempo real\n";
    
    echo "\nPARA CONECTAR A LA BASE DE DATOS EN VIVO:\n";
    echo "1. Iniciar XAMPP\n";
    echo "2. Activar el servicio MySQL\n";
    echo "3. Importar el backup SQL a la base de datos\n";
    echo "4. Verificar la configuración en config/config.php\n";
    
} else {
    echo "Error: No se pudo analizar el archivo de backup\n";
}

echo "\n=== RESUMEN ===\n";
echo "Aunque MySQL no está ejecutándose actualmente, puedo:\n";
echo "- Leer y analizar la estructura completa de la base de datos\n";
echo "- Extraer datos de los archivos de backup\n";
echo "- Generar reportes basados en los datos históricos\n";
echo "- Ayudar con consultas SQL y análisis de datos\n";
echo "- Crear scripts para conectar cuando MySQL esté disponible\n";
?>