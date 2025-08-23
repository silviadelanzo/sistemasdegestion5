<?php
require_once '../config/config.php';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Obtener estructura de la tabla productos
    $sql = "DESCRIBE productos";
    $result = $pdo->query($sql);

    echo "Estructura de la tabla productos:\n";
    echo "================================\n";

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf(
            "%-20s %-15s %-10s\n",
            $row['Field'],
            $row['Type'],
            $row['Null']
        );
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
