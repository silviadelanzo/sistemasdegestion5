<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$usuario_id = $_SESSION['id_usuario'] ?? null;

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

// 1. Verificar que el cliente exista y esté en papelera (eliminado=1)
if ($cliente_id <= 0) {
    $error = "ID de cliente inválido.";
} else {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id=? AND eliminado=1");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $error = "Cliente no encontrado o no está en papelera.";
    } else {
        // CHEQUEO DE SEGURIDAD MULTI-CUENTA
        if (isset($_SESSION['cuenta_id']) && $cliente['cuenta_id'] != $_SESSION['cuenta_id']) {
            error_log("ALERTA DE SEGURIDAD: Usuario ID {$_SESSION['id_usuario']} de cuenta ID {$_SESSION['cuenta_id']} intentó borrar al cliente ID {$cliente_id} de cuenta ID {$cliente['cuenta_id']}.");
            $error = "Cliente no encontrado o no está en papelera."; // Error genérico
            $cliente = null; // Anular el cliente para que no se muestre la confirmación
        }
    }
}

// 2. Al confirmar (POST), eliminar definitivamente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $cliente) {
    try {
                $stmt = $pdo->prepare("DELETE FROM clientes WHERE id=? AND cuenta_id = ?");
        $stmt->execute([$cliente_id, $_SESSION['cuenta_id']]);
        header("Location: papelera_clientes.php?msg=eliminado");
        exit;
    } catch (Exception $e) {
        $error = "Error al eliminar definitivamente: " . $e->getMessage();
    }
}

$pageTitle = "Eliminar Cliente Definitivamente";
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
        <div class="col-lg-7 col-md-9">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars(mostrar_utf8($error)) ?>
            </div>
            <a href="papelera_clientes.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Volver a la papelera</a>
        <?php elseif ($cliente): ?>
            <div class="card shadow border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-trash-fill"></i> Eliminar cliente definitivamente</h5>
                </div>
                <div class="card-body">
                    <p class="text-danger fw-bold">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        ¿Está seguro que desea eliminar <u>definitivamente</u> el siguiente cliente?<br>
                        <span class="fw-semibold text-dark">Esta acción <b>no se puede deshacer</b>.</span>
                    </p>
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
                    <form method="post" class="mt-3">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash-fill"></i> Eliminar definitivamente
                        </button>
                        <a href="papelera_clientes.php" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'eliminado'): ?>
<script>
    window.onload = function() {
        let alerta = document.createElement('div');
        alerta.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        alerta.style = 'top:70px;right:20px;z-index:1056;min-width:300px;';
        alerta.role = 'alert';
        alerta.innerHTML = '<i class="bi bi-trash-fill me-2"></i>Cliente eliminado definitivamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.body.appendChild(alerta);
        setTimeout(() => alerta.remove(), 5000);
    }
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>