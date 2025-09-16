<?php
session_start(); // <-- AÑADIDO: Iniciar sesión para acceder a la cuenta
require_once '../../config/config.php';
header('Content-Type: application/json; charset=UTF-8');
mb_internal_encoding('UTF-8');

$pdo = conectarDB();
$errores = [];
$res = ["success" => false, "errores" => [], "producto_id" => null];

// --- AÑADIDO: Validar cuenta_id de la sesión ---
if (!isset($_SESSION['cuenta_id']) || empty($_SESSION['cuenta_id'])) {
    $res['errores'][] = 'Error de sesión: La cuenta no está definida. Por favor, inicie sesión de nuevo.';
    echo json_encode($res);
    exit;
}
$cuenta_id = (int)$_SESSION['cuenta_id'];
// --- FIN AÑADIDO ---

// Recibir datos por POST (AJAX)
$producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
$codigo = trim($_POST['codigo'] ?? '');
$codigo_barras = trim($_POST['codigo_barras'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$categoria_id = $_POST['categoria_id'] ?? null;
$lugar_id = $_POST['lugar_id'] ?? null;
$unidad_medida = $_POST['unidad_medida'] ?? 'UN';
$publicar_web = isset($_POST['publicar_web']) ? 1 : 0;

if ($codigo === '') $errores[] = 'El código interno es obligatorio.';
if ($codigo_barras === '') $errores[] = 'El código de barras es obligatorio.';
if ($nombre === '') $errores[] = 'El nombre es obligatorio.';

// --- MODIFICADO: Validar duplicados dentro de la misma cuenta ---
if (!$producto_id && $codigo) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE codigo = ? AND cuenta_id = ?');
    $stmt->execute([$codigo, $cuenta_id]);
    if ($stmt->fetchColumn() > 0) $errores[] = 'El código interno ya existe.';
}
if ($codigo_barras) {
    $query = 'SELECT COUNT(*) FROM productos WHERE codigo_barras = ? AND cuenta_id = ?';
    $params = [$codigo_barras, $cuenta_id];
    if ($producto_id) {
        $query .= ' AND id != ?';
        $params[] = $producto_id;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    if ($stmt->fetchColumn() > 0) $errores[] = 'El código de barras ya existe.';
}
// --- FIN MODIFICADO ---

if ($errores) {
    $res['errores'] = $errores;
    echo json_encode($res);
    exit;
}

try {
    if ($producto_id > 0) {
        // --- MODIFICADO: UPDATE parcial con seguridad de cuenta ---
        $sql = "UPDATE productos SET codigo=?, codigo_barras=?, nombre=?, descripcion=?, categoria_id=?, lugar_id=?, unidad_medida=?, publicar_web=? WHERE id=? AND cuenta_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$codigo, $codigo_barras, $nombre, $descripcion, $categoria_id, $lugar_id, $unidad_medida, $publicar_web, $producto_id, $cuenta_id]);
    } else {
        // --- MODIFICADO: INSERT parcial con cuenta_id ---
        $sql = "INSERT INTO productos (cuenta_id, codigo, codigo_barras, nombre, descripcion, categoria_id, lugar_id, unidad_medida, publicar_web) VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cuenta_id, $codigo, $codigo_barras, $nombre, $descripcion, $categoria_id, $lugar_id, $unidad_medida, $publicar_web]);
        $producto_id = $pdo->lastInsertId();
    }
    $res['success'] = true;
    $res['producto_id'] = $producto_id;
} catch (PDOException $e) {
    // Devolver el error real de la base de datos para depuración
    $res['errores'][] = 'Error al guardar: ' . $e->getMessage();
}

echo json_encode($res);
