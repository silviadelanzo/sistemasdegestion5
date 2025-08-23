<?php
require_once '../../config/config.php';
header('Content-Type: application/json; charset=UTF-8');
mb_internal_encoding('UTF-8');

$pdo = conectarDB();
$errores = [];
$res = ["success" => false, "errores" => [], "producto_id" => null];

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

// Validar duplicados
if (!$producto_id && $codigo) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE codigo = ?');
    $stmt->execute([$codigo]);
    if ($stmt->fetchColumn() > 0) $errores[] = 'El código interno ya existe.';
}
if ($codigo_barras) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE codigo_barras = ?' . ($producto_id ? ' AND id != ?' : ''));
    $stmt->execute($producto_id ? [$codigo_barras, $producto_id] : [$codigo_barras]);
    if ($stmt->fetchColumn() > 0) $errores[] = 'El código de barras ya existe.';
}

if ($errores) {
    $res['errores'] = $errores;
    echo json_encode($res);
    exit;
}

try {
    if ($producto_id > 0) {
        // UPDATE parcial
        $sql = "UPDATE productos SET codigo=?, codigo_barras=?, nombre=?, descripcion=?, categoria_id=?, lugar_id=?, unidad_medida=?, publicar_web=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$codigo, $codigo_barras, $nombre, $descripcion, $categoria_id, $lugar_id, $unidad_medida, $publicar_web, $producto_id]);
    } else {
        // INSERT parcial
        $sql = "INSERT INTO productos (codigo, codigo_barras, nombre, descripcion, categoria_id, lugar_id, unidad_medida, publicar_web) VALUES (?,?,?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$codigo, $codigo_barras, $nombre, $descripcion, $categoria_id, $lugar_id, $unidad_medida, $publicar_web]);
        $producto_id = $pdo->lastInsertId();
    }
    $res['success'] = true;
    $res['producto_id'] = $producto_id;
} catch (PDOException $e) {
    $res['errores'][] = 'Error al guardar: ' . $e->getMessage();
}

echo json_encode($res);
