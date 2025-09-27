<?php
require_once 'config/config.php';
iniciarSesionSegura();
requireLogin();

$pageTitle = "Panel de Control - " . SISTEMA_NOMBRE;
$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .calendario { max-width: 350px; background: #fff; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; padding: 10px; margin: 0 auto; }
        .calendario h3 { margin: 0 0 8px 0; color: #007bff; font-size: 18px; }
        .calendario table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .calendario th { color: #6c757d; font-size: 12px; padding: 5px 0; font-weight: normal; }
        .calendario td { height: 35px; font-size: 14px; border: 1px solid #f1f1f1; vertical-align: top; padding: 2px; }
        .calendario td:hover { background: #e9ecef; cursor: pointer; }
        .hoy { background: #007bff; color: #fff; font-weight: bold; border-radius: 50%; }
        .nav-calendario { display: flex; justify-content: space-between; align-items: center; }
        .nav-calendario a { text-decoration: none; color: #007bff; font-weight: bold; font-size: 16px; padding: 2px 6px; border-radius: 6px; transition: 0.2s; }
        .nav-calendario a:hover { background: #e9ecef; }
        .chart-container { padding: 1rem; background: #fff; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <?php include 'config/navbar_code.php'; ?>

    <div class="container text-center my-3">
        <a href="menu_principal.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-grid-3x3-gap-fill"></i> Ver Módulos del Sistema
        </a>
    </div>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <!-- Columna Izquierda: Calendario y Ventas del Mes -->
            <div class="col-md-5">
                <div class="mb-4">
                    <h4 class="text-center mb-3">Agenda</h4>
                    <div id="calendario-container"></div>
                </div>
                <div class="mb-4">
                    <h4 class="text-center mb-3">Ventas del Mes</h4>
                    <div class="chart-container" style="max-width: 350px; margin: 0 auto;">
                        <canvas id="monthlySalesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Estadísticas y Ventas Anuales -->
            <div class="col-md-7">
                <h4 class="text-center mb-3">Estadísticas Generales</h4>
                <div class="row">
                    <div class="col-lg-4"><div class="card text-white bg-primary mb-3"><div class="card-header">Ventas del Mes</div><div class="card-body"><h5 class="card-title">$ 12,345.67</h5></div></div></div>
                    <div class="col-lg-4"><div class="card text-white bg-warning mb-3"><div class="card-header">Bajo Stock</div><div class="card-body"><h5 class="card-title">8 Prod.</h5></div></div></div>
                    <div class="col-lg-4"><div class="card text-white bg-success mb-3"><div class="card-header">Nuevos Clientes</div><div class="card-body"><h5 class="card-title">15</h5></div></div></div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h4 class="text-center mb-3 mt-3">Ventas Anuales</h4>
                        <div class="chart-container">
                            <canvas id="annualSalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detalleDiaModal" tabindex="-1" aria-labelledby="detalleDiaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="detalleDiaModalLabel">Detalles del Día</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body" id="detalleDiaModalBody"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalElement = document.getElementById('detalleDiaModal');
            const modalBody = document.getElementById('detalleDiaModalBody');
            const modalInstance = new bootstrap.Modal(modalElement);

            function cargarCalendario(mes, anio) {
                fetch(`widgets/panelcontrol_widgets.php?mes=${mes}&anio=${anio}`)
                    .then(res => res.text())
                    .then(html => {
                        document.getElementById("calendario-container").innerHTML = html;
                        document.querySelectorAll('#calendario-container td[data-date]').forEach(cell => {
                            cell.addEventListener('click', () => abrirDetalleDia(cell.dataset.date));
                        });
                    });
            }

            function abrirDetalleDia(fecha) {
                fetch(`modulos/agenda/detalle_dia.php?fecha=${fecha}`)
                    .then(response => response.text())
                    .then(html => {
                        modalBody.innerHTML = html;
                        modalInstance.show();
                    });
            }

            let fecha = new Date();
            cargarCalendario(fecha.getMonth() + 1, fecha.getFullYear());

            document.getElementById('calendario-container').addEventListener('click', function(event) {
                let target = event.target.closest('a'); // Find the closest anchor tag
                if (target && (target.classList.contains('nav-link-prev') || target.classList.contains('nav-link-next'))) {
                    event.preventDefault();
                    const mes = target.dataset.mes;
                    const anio = target.dataset.anio;
                    cargarCalendario(mes, anio);
                }
            });

            // --- CHARTS ---
            const annualCtx = document.getElementById('annualSalesChart').getContext('2d');
            new Chart(annualCtx, { type: 'line', data: { labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'], datasets: [{ label: 'Ventas Anuales', data: [1200, 1900, 3000, 5000, 2300, 3100, 4000, 3500, 4500, 6000, 5500, 7000], backgroundColor: 'rgba(0, 123, 255, 0.1)', borderColor: 'rgba(0, 123, 255, 1)', tension: 0.3, fill: true }] } });

            const monthlyCtx = document.getElementById('monthlySalesChart').getContext('2d');
            new Chart(monthlyCtx, { type: 'bar', data: { labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'], datasets: [{ label: 'Ventas del Mes', data: [1250, 1980, 3050, 2800], backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)', 'rgba(75, 192, 192, 0.2)'], borderColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)', 'rgba(75, 192, 192, 1)'], borderWidth: 1 }] }, options: { scales: { y: { beginAtZero: true } } } });

            // --- MODAL EVENT LISTENER ---
            modalBody.addEventListener('click', function(event) {
                const target = event.target;
                if (target.classList.contains('btn-editar')) {
                    event.preventDefault();
                    const id = target.dataset.id;
                    const evento = JSON.parse(modalBody.querySelector('#eventosData').textContent).find(ev => ev.id == id);
                    if (evento) {
                        modalBody.querySelector('#form-title').innerText = 'Editar Evento';
                        const form = modalBody.querySelector('#addEventForm');
                        form.querySelector('#evento_id').value = evento.id;
                        form.querySelector('#titulo').value = evento.titulo;
                        form.querySelector('#descripcion').value = evento.descripcion;
                        form.querySelector('#tipo').value = evento.tipo;
                        form.querySelector('#prioridad').value = evento.prioridad;
                        form.querySelector('#fecha_inicio').value = evento.fecha_inicio.replace(' ', 'T');
                        form.querySelector('#fecha_fin').value = evento.fecha_fin.replace(' ', 'T');
                        form.querySelector('#completada').checked = evento.completada == 1;
                    }
                }
                if (target.classList.contains('btn-eliminar')) {
                    event.preventDefault();
                    if (!confirm('¿Está seguro?')) return;
                    const id = target.dataset.id;
                    const formData = new FormData();
                    formData.append('id', id);
                    fetch('modulos/agenda/eliminar_evento.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if(data.success) {
                                modalInstance.hide();
                                cargarCalendario(new Date().getMonth() + 1, new Date().getFullYear());
                            } else { alert(data.message); }
                        });
                }
            });
            modalBody.addEventListener('submit', function(event) {
                if (event.target.id === 'addEventForm') {
                    event.preventDefault();
                    const form = event.target;
                    const formData = new FormData(form);
                    fetch(form.action, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if(data.success) {
                                modalInstance.hide();
                                cargarCalendario(new Date().getMonth() + 1, new Date().getFullYear());
                            } else { alert(data.message); }
                        });
                }
            });
        });
    </script>

</body>
</html>
