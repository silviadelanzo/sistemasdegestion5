<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

$agenda_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$es_edicion = $agenda_id > 0;

$item = [];
if ($es_edicion) {
    $stmt = $pdo->prepare("SELECT * FROM agenda WHERE id = ?");
    $stmt->execute([$agenda_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
}

$usuarios_stmt = $pdo->query("SELECT id, username FROM usuarios ORDER BY username");
$usuarios = $usuarios_stmt->fetchAll();

$clientes_stmt = $pdo->query("SELECT id, nombre, apellido FROM clientes ORDER BY apellido, nombre");
$clientes = $clientes_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $es_edicion ? 'Editar' : 'Nuevo' ?> Evento de Agenda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .form-control-custom {
            background-color: #e0f7fa;
        }
        .form-group-compact .mb-3 {
            margin-bottom: 0.5rem !important;
        }
        .form-group-compact label {
            margin-bottom: 0.2rem !important;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<?php include "../../config/navbar_code.php"; ?>
<div class="container mt-4">
    <div class="card mx-auto" style="max-width: 800px;">
        <div class="card-header">
            <h2 class="mb-0"><?= $es_edicion ? 'Editar' : 'Nuevo' ?> Evento de Agenda</h2>
        </div>
        <div class="card-body form-group-compact">
            <form action="guardar_agenda.php" method="POST">
                <input type="hidden" name="id" value="<?= $agenda_id ?>">

                <div class="mb-3">
                    <label for="titulo" class="form-label">Título</label>
                    <input type="text" class="form-control form-control-custom" id="titulo" name="titulo" value="<?= htmlspecialchars($item['titulo'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control form-control-custom" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($item['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select class="form-select form-control-custom" id="tipo" name="tipo" required>
                            <option value="evento" <?= (($item['tipo'] ?? '') == 'evento') ? 'selected' : '' ?>>Evento</option>
                            <option value="tarea" <?= (($item['tipo'] ?? '') == 'tarea') ? 'selected' : '' ?>>Tarea</option>
                            <option value="alerta" <?= (($item['tipo'] ?? '') == 'alerta') ? 'selected' : '' ?>>Alerta</option>
                            <option value="recordatorio" <?= (($item['tipo'] ?? '') == 'recordatorio') ? 'selected' : '' ?>>Recordatorio</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select form-control-custom" id="estado" name="estado" required>
                            <option value="pendiente" <?= (($item['estado'] ?? '') == 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                            <option value="en_progreso" <?= (($item['estado'] ?? '') == 'en_progreso') ? 'selected' : '' ?>>En Progreso</option>
                            <option value="completada" <?= (($item['estado'] ?? '') == 'completada') ? 'selected' : '' ?>>Completada</option>
                            <option value="cancelada" <?= (($item['estado'] ?? '') == 'cancelada') ? 'selected' : '' ?>>Cancelada</option>
                            <option value="pospuesta" <?= (($item['estado'] ?? '') == 'pospuesta') ? 'selected' : '' ?>>Pospuesta</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                        <input type="datetime-local" class="form-control form-control-custom" id="fecha_inicio" name="fecha_inicio" value="<?= !empty($item['fecha_inicio']) ? date('Y-m-d\TH:i', strtotime($item['fecha_inicio'])) : '' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                        <input type="datetime-local" class="form-control form-control-custom" id="fecha_fin" name="fecha_fin" value="<?= !empty($item['fecha_fin']) ? date('Y-m-d\TH:i', strtotime($item['fecha_fin'])) : '' ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="prioridad" class="form-label">Prioridad</label>
                        <select class="form-select form-control-custom" id="prioridad" name="prioridad" required>
                            <option value="baja" <?= (($item['prioridad'] ?? '') == 'baja') ? 'selected' : '' ?>>Baja</option>
                            <option value="normal" <?= (($item['prioridad'] ?? 'normal') == 'normal') ? 'selected' : '' ?>>Normal</option>
                            <option value="alta" <?= (($item['prioridad'] ?? '') == 'alta') ? 'selected' : '' ?>>Alta</option>
                            <option value="urgente" <?= (($item['prioridad'] ?? '') == 'urgente') ? 'selected' : '' ?>>Urgente</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="usuario_id" class="form-label">Asignado a</label>
                        <select class="form-select form-control-custom" id="usuario_id" name="usuario_id">
                            <option value="">Nadie</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?= $usuario['id'] ?>" <?= (($item['usuario_id'] ?? '') == $usuario['id']) ? 'selected' : '' ?>><?= htmlspecialchars($usuario['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="cliente_id" class="form-label">Cliente Asociado</label>
                        <select class="form-select form-control-custom" id="cliente_id" name="cliente_id">
                            <option value="">Ninguno</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>" <?= (($item['cliente_id'] ?? '') == $cliente['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cliente['apellido'] . ', ' . $cliente['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="index.php" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>