<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

echo "Sesión iniciada correctamente<br>";
echo "Usuario: " . ($_SESSION['nombre_usuario'] ?? 'No definido') . "<br>";
echo "Rol: " . ($_SESSION['rol_usuario'] ?? 'No definido') . "<br>";

$pdo = conectarDB();
echo "Base de datos conectada<br>";

// Probar consultas básicas
$proveedores = $pdo->query("SELECT COUNT(*) as total FROM proveedores")->fetch();
echo "Total proveedores: " . $proveedores['total'] . "<br>";

$paises = $pdo->query("SELECT COUNT(*) as total FROM paises")->fetch();
echo "Total países: " . $paises['total'] . "<br>";

echo "<br><a href='compra_form_new.php'>Ir al formulario de compras</a><br>";
echo "<a href='proveedores_new.php'>Ir a proveedores</a><br>";
?>
