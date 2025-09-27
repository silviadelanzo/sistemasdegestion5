<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin();
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['nombre'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre requerido']);
    exit;
}

$nombre = trim($_POST['nombre']);
if (strlen($nombre) < 2) {
    echo json_encode(['error' => 'El nombre debe tener al menos 2 caracteres']);
    exit;
}

$cuenta_id = $_SESSION['cuenta_id'] ?? 0;
if (!$cuenta_id) {
    echo json_encode(['error' => 'Sesión inválida']);
    exit;
}

try {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = ? AND cuenta_id = ?");
    $stmt->execute([$nombre, $cuenta_id]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'La categoría ya existe en tu cuenta']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO categorias (cuenta_id, nombre) VALUES (?, ?)");
    $stmt->execute([$cuenta_id, $nombre]);
    $nuevo_id = $pdo->lastInsertId();

    echo json_encode(['id' => (int)$nuevo_id, 'nombre' => $nombre]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear categoría']);
}