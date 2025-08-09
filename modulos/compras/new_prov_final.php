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
        
        /* 🌍 SELECTOR DE TELÉFONO COMO CLIENTE_FORM */
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
        
        /* 🎨 SELECT2 CON BANDERAS Y CÓDIGOS */
        .select2-container--default .select2-selection--single {
            height: 50px !important;
            border: 2px solid #e1e5e9 !important;
            border-radius: 8px !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 46px !important;
            padding-left: 15px !important;
        }
        
        /* 🏳️ ICONOS DE BANDERAS */
        .flag {
            width: 20px;
            height: 15px;
            display: inline-block;
            margin-right: 8px;
            border-radius: 2px;
            vertical-align: middle;
        }
        
        .flag-ar { background: linear-gradient(to bottom, #74B5FF 33%, white 33%, white 66%, #74B5FF 66%); }
        .flag-us { background: linear-gradient(45deg, #B22234, #3C3B6E); }
        .flag-mx { background: linear-gradient(to right, #006847 33%, white 33%, white 66%, #CE1126 66%); }
        .flag-es { background: linear-gradient(to bottom, #AA151B 25%, #F1BF00 25%, #F1BF00 75%, #AA151B 75%); }
        .flag-cl { background: linear-gradient(to bottom, #0039A6 50%, white 50%); }
        .flag-co { background: linear-gradient(to bottom, #FDE047 50%, #3B82F6 50%, #EF4444 75%); }
        .flag-br { background: linear-gradient(45deg, #009739, #FEDD00); }
        .flag-bo { background: linear-gradient(to bottom, #D32F2F 33%, #FFEB3B 33%, #FFEB3B 66%, #388E3C 66%); }
        .flag-pe { background: linear-gradient(to right, #D32F2F 33%, white 33%, white 66%, #D32F2F 66%); }
        .flag-uy { background: linear-gradient(to bottom, #0033A0 50%, white 50%); }
        
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
        
        .codigo-auto {
            background: #f8f9fa !important;
            border-color: #28a745 !important;
            font-weight: bold;
            color: #28a745;
        }
        
        .codigo-auto::placeholder {
            color: #28a745;
            opacity: 0.8;
        }
        
        /* Input masking styles */
        .masked-input {
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
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
            <form id="form-nuevo-proveedor" method="POST">
                <!-- Código Automático -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-hashtag me-2"></i>Código (Automático)
                        </label>
                        <input type="text" class="form-control codigo-auto" name="codigo" id="codigo" readonly placeholder="PROV-0000001">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-id-card me-2"></i>Tipo de Identificación *
                        </label>
                        <select class="form-select" name="tipo_identificacion" id="tipo_identificacion" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="CUIT">CUIT (Argentina)</option>
                            <option value="RUC">RUC (Perú, Ecuador)</option>
                            <option value="RFC">RFC (México)</option>
                            <option value="CNPJ">CNPJ (Brasil)</option>
                            <option value="RIF">RIF (Venezuela)</option>
                            <option value="NIT">NIT (Colombia, Bolivia)</option>
                            <option value="RUN">RUN (Chile)</option>
                            <option value="CIF">CIF (España)</option>
                            <option value="TIN">TIN (Estados Unidos)</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-id-badge me-2"></i>Número de Identificación *
                        </label>
                        <input type="text" class="form-control masked-input" name="numero_identificacion" id="numero_identificacion" required placeholder="Ej: 20-12345678-9">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-globe me-2"></i>País *
                        </label>
                        <select class="form-select" name="pais" id="pais" required>
                            <option value="">Seleccionar país...</option>
                            <option value="AR" data-phone="+54" data-flag="ar">Argentina</option>
                            <option value="US" data-phone="+1" data-flag="us">Estados Unidos</option>
                            <option value="MX" data-phone="+52" data-flag="mx">México</option>
                            <option value="ES" data-phone="+34" data-flag="es">España</option>
                            <option value="CL" data-phone="+56" data-flag="cl">Chile</option>
                            <option value="CO" data-phone="+57" data-flag="co">Colombia</option>
                            <option value="BR" data-phone="+55" data-flag="br">Brasil</option>
                            <option value="BO" data-phone="+591" data-flag="bo">Bolivia</option>
                            <option value="PE" data-phone="+51" data-flag="pe">Perú</option>
                            <option value="UY" data-phone="+598" data-flag="uy">Uruguay</option>
                        </select>
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
                
                <!-- 🌍 TELÉFONO COMO CLIENTE_FORM -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-phone me-2"></i>Teléfono
                    </label>
                    <div class="phone-container">
                        <select class="form-select country-selector" id="telefono-pais" name="telefono_pais">
                            <option value="">Seleccionar país...</option>
                        </select>
                        <input type="text" class="form-control phone-input masked-input" name="telefono" id="telefono" placeholder="11 1234 5678">
                    </div>
                </div>
                
                <!-- 📱 WHATSAPP -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fab fa-whatsapp me-2"></i>WhatsApp
                    </label>
                    <div class="phone-container">
                        <select class="form-select country-selector" id="whatsapp-pais" name="whatsapp_pais">
                            <option value="">Seleccionar país...</option>
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
        // 🎯 CONFIGURACIÓN DE PAÍSES (como cliente_form.php)
        const paisesConfig = {
            'AR': { nombre: 'Argentina', codigo: '+54', flag: 'ar', tipo_id: 'CUIT', mascara: '00-00000000-0' },
            'US': { nombre: 'Estados Unidos', codigo: '+1', flag: 'us', tipo_id: 'TIN', mascara: '000-00-0000' },
            'MX': { nombre: 'México', codigo: '+52', flag: 'mx', tipo_id: 'RFC', mascara: 'AAAA000000AAA' },
            'ES': { nombre: 'España', codigo: '+34', flag: 'es', tipo_id: 'CIF', mascara: 'A00000000' },
            'CL': { nombre: 'Chile', codigo: '+56', flag: 'cl', tipo_id: 'RUN', mascara: '00.000.000-0' },
            'CO': { nombre: 'Colombia', codigo: '+57', flag: 'co', tipo_id: 'NIT', mascara: '000.000.000-0' },
            'BR': { nombre: 'Brasil', codigo: '+55', flag: 'br', tipo_id: 'CNPJ', mascara: '00.000.000/0000-00' },
            'BO': { nombre: 'Bolivia', codigo: '+591', flag: 'bo', tipo_id: 'NIT', mascara: '0000000000' },
            'PE': { nombre: 'Perú', codigo: '+51', flag: 'pe', tipo_id: 'RUC', mascara: '00000000000' },
            'UY': { nombre: 'Uruguay', codigo: '+598', flag: 'uy', tipo_id: 'RUT', mascara: '0.000.000-0' }
        };
        
        $(document).ready(function() {
            // 🚀 GENERAR CÓDIGO AUTOMÁTICO
            generarCodigoAutomatico();
            
            // 🌍 POBLAR SELECTORES DE TELÉFONO
            poblarSelectoresTelefono();
            
            // 🎨 CONFIGURAR SELECT2
            $('.country-selector').select2({
                placeholder: 'Buscar país...',
                allowClear: true,
                templateResult: formatearOpcionPais,
                templateSelection: formatearSeleccionPais
            });
            
            $('#pais').select2({
                placeholder: 'Buscar país...',
                allowClear: true,
                templateResult: formatearOpcionPais,
                templateSelection: formatearSeleccionPais
            });
            
            // 🔄 EVENTOS DE CAMBIO
            $('#pais').on('change', function() {
                const paisCodigo = $(this).val();
                if (paisCodigo && paisesConfig[paisCodigo]) {
                    const config = paisesConfig[paisCodigo];
                    
                    // Actualizar tipo de identificación
                    $('#tipo_identificacion').val(config.tipo_id).trigger('change');
                    
                    // Actualizar máscara de identificación
                    actualizarMascaraIdentificacion(config.mascara);
                    
                    // Sincronizar teléfonos
                    $('#telefono-pais, #whatsapp-pais').val(paisCodigo).trigger('change');
                }
            });
            
            $('#telefono-pais').on('change', function() {
                actualizarMascaraTelefono('#telefono', $(this).val());
            });
            
            $('#whatsapp-pais').on('change', function() {
                actualizarMascaraTelefono('#whatsapp', $(this).val());
            });
            
            // 🚀 INICIALIZAR CON ARGENTINA
            $('#pais').val('AR').trigger('change');
        });
        
        // 🔢 GENERAR CÓDIGO AUTOMÁTICO
        function generarCodigoAutomatico() {
            // Simular obtención del próximo código
            const timestamp = Date.now().toString().slice(-6);
            const codigo = 'PROV-' + timestamp.padStart(7, '0');
            $('#codigo').val(codigo).attr('placeholder', codigo);
        }
        
        // 🌍 POBLAR SELECTORES DE TELÉFONO
        function poblarSelectoresTelefono() {
            const selectors = ['#telefono-pais', '#whatsapp-pais'];
            
            selectors.forEach(selector => {
                const $select = $(selector);
                $select.empty().append('<option value="">Seleccionar país...</option>');
                
                Object.entries(paisesConfig).forEach(([codigo, config]) => {
                    $select.append(`<option value="${codigo}" data-phone="${config.codigo}" data-flag="${config.flag}">${config.nombre}</option>`);
                });
            });
        }
        
        // 🎨 FORMATEAR OPCIÓN DE PAÍS
        function formatearOpcionPais(option) {
            if (!option.id) return option.text;
            
            const $option = $(option.element);
            const flag = $option.data('flag');
            const phone = $option.data('phone');
            
            if (!flag) return option.text;
            
            return $(`
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="flag flag-${flag}"></span>
                    <span style="font-weight: 600;">${phone || option.id}</span>
                    <span style="color: #666;">${option.text}</span>
                </div>
            `);
        }
        
        function formatearSeleccionPais(option) {
            if (!option.id) return option.text;
            
            const $option = $(option.element);
            const flag = $option.data('flag');
            const phone = $option.data('phone');
            
            if (!flag) return option.text;
            
            return $(`
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="flag flag-${flag}"></span>
                    <span style="font-weight: 600;">${phone || option.id}</span>
                </div>
            `);
        }
        
        // 🎭 ACTUALIZAR MÁSCARA DE IDENTIFICACIÓN
        function actualizarMascaraIdentificacion(mascara) {
            $('#numero_identificacion').unmask().mask(mascara, {
                placeholder: mascara.replace(/[A0]/g, '_'),
                translation: {
                    'A': {pattern: /[A-Za-z]/},
                    '0': {pattern: /[0-9]/}
                }
            });
        }
        
        // 📱 ACTUALIZAR MÁSCARA DE TELÉFONO
        function actualizarMascaraTelefono(input, paisCodigo) {
            const $input = $(input);
            $input.unmask();
            
            if (paisCodigo && paisesConfig[paisCodigo]) {
                const codigo = paisesConfig[paisCodigo].codigo;
                
                // Máscaras específicas por país
                const mascarasTelefono = {
                    '+54': '0000-000000', // Argentina
                    '+1': '(000) 000-0000', // USA
                    '+52': '00 0000 0000', // México
                    '+34': '000 00 00 00', // España
                    '+56': '0 0000 0000', // Chile
                    '+57': '000 000 0000', // Colombia
                    '+55': '(00) 00000-0000' // Brasil
                };
                
                const mascara = mascarasTelefono[codigo] || '000000000000';
                $input.mask(mascara);
            }
        }
        
        // 📝 ENVÍO DEL FORMULARIO
        $('#form-nuevo-proveedor').on('submit', function(e) {
            e.preventDefault();
            
            // Validaciones básicas
            const campos = ['razon_social', 'tipo_identificacion', 'numero_identificacion', 'pais'];
            let valido = true;
            
            campos.forEach(campo => {
                const valor = $(`[name="${campo}"]`).val().trim();
                if (!valor) {
                    alert(`El campo ${campo.replace('_', ' ')} es obligatorio`);
                    valido = false;
                    return false;
                }
            });
            
            if (valido) {
                alert('Proveedor guardado exitosamente!');
                cancelar();
            }
        });
        
        // ❌ CANCELAR
        function cancelar() {
            const origen = new URLSearchParams(window.location.search).get('origen');
            if (origen === 'compras') {
                window.location.href = 'compra_form_new.php';
            } else {
                window.location.href = 'proveedores.php';
            }
        }
    </script>
</body>
</html>
