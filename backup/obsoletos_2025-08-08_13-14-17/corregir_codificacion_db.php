<?php
header('Content-Type: text/html; charset=UTF-8');

$conn = new mysqli('localhost', 'root', '', 'sistema_gestion');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// Configurar charset UTF-8
$conn->set_charset("utf8mb4");

echo '<h2>🔧 CORRECCIÓN DE CODIFICACIÓN UTF-8</h2>';

// Lista de países con caracteres especiales corregidos
$paises_corregidos = [
    'México' => ['nombre' => 'México', 'codigo_telefono' => '+52'],
    'España' => ['nombre' => 'España', 'codigo_telefono' => '+34'],
    'Panamá' => ['nombre' => 'Panamá', 'codigo_telefono' => '+507'],
    'Perú' => ['nombre' => 'Perú', 'codigo_telefono' => '+51'],
    'Japón' => ['nombre' => 'Japón', 'codigo_telefono' => '+81']
];

echo '<h3>📋 CORRIGIENDO PAÍSES...</h3>';
echo '<ul>';

foreach ($paises_corregidos as $original => $datos) {
    // Buscar país por código telefónico
    $stmt = $conn->prepare("SELECT id, nombre FROM paises WHERE codigo_telefono = ?");
    $stmt->bind_param("s", $datos['codigo_telefono']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $nombreActual = $row['nombre'];
        
        if ($nombreActual !== $datos['nombre']) {
            // Actualizar nombre
            $updateStmt = $conn->prepare("UPDATE paises SET nombre = ? WHERE id = ?");
            $updateStmt->bind_param("si", $datos['nombre'], $id);
            
            if ($updateStmt->execute()) {
                echo "<li>✅ <strong>{$datos['codigo_telefono']}</strong>: '$nombreActual' → '{$datos['nombre']}'</li>";
            } else {
                echo "<li>❌ Error actualizando {$datos['codigo_telefono']}: " . $updateStmt->error . "</li>";
            }
            $updateStmt->close();
        } else {
            echo "<li>✓ <strong>{$datos['codigo_telefono']}</strong>: '{$datos['nombre']}' ya está correcto</li>";
        }
    } else {
        echo "<li>⚠️ No se encontró país con código {$datos['codigo_telefono']}</li>";
    }
    $stmt->close();
}

echo '</ul>';

// Verificar provincias también
echo '<h3>📍 VERIFICANDO PROVINCIAS DE ARGENTINA...</h3>';
$provincias_argentina = [
    'Buenos Aires',
    'Ciudad Autónoma de Buenos Aires', 
    'Córdoba',
    'Mendoza',
    'Santa Fe'
];

$stmt = $conn->prepare("SELECT id, nombre FROM provincias WHERE pais_id = 1 LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();

echo '<ul>';
while ($row = $result->fetch_assoc()) {
    $nombre = $row['nombre'];
    $hex = bin2hex($nombre);
    echo "<li><strong>{$nombre}</strong> (ID: {$row['id']}) - Hex: $hex</li>";
}
echo '</ul>';

$stmt->close();

echo '<h3>🎯 CONFIGURACIÓN DE CHARSET:</h3>';
$charset = $conn->get_charset();
echo '<pre>';
print_r($charset);
echo '</pre>';

// Mostrar configuración de tabla
echo '<h3>⚙️ CONFIGURACIÓN DE TABLAS:</h3>';
$tables = ['paises', 'provincias', 'ciudades'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW CREATE TABLE $table");
    if ($row = $result->fetch_assoc()) {
        $createStatement = $row['Create Table'];
        if (strpos($createStatement, 'utf8mb4') !== false) {
            echo "<p>✅ <strong>$table</strong>: Usa UTF8MB4</p>";
        } elseif (strpos($createStatement, 'utf8') !== false) {
            echo "<p>⚠️ <strong>$table</strong>: Usa UTF8 (recomendado UTF8MB4)</p>";
        } else {
            echo "<p>❌ <strong>$table</strong>: No especifica charset UTF8</p>";
        }
    }
}

// Sugerir conversión si es necesaria
echo '<h3>🚀 RECOMENDACIÓN:</h3>';
echo '<p>Si hay problemas de codificación, ejecuta estos comandos SQL:</p>';
echo '<pre>';
echo "ALTER TABLE paises CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
echo "ALTER TABLE provincias CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
echo "ALTER TABLE ciudades CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
echo '</pre>';

$conn->close();
?>
