<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$usuario_id = $_SESSION['id_usuario'] ?? null;
$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';

function mostrar_utf8($txt) {
    if (!mb_check_encoding($txt, 'UTF-8')) {
        return mb_convert_encoding($txt, 'UTF-8', 'ISO-8859-1');
    }
    return $txt;
}

$pdo = conectarDB();
$cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$cliente = null;
$pedidos_pendientes = 0;

if ($cliente_id <= 0) {
    $error = "ID de cliente inválido.";
} else {
    // Buscar cliente inactivo y no eliminado
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id=? AND eliminado=0 AND activo=0");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $error = "Cliente no encontrado, ya está activo o eliminado.";
    } else {
        // Verificar si tiene pedidos pendientes (opcional, si quieres impedir activación con pedidos pendientes)
        // Descomenta si lo necesitas:
        /*
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE cliente_id=? AND estado='pendiente'");
        $stmt2->execute([$cliente_id]);
        $pedidos_pendientes = $stmt2->fetchColumn();
        if ($pedidos_pendientes > 0) {
            $error = "No se puede activar: el cliente tiene $pedidos_pendientes pedido(s) pendiente(s).";
        }
        */
    }
}

// Procesar confirmación de activación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $cliente) {
    try {
        $stmt = $pdo->prepare("UPDATE clientes SET activo=1, fecha_modificacion=NOW(), modificado_por=? WHERE id=?");
        $stmt->execute([$usuario_id, $cliente_id]);
        header("Location: clientes.php?msg=activado");
        exit;
    } catch (Exception $e) {
        $error = "Error al activar el cliente: " . $e->getMessage();
    }
}

$pageTitle = "Activar Cliente";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include "../../config/navbar_code.php"; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars(mostrar_utf8($error)) ?>
            </div>
            <a href="clientes_inactivos.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Volver al listado</a>
        <?php elseif ($cliente): ?>
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-person-check"></i> Activar cliente</h5>
                </div>
                <div class="card-body">
                    <p>¿Está seguro que desea activar el siguiente cliente?</p>
                    <ul>
                        <li><b><?= htmlspecialchars(mostrar_utf8($cliente['nombre'] . ' ' . $cliente['apellido'])) ?></b></li>
                        <li>Código: <code><?= htmlspecialchars(mostrar_utf8($cliente['codigo'])) ?></code></li>
                        <?php if (!empty($cliente['empresa'])): ?>
                        <li>Empresa: <?= htmlspecialchars(mostrar_utf8($cliente['empresa'])) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($cliente['email'])): ?>
                        <li>Email: <?= htmlspecialchars(mostrar_utf8($cliente['email'])) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($cliente['telefono'])): ?>
                        <li>Teléfono: <?= htmlspecialchars(mostrar_utf8($cliente['telefono'])) ?></li>
                        <?php endif; ?>
                    </ul>
                    <form method="post">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-person-check"></i> Activar cliente
                        </button>
                        <a href="clientes_inactivos.php" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Mensaje en clientes.php si fue activado -->
<?php if (isset($_GET['msg']) && $_GET['msg'] == 'activado'): ?>
<script>
    window.onload = function() {
        let alerta = document.createElement('div');
        alerta.className = 'alert alert-success alert-dismissible fade show position-fixed';
        alerta.style = 'top:70px;right:20px;z-index:1056;min-width:300px;';
        alerta.role = 'alert';
        alerta.innerHTML = '<i class="bi bi-person-check me-2"></i>Cliente activado correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.body.appendChild(alerta);
        setTimeout(() => alerta.remove(), 5000);
    }
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>