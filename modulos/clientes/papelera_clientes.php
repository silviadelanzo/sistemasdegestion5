<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$usuario_id = $_SESSION['id_usuario'] ?? null;
$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol_usuario'] ?? 'inventario';
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

function mostrar_utf8($txt) {
    if (!mb_check_encoding($txt, 'UTF-8')) {
        return mb_convert_encoding($txt, 'UTF-8', 'ISO-8859-1');
    }
    return $txt;
}

$pdo = conectarDB();

// Obtener clientes en papelera
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE eliminado=1 ORDER BY fecha_eliminacion DESC");
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Papelera de Clientes";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<?php include "../../config/navbar_code.php"; ?>

<div class="container mt-5">
    <h1 class="mb-4"><i class="bi bi-trash"></i> Papelera de Clientes</h1>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'eliminado'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-trash-fill"></i> Cliente eliminado definitivamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (count($clientes) == 0): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No hay clientes en la papelera.
        </div>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-secondary">
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Contacto</th>
                    <th>Eliminado</th>
                    <th>Eliminado por</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td class="text-danger fw-bold"><?= htmlspecialchars(mostrar_utf8($cliente['codigo'])) ?></td>
                    <td>
                        <?= htmlspecialchars(mostrar_utf8($cliente['nombre'] . ' ' . $cliente['apellido'])) ?><br>
                        <?php if (!empty($cliente['empresa'])): ?>
                            <span class="text-muted small"><i class="bi bi-building"></i> <?= htmlspecialchars(mostrar_utf8($cliente['empresa'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-info"><?= htmlspecialchars(mostrar_utf8($cliente['tipo_cliente'])) ?></span>
                    </td>
                    <td>
                        <?php if (!empty($cliente['email'])): ?>
                            <i class="bi bi-envelope"></i> <?= htmlspecialchars(mostrar_utf8($cliente['email'])) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($cliente['telefono'])): ?>
                            <i class="bi bi-telephone"></i> <?= htmlspecialchars(mostrar_utf8($cliente['telefono'])) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($cliente['fecha_eliminacion'])): ?>
                            <i class="bi bi-calendar"></i> <?= htmlspecialchars($cliente['fecha_eliminacion']) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars(mostrar_utf8($cliente['eliminado_por'])) ?>
                    </td>
                    <td class="text-end">
                        <!-- Botón para restaurar cliente (opcional) -->
                        <a href="cliente_activar.php?id=<?= $cliente['id'] ?>"
                           class="btn btn-success btn-sm"
                           title="Restaurar cliente">
                            <i class="bi bi-arrow-repeat"></i>
                        </a>
                        <!-- Botón para eliminar definitivamente SIN confirm JS -->
                        <a href="eliminar_cliente_definitivo.php?id=<?= $cliente['id'] ?>"
                           class="btn btn-danger btn-sm"
                           title="Eliminar definitivamente">
                            <i class="bi bi-trash-fill"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
    <a href="clientes.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Volver al listado</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>