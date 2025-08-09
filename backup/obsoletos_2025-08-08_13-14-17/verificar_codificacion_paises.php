<?php
header('Content-Type: text/html; charset=UTF-8');

$conn = new mysqli('localhost', 'root', '', 'sistema_gestion');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// Configurar charset UTF-8
$conn->set_charset("utf8");

echo '<h2>🔍 VERIFICACIÓN DE CODIFICACIÓN DE PAÍSES</h2>';
echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
echo '<tr><th>ID</th><th>Nombre</th><th>Código Teléfono</th><th>Bytes Nombre</th></tr>';

$result = $conn->query('SELECT * FROM paises ORDER BY nombre LIMIT 10');
while($row = $result->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . $row['id'] . '</td>';
    echo '<td>' . htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . $row['codigo_telefono'] . '</td>';
    echo '<td>' . bin2hex($row['nombre']) . '</td>';
    echo '</tr>';
}

echo '</table>';

echo '<h3>🔧 CHARSET DE LA CONEXIÓN:</h3>';
$charset = $conn->get_charset();
echo '<pre>';
print_r($charset);
echo '</pre>';

$conn->close();
?>
