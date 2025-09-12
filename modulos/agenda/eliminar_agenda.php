<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("SELECT titulo FROM agenda WHERE id = ?");
    $stmt->execute([$id]);
    $titulo = $stmt->fetchColumn();

    $delete_stmt = $pdo->prepare("DELETE FROM agenda WHERE id = ?");
    $delete_stmt->execute([$id]);

    registrar_auditoria('ELIMINACION_AGENDA', 'agenda', $id, "Evento/Tarea eliminado: " . $titulo);

    header('Location: index.php?exito_delete=1');
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>