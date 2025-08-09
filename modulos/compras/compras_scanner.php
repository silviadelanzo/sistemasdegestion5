<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

// Obtener datos necesarios
$proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
$productos = $pdo->query("SELECT p.*, c.nombre as categoria_nombre, l.nombre as lugar_nombre 
                         FROM productos p 
                         LEFT JOIN categorias c ON p.categoria_id = c.id 
                         LEFT JOIN lugares l ON p.lugar_id = l.id 
                         WHERE p.activo = 1 ORDER BY p.nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📱 Scanner de Códigos de Barras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 1200px;
            overflow: hidden;
        }

        .header-scanner {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-scanner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0 0h20v20H0zM20 20h20v20H20zM40 0h20v20H40zM60 20h20v20H60zM80 0h20v20H80z" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .scanner-zone {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 3px dashed #28a745;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin: 30px;
            position: relative;
            transition: all 0.3s ease;
        }

        .scanner-zone.active {
            border-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5, #ffe6e6);
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .barcode-input {
            font-size: 1.5rem;
            font-family: 'Courier New', monospace;
            text-align: center;
            height: 60px;
            border: 3px solid #28a745;
            border-radius: 10px;
            background: white;
        }

        .barcode-input:focus {
            border-color: #dc3545;
            box-shadow: 0 0 20px rgba(220, 53, 69, 0.3);
        }

        .product-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin: 10px 0;
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            border-color: #28a745;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
        }

        .product-card.scanned {
            border-color: #28a745;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
        }

        .nav-tabs-scanner {
            background: linear-gradient(90deg, #f8f9fa, #e9ecef);
            border-bottom: 3px solid #28a745;
            padding: 0 30px;
        }

        .nav-tabs-scanner .nav-link {
            border: none;
            border-radius: 15px 15px 0 0;
            padding: 15px 25px;
            margin-right: 5px;
            font-weight: 600;
            color: #495057;
            transition: all 0.3s ease;
        }

        .nav-tabs-scanner .nav-link.active {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .stats-box {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin: 10px 0;
        }

        .btn-scanner {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            border-radius: 25px;
            padding: 15px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-scanner:hover {
            background: linear-gradient(135deg, #20c997, #17a2b8);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>

<body>
    <div class="main-card">
        <!-- Header -->
        <div class="header-scanner">
            <h1 class="mb-3">
                <i class="fas fa-barcode fa-2x"></i><br>
                Scanner de Códigos de Barras
            </h1>
            <p class="lead mb-4">Carga rápida con pistola láser o cámara web</p>
            <div class="row text-center">
                <div class="col-md-4">
                    <h3 id="total-escaneados">0</h3>
                    <small>Productos Escaneados</small>
                </div>
                <div class="col-md-4">
                    <h3 id="total-cantidad">0</h3>
                    <small>Cantidad Total</small>
                </div>
                <div class="col-md-4">
                    <h3 id="velocidad-escaneo">0</h3>
                    <small>Items/min</small>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs nav-tabs-scanner" id="scannerTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="escaner-tab" data-bs-toggle="tab" data-bs-target="#escaner" type="button" role="tab">
                    <i class="fas fa-barcode"></i> Escanear
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos" type="button" role="tab">
                    <i class="fas fa-list"></i> Productos <span class="badge bg-light text-dark ms-1" id="badge-productos">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="configuracion-tab" data-bs-toggle="tab" data-bs-target="#configuracion" type="button" role="tab">
                    <i class="fas fa-cog"></i> Configuración
                </button>
            </li>
        </ul>

        <div class="tab-content p-4">
            <!-- Tab Escanear -->
            <div class="tab-pane fade show active" id="escaner" role="tabpanel">
                <div class="scanner-zone" id="scanner-zone">
                    <i class="fas fa-barcode fa-4x text-success mb-3"></i>
                    <h4>Zona de Escaneo Activa</h4>
                    <p class="text-muted mb-4">Apunta la pistola láser aquí o usa la cámara web</p>
                    
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <input type="text" class="form-control barcode-input" id="barcode-input" 
                                   placeholder="Código aparecerá aquí..." autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button class="btn btn-scanner me-3" onclick="activarCamara()">
                            <i class="fas fa-camera me-2"></i>Activar Cámara
                        </button>
                        <button class="btn btn-outline-primary" onclick="escaneoManual()">
                            <i class="fas fa-keyboard me-2"></i>Ingreso Manual
                        </button>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="stats-box">
                            <h3 id="session-count">0</h3>
                            <p class="mb-0">Esta Sesión</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-box">
                            <h3 id="success-rate">100%</h3>
                            <p class="mb-0">Éxito</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-box">
                            <h3 id="tiempo-session">0:00</h3>
                            <p class="mb-0">Tiempo</p>
                        </div>
                    </div>
                </div>

                <!-- Últimos escaneados -->
                <div class="mt-4">
                    <h5><i class="fas fa-history me-2"></i>Últimos Escaneados</h5>
                    <div id="ultimos-escaneados" class="row">
                        <div class="col-12 text-center text-muted py-4">
                            <i class="fas fa-barcode fa-3x mb-3"></i>
                            <p>Los productos escaneados aparecerán aquí</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Productos -->
            <div class="tab-pane fade" id="productos" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="fas fa-boxes me-2"></i>Lista de Productos</h4>
                    <div>
                        <button class="btn btn-success" onclick="finalizarCompra()">
                            <i class="fas fa-check me-2"></i>Finalizar Compra
                        </button>
                        <button class="btn btn-outline-danger ms-2" onclick="limpiarLista()">
                            <i class="fas fa-trash me-2"></i>Limpiar
                        </button>
                    </div>
                </div>

                <div id="lista-productos">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-shopping-cart fa-4x mb-3"></i>
                        <h5>No hay productos en la lista</h5>
                        <p>Escanea códigos de barras para comenzar</p>
                    </div>
                </div>
            </div>

            <!-- Tab Configuración -->
            <div class="tab-pane fade" id="configuracion" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-cog me-2"></i>Configuración del Scanner</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Proveedor por Defecto</label>
                                    <select class="form-select" id="proveedor-defecto">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($proveedores as $proveedor): ?>
                                            <option value="<?php echo $proveedor['id']; ?>">
                                                <?php echo htmlspecialchars($proveedor['razon_social']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Cantidad por Defecto</label>
                                    <input type="number" class="form-control" id="cantidad-defecto" value="1" min="1" step="0.01">
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="sonido-beep" checked>
                                        <label class="form-check-label" for="sonido-beep">
                                            Sonido de confirmación
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="auto-focus" checked>
                                        <label class="form-check-label" for="auto-focus">
                                            Auto-focus en campo de escaneo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar me-2"></i>Estadísticas</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">Productos únicos escaneados</small>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 0%" id="progress-unicos"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Velocidad de escaneo</small>
                                    <div class="progress">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 0%" id="progress-velocidad"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Precisión del scanner</small>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 100%" id="progress-precision"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fas fa-camera me-2"></i>Scanner por Cámara</h5>
                            </div>
                            <div class="card-body">
                                <video id="video" width="100%" height="200" style="border-radius: 8px; display: none;"></video>
                                <canvas id="canvas" style="display: none;"></canvas>
                                <div id="camera-status" class="text-center text-muted py-4">
                                    <i class="fas fa-camera fa-3x mb-3"></i>
                                    <p>Haz clic en "Activar Cámara" para usar scanner visual</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-light p-3 text-center">
            <button class="btn btn-secondary me-3" onclick="window.location.href='compras_form.php'">
                <i class="fas fa-arrow-left me-2"></i>Volver al Selector
            </button>
            <button class="btn btn-primary" onclick="continuarConManual()">
                <i class="fas fa-keyboard me-2"></i>Continuar en Manual
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const productos = <?php echo json_encode($productos); ?>;
        let productosEscaneados = [];
        let sessionStartTime = Date.now();
        let sessionCount = 0;
        let errorCount = 0;

        // Variables para cámara
        let video = document.getElementById('video');
        let canvas = document.getElementById('canvas');
        let context = canvas.getContext('2d');
        let scanning = false;

        document.addEventListener('DOMContentLoaded', function() {
            const barcodeInput = document.getElementById('barcode-input');
            barcodeInput.focus();
            
            // Event listener para el input del código de barras
            barcodeInput.addEventListener('input', function(e) {
                if (e.target.value.length >= 8) { // Mínimo para códigos EAN
                    procesarCodigoBarras(e.target.value);
                    e.target.value = '';
                }
            });

            // Mantener focus en el input
            document.addEventListener('click', function() {
                if (document.getElementById('auto-focus').checked) {
                    barcodeInput.focus();
                }
            });

            // Actualizar timer
            setInterval(actualizarTimer, 1000);
        });

        function procesarCodigoBarras(codigo) {
            // Buscar producto por código de barras
            const producto = productos.find(p => p.codigo_barra === codigo);
            
            if (producto) {
                agregarProductoEscaneado(producto);
                sessionCount++;
                reproducirSonido();
                mostrarAnimacionExito();
            } else {
                errorCount++;
                mostrarError(`Producto no encontrado: ${codigo}`);
            }
            
            actualizarEstadisticas();
        }

        function agregarProductoEscaneado(producto) {
            const cantidadDefecto = document.getElementById('cantidad-defecto').value;
            
            // Verificar si ya existe
            const existente = productosEscaneados.find(p => p.id === producto.id);
            
            if (existente) {
                existente.cantidad = parseFloat(existente.cantidad) + parseFloat(cantidadDefecto);
                existente.veces_escaneado++;
            } else {
                productosEscaneados.push({
                    ...producto,
                    cantidad: parseFloat(cantidadDefecto),
                    veces_escaneado: 1,
                    timestamp: new Date()
                });
            }
            
            actualizarListaProductos();
            actualizarUltimosEscaneados();
        }

        function actualizarListaProductos() {
            const lista = document.getElementById('lista-productos');
            
            if (productosEscaneados.length === 0) {
                lista.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-shopping-cart fa-4x mb-3"></i>
                        <h5>No hay productos en la lista</h5>
                        <p>Escanea códigos de barras para comenzar</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            productosEscaneados.forEach((producto, index) => {
                html += `
                    <div class="product-card scanned">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-1">${producto.nombre}</h6>
                                <small class="text-muted">Código: ${producto.codigo_barra}</small>
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" value="${producto.cantidad}" 
                                       onchange="actualizarCantidad(${index}, this.value)" min="0.01" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <span class="badge bg-info">${producto.veces_escaneado}x</span>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-danger btn-sm" onclick="eliminarProducto(${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            lista.innerHTML = html;
            document.getElementById('badge-productos').textContent = productosEscaneados.length;
        }

        function actualizarUltimosEscaneados() {
            const container = document.getElementById('ultimos-escaneados');
            const ultimos = productosEscaneados.slice(-3).reverse();
            
            if (ultimos.length === 0) return;
            
            let html = '';
            ultimos.forEach(producto => {
                html += `
                    <div class="col-md-4 mb-3">
                        <div class="product-card scanned">
                            <h6 class="mb-1">${producto.nombre}</h6>
                            <small class="text-muted">Cantidad: ${producto.cantidad}</small><br>
                            <small class="text-muted">${producto.timestamp.toLocaleTimeString()}</small>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function actualizarEstadisticas() {
            document.getElementById('total-escaneados').textContent = productosEscaneados.length;
            
            const totalCantidad = productosEscaneados.reduce((sum, p) => sum + parseFloat(p.cantidad), 0);
            document.getElementById('total-cantidad').textContent = totalCantidad.toFixed(2);
            
            document.getElementById('session-count').textContent = sessionCount;
            
            const successRate = sessionCount > 0 ? ((sessionCount - errorCount) / sessionCount * 100).toFixed(1) : 100;
            document.getElementById('success-rate').textContent = successRate + '%';
            
            // Calcular velocidad
            const tiempoTranscurrido = (Date.now() - sessionStartTime) / 60000; // minutos
            const velocidad = tiempoTranscurrido > 0 ? (sessionCount / tiempoTranscurrido).toFixed(1) : 0;
            document.getElementById('velocidad-escaneo').textContent = velocidad;
        }

        function actualizarTimer() {
            const tiempoTranscurrido = Math.floor((Date.now() - sessionStartTime) / 1000);
            const minutos = Math.floor(tiempoTranscurrido / 60);
            const segundos = tiempoTranscurrido % 60;
            document.getElementById('tiempo-session').textContent = 
                `${minutos}:${segundos.toString().padStart(2, '0')}`;
        }

        function activarCamara() {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(stream) {
                    video.srcObject = stream;
                    video.style.display = 'block';
                    document.getElementById('camera-status').style.display = 'none';
                    video.play();
                    scanning = true;
                    // Aquí se integraría una librería de lectura de códigos QR/barras
                    alert('Cámara activada. Integración con librería de códigos de barras pendiente.');
                })
                .catch(function(error) {
                    alert('Error al acceder a la cámara: ' + error.message);
                });
            } else {
                alert('Tu navegador no soporta acceso a cámara');
            }
        }

        function escaneoManual() {
            const codigo = prompt('Ingresa el código de barras manualmente:');
            if (codigo) {
                procesarCodigoBarras(codigo);
            }
        }

        function actualizarCantidad(index, nuevaCantidad) {
            productosEscaneados[index].cantidad = parseFloat(nuevaCantidad);
            actualizarEstadisticas();
        }

        function eliminarProducto(index) {
            productosEscaneados.splice(index, 1);
            actualizarListaProductos();
            actualizarEstadisticas();
        }

        function limpiarLista() {
            if (confirm('¿Eliminar todos los productos escaneados?')) {
                productosEscaneados = [];
                sessionCount = 0;
                errorCount = 0;
                sessionStartTime = Date.now();
                actualizarListaProductos();
                actualizarEstadisticas();
                actualizarUltimosEscaneados();
            }
        }

        function finalizarCompra() {
            if (productosEscaneados.length === 0) {
                alert('No hay productos para procesar');
                return;
            }
            
            // Aquí se procesaría la compra
            alert(`Compra finalizada con ${productosEscaneados.length} productos`);
            // Redirigir o procesar...
        }

        function continuarConManual() {
            // Guardar datos en sessionStorage para continuar en manual
            sessionStorage.setItem('productosEscaneados', JSON.stringify(productosEscaneados));
            window.location.href = 'compras_manual.php';
        }

        function reproducirSonido() {
            if (document.getElementById('sonido-beep').checked) {
                // Crear sonido de beep
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.1);
            }
        }

        function mostrarAnimacionExito() {
            const zone = document.getElementById('scanner-zone');
            zone.classList.add('active');
            setTimeout(() => zone.classList.remove('active'), 1000);
        }

        function mostrarError(mensaje) {
            // Crear toast de error
            const toast = document.createElement('div');
            toast.className = 'alert alert-danger position-fixed top-0 end-0 m-3';
            toast.style.zIndex = '9999';
            toast.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${mensaje}`;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 3000);
        }
    </script>
</body>
</html>
