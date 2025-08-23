<?php
require_once '../config/config.php';

try {
    $pdo = conectarDB();
    $stmt = $pdo->query('DESCRIBE productos');
    $columns = $stmt->fetchAll();
    
    echo "<h3>Columnas en tabla productos:</h3><ul>";
    foreach($columns as $col) {
        echo "<li><strong>{$col['Field']}</strong> ({$col['Type']})</li>";
    }
    echo "</ul>";
    
    // También verificar categorias y lugares
    echo "<h3>Categorias disponibles:</h3><ul>";
    $stmt = $pdo->query('SELECT id, nombre FROM categorias LIMIT 10');
    $cats = $stmt->fetchAll();
    foreach($cats as $cat) {
        echo "<li>ID: {$cat['id']} - {$cat['nombre']}</li>";
    }
    echo "</ul>";
    
    echo "<h3>Lugares disponibles:</h3><ul>";
    $stmt = $pdo->query('SELECT id, nombre FROM lugares LIMIT 10');
    $lugares = $stmt->fetchAll();
    foreach($lugares as $lugar) {
        echo "<li>ID: {$lugar['id']} - {$lugar['nombre']}</li>";
    }
    echo "</ul>";
    
} catch(Exception $e) {
    echo '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
}
?>
