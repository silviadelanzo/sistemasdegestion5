<?php
// Archivo: modulos/Inventario/ajax_categorias.php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=UTF-8');
mb_internal_encoding('UTF-8');

// LOG rápido para ver qué llega
error_log('AJAX CATEGORIAS METHOD=' . ($_SERVER['REQUEST_METHOD'] ?? '') . ' BODY=' . http_build_query($_POST));

// Aceptar GET para destrabar (mapea GET -> POST)
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
  if ($method === 'GET') { $_POST = $_GET; }
  else {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Método no permitido']);
    exit;
  }
}

try {
  $accion = $_POST['accion'] ?? '';
  $nombre = trim($_POST['nombre_categoria'] ?? ($_POST['nombre'] ?? ''));

  if ($accion !== 'crear_simple') {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Acción no válida.']);
    exit;
  }

  if ($nombre === '') {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'El nombre de la categoría no puede estar vacío.']);
    exit;
  }

  $pdo = conectarDB();
  $pdo->exec("SET NAMES utf8mb4");

  // Duplicado
  $stmt = $pdo->prepare("SELECT id, nombre FROM categorias WHERE nombre = ? AND activo = 1 LIMIT 1");
  $stmt->execute([$nombre]);
  $existe = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($existe) {
    echo json_encode([
      'success' => true,
      'duplicado' => true,
      'message' => 'La categoría ya existe.',
      'categoria' => ['id' => (int)$existe['id'], 'nombre' => $existe['nombre']],
      'id' => (int)$existe['id'],
      'nombre' => $existe['nombre'],
    ]);
    exit;
  }

  // Insert
  $ins = $pdo->prepare("INSERT INTO categorias (nombre, activo, fecha_creacion) VALUES (?, 1, NOW())");
  $ins->execute([$nombre]);
  $id = (int)$pdo->lastInsertId();

  echo json_encode([
    'success' => true,
    'message' => 'Categoría creada correctamente.',
    'categoria' => ['id' => $id, 'nombre' => $nombre],
    'id' => $id,
    'nombre' => $nombre,
  ]);
} catch (Throwable $e) {
  error_log('ajax_categorias.php error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Error del servidor']);
}