<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin();

$fechaSeleccionada = $_GET['fecha'] ?? date('Y-m-d');

$pdo = conectarDB();

// Fetch events for the selected date
$sql = "SELECT id, titulo, descripcion, fecha_inicio, fecha_fin, tipo, prioridad, completada FROM agenda 
        WHERE DATE(fecha_inicio) = :fecha ORDER BY fecha_inicio ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['fecha' => $fechaSeleccionada]);
$eventosDelDia = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<script type="application/json" id="eventosData">
    <?php echo json_encode($eventosDelDia); ?>
</script>
<style>
    .form-control-custom {
        background-color: #e0f7fa; /* Light blue background */
    }
    .form-group-compact .mb-2 {
        margin-bottom: 0.3rem !important; /* Further reduce spacing */
    }
    .form-group-compact label {
        margin-bottom: 0.1rem !important; /* Reduce spacing for labels */
        font-size: 0.85rem; /* Smaller font for labels */
    }
    .event-card-compact .card-body {
        padding: 0.5rem; /* Reduce padding in event cards */
    }
    .event-card-compact .card-text {
        margin-bottom: 0.2rem; /* Reduce margin for text in event cards */
    }
    .event-card-compact .card-title {
        margin-bottom: 0.3rem; /* Reduce margin for title in event cards */
        font-size: 0.95rem;
    }
</style>
<h5 class="mb-3">Eventos para el día: <?php echo htmlspecialchars($fechaSeleccionada); ?></h5>

<?php if (empty($eventosDelDia)): ?>
    <div class="alert alert-info" role="alert">
        No hay eventos programados para este día.
    </div>
<?php else: ?>
    <?php foreach ($eventosDelDia as $evento): ?>
        <div class="card event-card event-card-compact mb-2" id="evento-<?php echo $evento['id']; ?>">
            <div class="card-body">
                <h6 class="card-title"><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                <p class="card-text"><small class="text-muted">Tipo: <?php echo htmlspecialchars($evento['tipo']); ?> | Prioridad: <?php echo htmlspecialchars($evento['prioridad']); ?></small></p>
                <p class="card-text"><small class="text-muted">Inicio: <?php echo htmlspecialchars($evento['fecha_inicio']); ?> | Fin: <?php echo htmlspecialchars($evento['fecha_fin']); ?></small></p>
                <p class="card-text"><small class="text-muted">Completada: <?php echo $evento['completada'] ? 'Sí' : 'No'; ?></small></p>
                <a href="#" class="btn btn-sm btn-primary btn-editar" data-id="<?php echo $evento['id']; ?>">Editar</a>
                <a href="#" class="btn btn-sm btn-danger btn-eliminar" data-id="<?php echo $evento['id']; ?>">Eliminar</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<hr>
<h5 class="mb-3" id="form-title">Agregar Nuevo Evento</h5>
<form id="addEventForm" action="modulos/agenda/guardar_evento.php" method="POST">
    <input type="hidden" name="id" id="evento_id">
    <input type="hidden" name="fechaSeleccionada" value="<?php echo htmlspecialchars($fechaSeleccionada); ?>">
    <div class="mb-2 form-group-compact">
        <label for="titulo" class="form-label">Título</label>
        <input type="text" class="form-control form-control-custom" id="titulo" name="titulo" required>
    </div>
    <div class="mb-2 form-group-compact">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea class="form-control form-control-custom" id="descripcion" name="descripcion" rows="2"></textarea>
    </div>
    <div class="mb-2 form-group-compact">
        <label for="tipo" class="form-label">Tipo</label>
        <select class="form-select form-control-custom" id="tipo" name="tipo">
            <option value="reunion">Reunión</option>
            <option value="tarea">Tarea</option>
            <option value="alerta">Alerta</option>
        </select>
    </div>
    <div class="mb-2 form-group-compact">
        <label for="prioridad" class="form-label">Prioridad</label>
        <select class="form-select form-control-custom" id="prioridad" name="prioridad">
            <option value="alta">Alta</option>
            <option value="media">Media</option>
            <option value="baja">Baja</option>
        </select>
    </div>
    <div class="mb-2 form-group-compact">
        <label for="fecha_inicio" class="form-label">Fecha y Hora de Inicio</label>
        <input type="datetime-local" class="form-control form-control-custom" id="fecha_inicio" name="fecha_inicio" value="<?php echo date('Y-m-d', strtotime($fechaSeleccionada)) . 'T' . date('H:i'); ?>" required>
    </div>
    <div class="mb-2 form-group-compact">
        <label for="fecha_fin" class="form-label">Fecha y Hora de Fin</label>
        <input type="datetime-local" class="form-control form-control-custom" id="fecha_fin" name="fecha_fin" value="<?php echo date('Y-m-d', strtotime($fechaSeleccionada)) . 'T' . date('H:i'); ?>" required>
    </div>
    <div class="mb-2 form-check form-group-compact">
        <input type="checkbox" class="form-check-input" id="completada" name="completada" value="1">
        <label class="form-check-label" for="completada">Completada</label>
    </div>
    <div class="d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-success me-2">Guardar Evento</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
    </div>
</form>