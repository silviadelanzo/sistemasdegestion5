<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

// Probar consulta simple
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM proveedores");
    $stmt->execute();
    $total = $stmt->fetch()['total'];
    echo "Total proveedores: " . $total . "<br>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM paises");
    $stmt->execute();
    $total_paises = $stmt->fetch()['total'];
    echo "Total países: " . $total_paises . "<br>";
    
    echo "<hr>";
    echo "<h3>Prueba consulta completa:</h3>";
    
    $sql = "SELECT p.*, 
                   pa.nombre as pais_nombre,
                   pr.nombre as provincia_nombre,
                   c.nombre as ciudad_nombre
            FROM proveedores p
            LEFT JOIN paises pa ON p.pais_id = pa.id
            LEFT JOIN provincias pr ON p.provincia_id = pr.id
            LEFT JOIN ciudades c ON p.ciudad_id = c.id
            LIMIT 3";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Razón Social</th><th>País</th><th>Provincia</th><th>Ciudad</th></tr>";
    foreach($proveedores as $p) {
        echo "<tr>";
        echo "<td>" . $p['id'] . "</td>";
        echo "<td>" . $p['razon_social'] . "</td>";
        echo "<td>" . ($p['pais_nombre'] ?? 'Sin país') . "</td>";
        echo "<td>" . ($p['provincia_nombre'] ?? 'Sin provincia') . "</td>";
        echo "<td>" . ($p['ciudad_nombre'] ?? 'Sin ciudad') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><a href='proveedores_new.php'>Ir a proveedores_new.php</a>";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
