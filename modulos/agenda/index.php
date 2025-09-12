<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agenda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include "../../config/navbar_code.php"; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Agenda</h2>
        <a href="agenda_form.php" class="btn btn-primary">Nuevo Evento</a>
    </div>

    <p>Aquí se mostrará el calendario y la lista de eventos.</p>

    <?php
    $stmt = $pdo->query("SELECT a.*, u.username, c.nombre as cliente_nombre, c.apellido as cliente_apellido FROM agenda a LEFT JOIN usuarios u ON a.usuario_id = u.id LEFT JOIN clientes c ON a.cliente_id = c.id ORDER BY a.fecha_inicio ASC");
    $eventos = $stmt->fetchAll();
    ?>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Título</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Fecha de Inicio</th>
                <th>Fecha de Fin</th>
                <th>Asignado a</th>
                <th>Cliente</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($eventos)): ?>
                <tr>
                    <td colspan="8" class="text-center">No hay eventos en la agenda.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($eventos as $evento): ?>
                    <tr>
                        <td><?= htmlspecialchars($evento['titulo']) ?></td>
                        <td><?= htmlspecialchars($evento['tipo']) ?></td>
                        <td><?= htmlspecialchars($evento['estado']) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($evento['fecha_inicio']))) ?></td>
                        <td><?= $evento['fecha_fin'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($evento['fecha_fin']))) : '' ?></td>
                        <td><?= htmlspecialchars($evento['username'] ?? 'Nadie') ?></td>
                        <td><?= $evento['cliente_id'] ? htmlspecialchars($evento['cliente_apellido'] . ', ' . $evento['cliente_nombre']) : 'Ninguno' ?></td>
                        <td>
                            <a href="agenda_form.php?id=<?= $evento['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="eliminar_agenda.php?id=<?= $evento['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar este evento?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
