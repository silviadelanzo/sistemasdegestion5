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

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Verificar si la columna eliminado existe, si no crearla
    $stmt = $pdo->query("SHOW COLUMNS FROM proveedores LIKE 'eliminado'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE proveedores ADD COLUMN eliminado TINYINT(1) DEFAULT 0");
        $pdo->exec("ALTER TABLE proveedores ADD COLUMN fecha_eliminacion DATETIME NULL");
        $pdo->exec("ALTER TABLE proveedores ADD COLUMN eliminado_por VARCHAR(100) NULL");
    }

    // Obtener proveedores en papelera
    $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE eliminado=1 ORDER BY fecha_eliminacion DESC");
    $stmt->execute();
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = "Error al cargar papelera: " . $e->getMessage();
    $proveedores = [];
}

$pageTitle = "Papelera de Proveedores - " . SISTEMA_NOMBRE;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .main-container {
            margin: 0 auto;
            max-width: 1200px;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-top: 30px;
        }
        .info-card-trash {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 4px 16px rgba(220, 53, 69, 0.3);
        }
        .btn-action {
            padding: 4px 8px;
            margin: 0 1px;
            border-radius: 5px;
            font-size: 0.85rem;
        }
        .search-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 15px 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include "../../config/navbar_code.php"; ?>

    <div class="main-container">
        <!-- Tarjeta de información -->
        <div class="info-card-trash">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="mb-1"><i class="bi bi-trash3-fill me-2"></i>Papelera de Proveedores</h3>
                    <p class="mb-0 opacity-75">Proveedores eliminados temporalmente</p>
                </div>
                <div class="text-end">
                    <h2 class="mb-0"><?= number_format(count($proveedores)) ?></h2>
                    <small>Elementos</small>
                </div>
            </div>
        </div>

        <!-- Barra de acciones -->
        <div class="search-section">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-danger">
                    <i class="bi bi-exclamation-triangle"></i> 
                    Estos proveedores fueron eliminados pero pueden ser restaurados
                </h5>
                <div>
                    <a href="proveedores.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Volver a Proveedores
                    </a>
                    <a href="../../menu_principal.php" class="btn btn-outline-secondary">
                        <i class="bi bi-house"></i> Menú
                    </a>
                </div>
            </div>
        </div>

        <!-- Tabla de proveedores eliminados -->
        <div class="table-container p-3">
            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] == 'restaurado'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i> Proveedor restaurado exitosamente.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['msg'] == 'eliminado_definitivo'): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-trash-fill"></i> Proveedor eliminado definitivamente.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 text-danger">
                    <i class="bi bi-list-ul me-2"></i>Proveedores en Papelera
                </h4>
            </div>

            <?php if (empty($proveedores)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-trash3 display-1 text-muted"></i>
                    <h3 class="text-muted mt-3">Papelera vacía</h3>
                    <p class="text-muted">No hay proveedores eliminados</p>
                    <a href="proveedores.php" class="btn btn-primary">
                        <i class="bi bi-building"></i> Ver Proveedores Activos
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-danger">
                            <tr>
                                <th>Código</th>
                                <th>Razón Social</th>
                                <th>Contacto</th>
                                <th>Eliminado</th>
                                <th>Eliminado por</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <tr>
                                    <td>
                                        <code class="text-danger fw-bold">
                                            <?= htmlspecialchars($proveedor['codigo'] ?? 'SIN-CODIGO-' . $proveedor['id']) ?>
                                        </code>
                                    </td>
                                    <td>
                                        <strong class="text-danger"><?= htmlspecialchars(mostrar_utf8($proveedor['razon_social'])) ?></strong>
                                        <?php if (!empty($proveedor['nombre_comercial'])): ?>
                                            <br><small class="text-muted">
                                                <i class="bi bi-building"></i> <?= htmlspecialchars(mostrar_utf8($proveedor['nombre_comercial'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($proveedor['email'])): ?>
                                            <i class="bi bi-envelope text-muted"></i> 
                                            <small><?= htmlspecialchars(mostrar_utf8($proveedor['email'])) ?></small><br>
                                        <?php endif; ?>
                                        <?php if (!empty($proveedor['telefono'])): ?>
                                            <i class="bi bi-telephone text-muted"></i> 
                                            <small><?= htmlspecialchars(mostrar_utf8($proveedor['telefono'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($proveedor['fecha_eliminacion'])): ?>
                                            <small>
                                                <i class="bi bi-calendar text-muted"></i> 
                                                <?= date('d/m/Y H:i', strtotime($proveedor['fecha_eliminacion'])) ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">Sin fecha</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> 
                                            <?= htmlspecialchars(mostrar_utf8($proveedor['eliminado_por'] ?? 'Sistema')) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <!-- Restaurar proveedor -->
                                            <button type="button" class="btn btn-success btn-action" 
                                                    onclick="restaurarProveedor(<?= $proveedor['id'] ?>)"
                                                    title="Restaurar Proveedor">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                            
                                            <!-- Eliminar definitivamente -->
                                            <?php if ($es_administrador): ?>
                                                <button type="button" class="btn btn-danger btn-action" 
                                                        onclick="eliminarDefinitivo(<?= $proveedor['id'] ?>)"
                                                        title="Eliminar Definitivamente">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function restaurarProveedor(id) {
            if (confirm('¿Está seguro de restaurar este proveedor?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'gestionar_proveedor.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'accion';
                actionInput.value = 'restaurar';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function eliminarDefinitivo(id) {
            if (confirm('⚠️ ¿ELIMINAR DEFINITIVAMENTE?\n\nEsta acción NO se puede deshacer.\nEl proveedor será eliminado permanentemente.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'gestionar_proveedor.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'accion';
                actionInput.value = 'eliminar_definitivo';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
