<?php
require_once '../../config/config.php';
require_once '../../api/api_client.php';
iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

// --- Lógica de obtención de datos vía API ---
try {
    $data = llamar_api('compras/compras.php', $_GET);

    $ordenes_compra = $data['ordenes_compra'];
    $paginacion = $data['paginacion'];
    $stats = $data['stats'];
    $proveedores = $data['proveedores'];

    $page = $paginacion['page'];
    $total_pages = $paginacion['total_pages'];
    $ordenes_compra_filtradas = $paginacion['total_records'];
    
    $total_ordenes_compra = $stats['total'];
    $ordenes_pendientes = $stats['counts']['pendiente de entrega'] ?? 0;
    $ordenes_parcial = $stats['counts']['parcialmente entregada'] ?? 0;
    $ordenes_entregadas = $stats['counts']['entregada'] ?? 0;
    $ordenes_canceladas = $stats['counts']['cancelada'] ?? 0;
    $valor_total_ordenes_compra = $stats['valor_total'];

} catch (Exception $e) {
    $error_message = "Error al cargar datos desde el API: " . $e->getMessage();
    $ordenes_compra = [];
    $total_pages = 1;
    $page = 1;
    $total_ordenes_compra = 0;
    $valor_total_ordenes_compra = 0;
    $proveedores = [];
    $ordenes_pendientes = 0;
    $ordenes_parcial = 0;
    $ordenes_entregadas = 0;
    $ordenes_canceladas = 0;
}

// --- Variables para la vista ---
$filtro_busqueda    = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_proveedor   = isset($_GET['proveedor']) ? trim($_GET['proveedor']) : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$orden_campo = isset($_GET['orden']) ? $_GET['orden'] : 'numero_orden';
$orden_direccion = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$pageTitle = "Gestión de Compras - " . SISTEMA_NOMBRE;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .main-container { max-width: 1000px; margin: 0 auto; }
        .table-container, .search-section { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-top: 16px; padding: 10px 12px; }
        .info-cards { display: flex; gap: 8px; margin: 12px 0 6px 0; }
        .info-card { flex: 1; display: flex; align-items: center; padding: 6px 10px; border-radius: 8px; color: #fff; font-weight: 500; font-size: 0.85rem; line-height: 1.1; min-height: 36px; box-shadow: 0 1px 5px rgba(0,0,0,0.04); }
        .info-card .icon { font-size: 1.2rem; margin-left: auto; opacity: 0.65; }
        .ic-blue   { background: linear-gradient(90deg, #396afc 0%, #2948ff 100%); }
        .ic-yellow { background: linear-gradient(90deg, #fc4a1a 0%, #f7b733 100%); }
        .ic-green  { background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%); }
        .ic-cyan   { background: linear-gradient(90deg, #a7a7a7 0%, #636363 100%); }
        .ic-purple { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); }
        .ic-danger { background: linear-gradient(90deg, #d31027 0%, #ea384d 100%); }
        .table { border-collapse: separate; border-spacing: 0; font-size: 0.92rem; }
        .table thead th { border-top: none !important; border-bottom: 1px solid #e9ecef !important; padding-top: 6px; padding-bottom: 6px; white-space: nowrap; }
        .table tbody tr { border-top: none !important; border-bottom: 1px solid #e9ecef; }
        .table tbody tr:last-child { border-bottom: none; }
        .table tbody td { border-top: none !important; border-bottom: none !important; padding: 6px 8px; vertical-align: middle; }
    </style>
</head>
<body>
    <?php include "../../config/navbar_code.php"; ?>
    <div class="main-container">
        <?php if (!empty($error_message)):
            echo '<div class="alert alert-danger"><strong>Error:</strong> ' . htmlspecialchars($error_message) . '</div>';
        endif; ?>
        <!-- Tarjetas resumen -->
        <div class="info-cards">
            <div class="info-card ic-yellow">
                <div>
                    <div style="font-size:0.8rem;font-weight:400;">Pendientes</div>
                    <div style="font-size:1.1rem; font-weight:bold;"><?= $ordenes_pendientes ?> de <?= $total_ordenes_compra ?></div>
                </div>
                <span class="icon ms-2"><i class="bi bi-clock"></i></span>
            </div>
            <div class="info-card ic-blue">
                <div>
                    <div style="font-size:0.8rem;font-weight:400;">Parcialmente Entregada</div>
                    <div style="font-size:1.1rem; font-weight:bold;"><?= $ordenes_parcial ?> de <?= $total_ordenes_compra ?></div>
                </div>
                <span class="icon ms-2"><i class="bi bi-box-seam"></i></span>
            </div>
            <div class="info-card ic-green">
                <div>
                    <div style="font-size:0.8rem;font-weight:400;">Entregadas</div>
                    <div style="font-size:1.1rem; font-weight:bold;"><?= $ordenes_entregadas ?> de <?= $total_ordenes_compra ?></div>
                </div>
                <span class="icon ms-2"><i class="bi bi-check-circle"></i></span>
            </div>
            <div class="info-card ic-danger">
                <div>
                    <div style="font-size:0.8rem;font-weight:400;">Canceladas</div>
                    <div style="font-size:1.1rem; font-weight:bold;"><?= $ordenes_canceladas ?> de <?= $total_ordenes_compra ?></div>
                </div>
                <span class="icon ms-2"><i class="bi bi-x-circle"></i></span>
            </div>
            <div class="info-card ic-purple">
                 <div>
                    <div style="font-size:0.8rem;font-weight:400;">Valor Total</div>
                    <div style="font-size:1.1rem; font-weight:bold;">$<?= number_format($valor_total_ordenes_compra, 2) ?></div>
                </div>
                <span class="icon ms-2"><i class="bi bi-currency-dollar"></i></span>
            </div>
        </div>
        <!-- Buscador y Filtros -->
        <div class="search-section">
            <form method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="busqueda" class="form-label mb-1">Nro. Orden o Proveedor</label>
                        <input type="text" class="form-control" id="busqueda" name="busqueda" placeholder="Buscar..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="proveedor" class="form-label mb-1">Proveedor</label>
                        <select class="form-select" id="proveedor" name="proveedor">
                            <option value="todos">Todos</option>
                            <?php foreach ($proveedores as $proveedor):
                                echo '<option value="' . $proveedor['id'] . '" ' . ($filtro_proveedor == $proveedor['id'] ? "selected" : "") . '>' . htmlspecialchars($proveedor['razon_social']) . '</option>';
                            endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_desde" class="form-label mb-1">Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_hasta" class="form-label mb-1">Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
                    </div>
                    <div class="col-md-1 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary me-2"><i class="bi bi-search"></i></button>
                        <a href="compras.php" class="btn btn-danger" title="Limpiar filtros"><i class="bi bi-arrow-clockwise"></i></a>
                    </div>
                </div>
                <div class="row mt-3"><div class="col-12 text-center"><a href="compra_form.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nueva O. de Compra</a></div></div>
            </form>
        </div>
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'numero_orden', 'dir' => ($orden_campo === 'numero_orden' && $orden_direccion === 'ASC') ? 'DESC' : 'ASC'])) ?>>Nro. Orden <i class="bi <?= ($orden_campo === 'numero_orden') ? (($orden_direccion === 'ASC') ? 'bi-arrow-up' : 'bi-arrow-down') : '' ?>></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'proveedor_nombre', 'dir' => ($orden_campo === 'proveedor_nombre' && $orden_direccion === 'ASC') ? 'DESC' : 'ASC'])) ?>>Proveedor <i class="bi <?= ($orden_campo === 'proveedor_nombre') ? (($orden_direccion === 'ASC') ? 'bi-arrow-up' : 'bi-arrow-down') : '' ?>></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'fecha_orden', 'dir' => ($orden_campo === 'fecha_orden' && $orden_direccion === 'ASC') ? 'DESC' : 'ASC'])) ?>>Fecha <i class="bi <?= ($orden_campo === 'fecha_orden') ? (($orden_direccion === 'ASC') ? 'bi-arrow-up' : 'bi-arrow-down') : '' ?>></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'estado_id', 'dir' => ($orden_campo === 'estado_id' && $orden_direccion === 'ASC') ? 'DESC' : 'ASC'])) ?>>Estado <i class="bi <?= ($orden_campo === 'estado_id') ? (($orden_direccion === 'ASC') ? 'bi-arrow-up' : 'bi-arrow-down') : '' ?>></i></a></th>
                            <th class="text-end">Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordenes_compra)):
                            echo '<tr><td colspan="6" class="text-center py-4"><i class="bi bi-inbox display-4 text-muted"></i><p class="text-muted mt-2">No se encontraron órdenes de compra.</p></td></tr>';
                        else:
                            foreach ($ordenes_compra as $orden):
                                echo '<tr id="orden-compra-' . $orden['id_orden'] . '">';
                                echo '<td><code class="text-primary">' . htmlspecialchars($orden['numero_orden']) . '</code></td>';
                                echo '<td><div class="fw-bold text-truncate">' . htmlspecialchars($orden['proveedor_nombre']) . '</div></td>';
                                echo '<td>' . date('d/m/Y', strtotime($orden['fecha_orden'])) . '</td>';
                                echo '<td>';
                                            $estado_nombre = $orden['nombre_estado'] ?? 'Desconocido';
                                            $clase_badge = 'bg-secondary';
                                            $nombre_normalizado = trim(strtolower($estado_nombre));

                                            if ($nombre_normalizado == 'pendiente') { $clase_badge = 'bg-warning text-dark'; } 
                                            elseif ($nombre_normalizado == 'confirmada') { $clase_badge = 'bg-info'; } 
                                            elseif ($nombre_normalizado == 'entregada' || $nombre_normalizado == 'recibida') { $clase_badge = 'bg-success'; } 
                                            elseif ($nombre_normalizado == 'cancelada') { $clase_badge = 'bg-danger'; } 
                                            elseif ($nombre_normalizado == 'parcialmente entregada' || $nombre_normalizado == 'parcial') { $clase_badge = 'bg-primary'; } 
                                            
                                            echo "<span class=\"badge {$clase_badge}\">" . htmlspecialchars($estado_nombre) . "</span>";
                                echo '</td>';
                                echo '<td class="text-end"><div class="fw-bold text-success">$' . number_format($orden['total'], 2) . '</div></td>';
                                echo '<td class="col-acciones">';
                                echo '<div class="btn-group" role="group">';
                                echo '<a href="compra_form.php?id=' . $orden['id_orden'] . '" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>';
                                echo '<a href="compra_detalle.php?id=' . $orden['id_orden'] . '" class="btn btn-sm btn-info" title="Ver Detalle"><i class="bi bi-eye"></i></a>';
                                echo '<a href="compra_imprimir.php?id=' . $orden['id_orden'] . '" class="btn btn-sm btn-secondary" title="Imprimir" target="_blank"><i class="bi bi-printer"></i></a>';
                                $numeroOrden = !empty($orden['numero_orden']) ? $orden['numero_orden'] : 'ID: ' . $orden['id_orden'];
                                echo '<button type="button" class="btn btn-sm btn-danger" title="Eliminar" onclick="eliminarOrdenCompra(' . $orden['id_orden'] . ', \''. htmlspecialchars($numeroOrden) .'\');"><i class="bi bi-trash"></i></button>';
                                echo '</div>';
                                echo '</td>';
                                echo '</tr>';
                            endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages > 1):
                echo '<div class="mt-3">';
                echo '<nav><ul class="pagination justify-content-center mb-0">';
                if ($page > 1):
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '"><i class="bi bi-chevron-double-left"></i></a></li>';
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $page - 1])) . '"><i class="bi bi-chevron-left"></i></a></li>';
                endif;
                for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++):
                    echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a></li>';
                endfor;
                if ($page < $total_pages):
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $page + 1])) . '"><i class="bi bi-chevron-right"></i></a></li>';
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '"><i class="bi bi-chevron-double-right"></i></a></li>';
                endif;
                echo '</ul></nav>';
                echo '<div class="text-center mt-2"><small class="text-muted">Página ' . $page . ' de ' . $total_pages . ' (' . number_format($ordenes_compra_filtradas) . ' resultados)</small></div>';
                echo '</div>';
            endif; ?>
        </div>
    </div>
    <!-- Modal para Eliminar Orden de Compra -->
    <div class="modal fade" id="modalEliminarCompra" tabindex="-1" aria-labelledby="modalEliminarCompraLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalEliminarCompraLabel"><i class="bi bi-trash me-2"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar la orden de compra Nro. <strong id="numeroOrdenEliminar"></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer y eliminará todos los detalles asociados.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmarEliminarCompra"><i class="bi bi-trash me-2"></i>Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let ordenCompraIdActual = null;
        let modalEliminarCompra = new bootstrap.Modal(document.getElementById('modalEliminarCompra'));

        function eliminarOrdenCompra(id, numeroOrden) {
            ordenCompraIdActual = id;
            // Validar que numeroOrden no sea undefined, null o vacío
            const numeroMostrar = numeroOrden && numeroOrden !== 'undefined' ? numeroOrden : 'ID: ' + id;
            document.getElementById('numeroOrdenEliminar').textContent = numeroMostrar;
            modalEliminarCompra.show();
        }

        document.getElementById('confirmarEliminarCompra').addEventListener('click', function() {
            if (ordenCompraIdActual) {
                gestionarCompra('eliminar', ordenCompraIdActual);
            }
        });

        function gestionarCompra(accion, id) {
            const btn = document.getElementById('confirmarEliminarCompra');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Procesando...';
            btn.disabled = true;

            fetch(`../../api/compras/compras.php?id=${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(result => {
                modalEliminarCompra.hide();
                if (result.status === 'success') {
                    mostrarMensaje(result.data.message, 'success');
                    const fila = document.getElementById('orden-compra-' + id);
                    if (fila) {
                        fila.remove();
                    }
                } else {
                    mostrarMensaje(result.message || 'Error desconocido', 'danger');
                }
            })
            .catch(error => {
                modalEliminarCompra.hide();
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
            setTimeout(() => { alertDiv.classList.remove('show'); setTimeout(() => alertContainer.remove(), 150); }, 5000);
        }
    </script>
</body>
</html>