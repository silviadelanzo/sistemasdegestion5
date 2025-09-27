<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$filtro_busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$orden_campo = isset($_GET['orden']) ? $_GET['orden'] : 'razon_social';
$orden_direccion = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
$campos_permitidos = ['codigo', 'razon_social', 'email', 'telefono', 'fecha_creacion'];
if (!in_array($orden_campo, $campos_permitidos)) {
    $orden_campo = 'razon_social';
}
try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    // Filtros: solo inactivos y no eliminados
    $where_conditions = ['activo = 0', 'eliminado = 0'];
    $params = [];
    if ($filtro_busqueda !== '') {
        $where_conditions[] = "(codigo LIKE ? OR razon_social LIKE ? OR email LIKE ? OR telefono LIKE ?)";
        $busqueda = "%{$filtro_busqueda}%";
        array_push($params, $busqueda, $busqueda, $busqueda, $busqueda);
    }
    $where_clause = implode(' AND ', $where_conditions);
    $orden_sql = $orden_campo;
    $sql = "SELECT * FROM proveedores WHERE $where_clause
            ORDER BY $orden_sql $orden_direccion
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count_sql = "SELECT COUNT(*) FROM proveedores WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    $total_pages = ceil($total / $per_page);
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
    $proveedores = [];
    $total_pages = 1;
}
$pageTitle = "Proveedores Inactivos - " . SISTEMA_NOMBRE;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .main-container { max-width: 1000px; margin: 0 auto; }
        .table-container, .search-section { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-top: 16px; padding: 10px 12px; }
        .btn-action { padding: 4px 8px; margin: 0 1px; border-radius: 5px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <?php include "../../config/navbar_code.php"; ?>
    <div class="main-container">
        <div class="search-section">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input type="text" class="form-control" name="busqueda" placeholder="Buscar por código, razón social..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
                </div>
            </form>
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <a href="proveedores.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Volver a Proveedores Activos
                    </a>
                </div>
            </div>
        </div>
        <div class="table-container p-3">
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'reactivado'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> Proveedor reactivado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="bi bi-archive me-2"></i>Proveedores Inactivos</h4>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Razón Social</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($proveedores)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="text-muted mt-2">No hay proveedores inactivos</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($proveedores as $p): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($p['codigo'] ?? 'SIN-CODIGO-' . $p['id']) ?></code></td>
                            <td><?= htmlspecialchars($p['razon_social']) ?></td>
                            <td><?= htmlspecialchars($p['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['telefono'] ?? '-') ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="new_proveedor.php?id=<?= (int)$p['id'] ?>&origen=inactivos" class="btn btn-warning btn-action" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="proveedor_detalle.php?id=<?= (int)$p['id'] ?>" class="btn btn-info btn-action" title="Ver Detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button onclick="reactivarProveedor(<?= (int)$p['id'] ?>)" class="btn btn-success btn-action" title="Reactivar">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                    <button onclick="eliminarProveedor(<?= (int)$p['id'] ?>, '<?= htmlspecialchars(addslashes($p['razon_social'])) ?>')" class="btn btn-danger btn-action" title="Eliminar definitivamente">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="my-3">
                <nav aria-label="Paginación">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">Primera</a>
                        </li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">Última</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Modales -->
    <div class="modal fade" id="modalReactivar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reactivar Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Seguro que quieres reactivar este proveedor?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirmarReactivar">Reactivar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Eliminar permanentemente el proveedor <strong id="nombreProveedorEliminar"></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmarEliminar">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let proveedorIdActual = null;
    let modalReactivar = new bootstrap.Modal(document.getElementById('modalReactivar'));
    let modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));

    function reactivarProveedor(id) {
        proveedorIdActual = id;
        modalReactivar.show();
    }

    function eliminarProveedor(id, nombre) {
        proveedorIdActual = id;
        document.getElementById('nombreProveedorEliminar').textContent = nombre;
        modalEliminar.show();
    }

    document.getElementById('confirmarReactivar').addEventListener('click', function() {
        if (proveedorIdActual) {
            gestionarProveedor('reactivar', proveedorIdActual);
        }
    });

    document.getElementById('confirmarEliminar').addEventListener('click', function() {
        if (proveedorIdActual) {
            gestionarProveedor('eliminar', proveedorIdActual);
        }
    });

    function gestionarProveedor(accion, id) {
        const modal = (accion === 'reactivar') ? modalReactivar : modalEliminar;
        const btn = document.getElementById(accion === 'reactivar' ? 'confirmarReactivar' : 'confirmarEliminar');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Procesando...';
        btn.disabled = true;

        fetch('gestionar_proveedor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ accion: accion, id: id })
        })
        .then(response => response.json())
        .then(data => {
            modal.hide();
            if (data.success) {
                mostrarMensaje(data.message, 'success');
                // Eliminar la fila de la tabla para una UI más limpia
                const fila = document.getElementById('proveedor-' + id);
                if (fila) {
                    fila.remove();
                }
            } else {
                mostrarMensaje(data.message || 'Error desconocido', 'danger');
            }
        })
        .catch(error => {
            modal.hide();
            console.error('Error:', error);
            mostrarMensaje('Error al procesar la solicitud.', 'danger');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }

    function mostrarMensaje(mensaje, tipo) {
        const alertContainer = document.createElement('div');
        alertContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1056; min-width: 300px;';
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
        alertDiv.innerHTML = `<i class="bi bi-${tipo === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} me-2"></i>${mensaje}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        alertContainer.appendChild(alertDiv);
        document.body.appendChild(alertContainer);
        new bootstrap.Alert(alertDiv);
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertContainer.remove(), 150);
        }, 5000);
    }
    </script>
</body>
</html>