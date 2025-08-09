<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Conectar a la base de datos
try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Obtener el próximo código de proveedor
    $sql_codigo = "SELECT codigo FROM proveedores WHERE codigo LIKE 'PROV-%' ORDER BY CAST(SUBSTRING(codigo, 6) AS UNSIGNED) DESC LIMIT 1";
    $stmt_codigo = $pdo->query($sql_codigo);
    $ultimo_codigo = $stmt_codigo->fetchColumn();
    
    if ($ultimo_codigo) {
        $numero = intval(substr($ultimo_codigo, 5)) + 1;
    } else {
        $numero = 1;
    }
    $nuevo_codigo = 'PROV-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
    
    // Obtener todos los países de la base de datos
    $sql_paises = "SELECT id, nombre, codigo_iso, codigo_telefono FROM paises WHERE activo = 1 ORDER BY nombre";
    $stmt_paises = $pdo->query($sql_paises);
    $paises = $stmt_paises->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error al conectar: " . $e->getMessage());
    $nuevo_codigo = 'PROV-0000001';
    $paises = [];
}

// Mapeo de tipos de identificación por país
$tipos_identificacion = [
    'ARG' => ['tipo' => 'CUIT', 'mascara' => '00-00000000-0', 'ejemplo' => '20-12345678-9'],
    'BRA' => ['tipo' => 'CNPJ', 'mascara' => '00.000.000/0000-00', 'ejemplo' => '12.345.678/0001-90'],
    'CHL' => ['tipo' => 'RUN', 'mascara' => '00.000.000-0', 'ejemplo' => '12.345.678-9'],
    'COL' => ['tipo' => 'NIT', 'mascara' => '000.000.000-0', 'ejemplo' => '123.456.789-0'],
    'USA' => ['tipo' => 'EIN', 'mascara' => '00-0000000', 'ejemplo' => '12-3456789'],
    'ESP' => ['tipo' => 'CIF', 'mascara' => 'A00000000', 'ejemplo' => 'A12345678'],
    'MEX' => ['tipo' => 'RFC', 'mascara' => 'AAAA000000AAA', 'ejemplo' => 'XAXX010101000'],
    'URY' => ['tipo' => 'RUT', 'mascara' => '0.000.000-0', 'ejemplo' => '1.234.567-8'],
    'BOL' => ['tipo' => 'NIT', 'mascara' => '0000000000', 'ejemplo' => '1234567890'],
    'CRI' => ['tipo' => 'CEDULA', 'mascara' => '0-0000-0000', 'ejemplo' => '1-2345-6789'],
    'ECU' => ['tipo' => 'RUC', 'mascara' => '0000000000000', 'ejemplo' => '1234567890001'],
    'GTM' => ['tipo' => 'NIT', 'mascara' => '0000000-0', 'ejemplo' => '1234567-8'],
    'HND' => ['tipo' => 'RTN', 'mascara' => '00000000000000', 'ejemplo' => '12345678901234'],
    'NIC' => ['tipo' => 'RUC', 'mascara' => 'J00000000000000', 'ejemplo' => 'J12345678901234'],
    'PAN' => ['tipo' => 'RUC', 'mascara' => '0-000-000000', 'ejemplo' => '1-234-567890'],
    'PER' => ['tipo' => 'RUC', 'mascara' => '00000000000', 'ejemplo' => '12345678901'],
    'PRY' => ['tipo' => 'RUC', 'mascara' => '00000000-0', 'ejemplo' => '12345678-9'],
    'DOM' => ['tipo' => 'RNC', 'mascara' => '000000000', 'ejemplo' => '123456789'],
    'SLV' => ['tipo' => 'NIT', 'mascara' => '0000-000000-000-0', 'ejemplo' => '1234-567890-123-4'],
    'VEN' => ['tipo' => 'RIF', 'mascara' => 'A-00000000-0', 'ejemplo' => 'J-12345678-9'],
    'CAN' => ['tipo' => 'BN', 'mascara' => '000000000AA0000', 'ejemplo' => '123456789RT0001'],
    'CHN' => ['tipo' => 'USCC', 'mascara' => '000000000000000000', 'ejemplo' => '123456789012345678'],
    'ITA' => ['tipo' => 'CF', 'mascara' => 'AAAAAA00A00A000A', 'ejemplo' => 'RSSMRA80A01H501A'],
    'FRA' => ['tipo' => 'SIRET', 'mascara' => '00000000000000', 'ejemplo' => '12345678901234'],
    'DEU' => ['tipo' => 'USt-IdNr', 'mascara' => 'DE000000000', 'ejemplo' => 'DE123456789']
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $codigo = $nuevo_codigo;
        $razon_social = trim($_POST['razon_social']);
        $tipo_identificacion = trim($_POST['tipo_identificacion']);
        $numero_identificacion = trim($_POST['numero_identificacion']);
        $pais_id = intval($_POST['pais']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        $whatsapp = trim($_POST['whatsapp']);
        $direccion = trim($_POST['direccion']);
        $ciudad = trim($_POST['ciudad']);
        $observaciones = trim($_POST['observaciones']);
        
        // Validaciones básicas
        if (empty($razon_social)) {
            throw new Exception("La razón social es obligatoria");
        }
        if (empty($tipo_identificacion) || empty($numero_identificacion)) {
            throw new Exception("El tipo e identificación son obligatorios");
        }
        if ($pais_id <= 0) {
            throw new Exception("Debe seleccionar un país");
        }
        
        // Insertar en la base de datos
        $sql = "INSERT INTO proveedores (codigo, razon_social, cuit, pais_id, email, telefono, whatsapp, direccion, ciudad, activo, fecha_creacion) 
                VALUES (:codigo, :razon_social, :cuit, :pais_id, :email, :telefono, :whatsapp, :direccion, :ciudad, 1, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':codigo' => $codigo,
            ':razon_social' => $razon_social,
            ':cuit' => $numero_identificacion,
            ':pais_id' => $pais_id,
            ':email' => $email,
            ':telefono' => $telefono,
            ':whatsapp' => $whatsapp,
            ':direccion' => $direccion,
            ':ciudad' => $ciudad
        ]);
        
        $mensaje_exito = "Proveedor creado exitosamente con código: " . $codigo;
        
        // Redireccionar según origen
        $origen = $_GET['origen'] ?? 'proveedores';
        if ($origen === 'compras') {
            header("Location: compras_form.php?msg=proveedor_creado");
        } else {
            header("Location: proveedores.php?msg=proveedor_creado");
        }
        exit;
        
    } catch (Exception $e) {
        $mensaje_error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Proveedor - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --border-radius: 10px;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .main-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .form-container {
            padding: 40px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 16px;
        }
        
        .phone-container {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }
        
        .country-selector {
            flex: 2;
            min-width: 200px;
        }
        
        .phone-input {
            flex: 3;
        }
        
        .select2-container--default .select2-selection--single {
            height: 50px !important;
            border: 2px solid #e1e5e9 !important;
            border-radius: 8px !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 46px !important;
            padding-left: 15px !important;
        }
        
        .flag {
            width: 20px;
            height: 15px;
            display: inline-block;
            margin-right: 8px;
            border-radius: 2px;
            vertical-align: middle;
            background-size: cover;
            background-position: center;
        }
        
        .codigo-auto {
            background: #f8f9fa !important;
            border-color: #28a745 !important;
            font-weight: bold;
            color: #28a745;
        }
        
        .btn-action {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }
        
        .masked-input {
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .phone-container {
                flex-direction: column;
            }
            .country-selector, .phone-input {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1><i class="fas fa-plus-circle me-3"></i>Nuevo Proveedor</h1>
            <p>Complete la información del proveedor</p>
        </div>
        
        <div class="form-container">
            <?php if (isset($mensaje_error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $mensaje_error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($mensaje_exito)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $mensaje_exito; ?>
                </div>
            <?php endif; ?>
            
            <form id="form-nuevo-proveedor" method="POST">
                <!-- Código Automático -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-hashtag me-2"></i>Código (Automático)
                        </label>
                        <input type="text" class="form-control codigo-auto" name="codigo" value="<?php echo $nuevo_codigo; ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-globe me-2"></i>País *
                        </label>
                        <select class="form-select" name="pais" id="pais" required>
                            <option value="">Seleccionar país...</option>
                            <?php foreach ($paises as $pais): ?>
                                <option value="<?php echo $pais['id']; ?>" 
                                        data-iso="<?php echo $pais['codigo_iso']; ?>"
                                        data-phone="<?php echo $pais['codigo_telefono']; ?>"
                                        <?php echo ($pais['codigo_iso'] === 'ARG') ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pais['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-id-card me-2"></i>Tipo de Identificación *
                        </label>
                        <select class="form-select" name="tipo_identificacion" id="tipo_identificacion" required>
                            <option value="">Seleccionar tipo...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-id-badge me-2"></i>Número de Identificación *
                        </label>
                        <input type="text" class="form-control masked-input" name="numero_identificacion" id="numero_identificacion" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-building me-2"></i>Razón Social *
                    </label>
                    <input type="text" class="form-control" name="razon_social" required placeholder="Nombre oficial de la empresa">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" class="form-control" name="email" placeholder="correo@empresa.com">
                </div>
                
                <!-- Teléfono -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-phone me-2"></i>Teléfono
                    </label>
                    <div class="phone-container">
                        <select class="form-select country-selector" id="telefono-pais" name="telefono_pais">
                            <option value="">Seleccionar país...</option>
                            <?php foreach ($paises as $pais): ?>
                                <option value="<?php echo $pais['codigo_iso']; ?>" 
                                        data-phone="<?php echo $pais['codigo_telefono']; ?>"
                                        <?php echo ($pais['codigo_iso'] === 'ARG') ? 'selected' : ''; ?>>
                                    <?php echo $pais['codigo_iso']; ?> (<?php echo $pais['codigo_telefono']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" class="form-control phone-input masked-input" name="telefono" id="telefono" placeholder="11 1234 5678">
                    </div>
                </div>
                
                <!-- WhatsApp -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fab fa-whatsapp me-2"></i>WhatsApp
                    </label>
                    <div class="phone-container">
                        <select class="form-select country-selector" id="whatsapp-pais" name="whatsapp_pais">
                            <option value="">Seleccionar país...</option>
                            <?php foreach ($paises as $pais): ?>
                                <option value="<?php echo $pais['codigo_iso']; ?>" 
                                        data-phone="<?php echo $pais['codigo_telefono']; ?>"
                                        <?php echo ($pais['codigo_iso'] === 'ARG') ? 'selected' : ''; ?>>
                                    <?php echo $pais['codigo_iso']; ?> (<?php echo $pais['codigo_telefono']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" class="form-control phone-input masked-input" name="whatsapp" id="whatsapp" placeholder="11 1234 5678">
                    </div>
                </div>
                
                <!-- Información Adicional -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt me-2"></i>Dirección
                        </label>
                        <input type="text" class="form-control" name="direccion" placeholder="Calle y número">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-city me-2"></i>Ciudad
                        </label>
                        <input type="text" class="form-control" name="ciudad" placeholder="Ciudad">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-sticky-note me-2"></i>Observaciones
                    </label>
                    <textarea class="form-control" name="observaciones" rows="3" placeholder="Notas adicionales..."></textarea>
                </div>
                
                <div class="actions">
                    <button type="button" class="btn btn-secondary btn-action" onclick="cancelar()">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary btn-action">
                        <i class="fas fa-save me-2"></i>Guardar Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <script>
        // Configuración de tipos de identificación por país
        const tiposIdentificacion = <?php echo json_encode($tipos_identificacion); ?>;
        
        $(document).ready(function() {
            // Configurar Select2
            $('#pais, .country-selector').select2({
                placeholder: 'Buscar país...',
                allowClear: true
            });
            
            // Eventos de cambio
            $('#pais').on('change', function() {
                const paisOption = $(this).find('option:selected');
                const paisIso = paisOption.data('iso');
                const paisPhone = paisOption.data('phone');
                
                if (paisIso && tiposIdentificacion[paisIso]) {
                    const config = tiposIdentificacion[paisIso];
                    
                    // Actualizar tipo de identificación
                    $('#tipo_identificacion').empty()
                        .append('<option value="">Seleccionar tipo...</option>')
                        .append(`<option value="${config.tipo}" selected>${config.tipo}</option>`);
                    
                    // Actualizar máscara
                    $('#numero_identificacion').unmask().mask(config.mascara, {
                        placeholder: config.ejemplo
                    });
                    
                    // Sincronizar teléfonos
                    $('#telefono-pais, #whatsapp-pais').val(paisIso).trigger('change');
                }
            });
            
            // Máscaras de teléfono
            $('#telefono-pais, #whatsapp-pais').on('change', function() {
                const paisIso = $(this).val();
                const input = $(this).siblings('.phone-input');
                
                input.unmask();
                
                // Máscaras específicas por país
                const mascarasTelefono = {
                    'ARG': '0000-000000',
                    'USA': '(000) 000-0000',
                    'MEX': '00 0000 0000',
                    'ESP': '000 00 00 00',
                    'CHL': '0 0000 0000',
                    'COL': '000 000 0000',
                    'BRA': '(00) 00000-0000',
                    'BOL': '0000-0000',
                    'URY': '0000 0000'
                };
                
                const mascara = mascarasTelefono[paisIso] || '000000000000';
                input.mask(mascara);
            });
            
            // Inicializar con Argentina
            $('#pais').trigger('change');
        });
        
        function cancelar() {
            const origen = new URLSearchParams(window.location.search).get('origen');
            if (origen === 'compras') {
                window.location.href = 'compras_form.php';
            } else {
                window.location.href = 'proveedores.php';
            }
        }
    </script>
</body>
</html>
