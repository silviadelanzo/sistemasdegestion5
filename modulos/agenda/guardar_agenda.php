<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $tipo = $_POST['tipo'] ?? 'evento';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $estado = $_POST['estado'] ?? 'pendiente';
    $prioridad = $_POST['prioridad'] ?? 'normal';
    $usuario_id = $_POST['usuario_id'] ?: null;
    $cliente_id = $_POST['cliente_id'] ?: null;

    if (empty($titulo) || empty($fecha_inicio)) {
        // Manejo de error simple
        header('Location: index.php?error=faltan_datos');
        exit;
    }

    if ($id) {
        // Actualizar
        $sql = "UPDATE agenda SET titulo = ?, descripcion = ?, tipo = ?, fecha_inicio = ?, fecha_fin = ?, estado = ?, prioridad = ?, usuario_id = ?, cliente_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $descripcion, $tipo, $fecha_inicio, $fecha_fin, $estado, $prioridad, $usuario_id, $cliente_id, $id]);
        registrar_auditoria('MODIFICACION_AGENDA', 'agenda', $id, "Evento/Tarea modificado: " . $titulo);
    } else {
        // Insertar
        $sql = "INSERT INTO agenda (titulo, descripcion, tipo, fecha_inicio, fecha_fin, estado, prioridad, usuario_id, cliente_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $descripcion, $tipo, $fecha_inicio, $fecha_fin, $estado, $prioridad, $usuario_id, $cliente_id]);
        $id = $pdo->lastInsertId();
        registrar_auditoria('ALTA_AGENDA', 'agenda', $id, "Evento/Tarea creado: " . $titulo);
    }

    header('Location: index.php?exito=1');
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>