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
            --success-color: #198754;
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
        
        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
        }
        
        .form-container {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control, .form-select {
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        /* 🌍 SELECTOR DE PAÍSES CON BANDERAS */
        .phone-container {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }
        
        .country-selector {
            flex: 2;
            min-width: 250px;
        }
        
        .phone-code-display {
            width: 90px;
            background: #f8f9fa;
            border: 2px solid #e1e5e9;
            text-align: center;
            font-weight: bold;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
        
        .phone-number-input {
            flex: 2;
            min-width: 180px;
        }
        
        /* 🎨 SELECT2 CON ICONOS DE PAÍSES */
        .select2-container--default .select2-selection--single {
            height: 50px !important;
            border: 2px solid #e1e5e9 !important;
            border-radius: 8px !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 46px !important;
            padding-left: 15px !important;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .country-icon {
            width: 20px;
            height: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 2px;
            font-size: 10px;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
        }
        
        .country-icon.ar { background: linear-gradient(to bottom, #74B5FF 33%, white 33%, white 66%, #74B5FF 66%); }
        .country-icon.us { background: linear-gradient(45deg, #B22234, #3C3B6E); }
        .country-icon.mx { background: linear-gradient(to right, #006847 33%, white 33%, white 66%, #CE1126 66%); }
        .country-icon.es { background: linear-gradient(to bottom, #AA151B 25%, #F1BF00 25%, #F1BF00 75%, #AA151B 75%); }
        .country-icon.cl { background: linear-gradient(to bottom, #0039A6 50%, white 50%); }
        .country-icon.co { background: linear-gradient(to bottom, #FDE047 50%, #3B82F6 50%, #EF4444 75%); }
        .country-icon.br { background: linear-gradient(45deg, #009739, #FEDD00); }
        
        .country-code {
            color: #666;
            font-size: 0.9em;
            margin-left: auto;
        }
        
        /* 📱 RESPONSIVE */
        @media (max-width: 768px) {
            .phone-container {
                flex-direction: column;
            }
            
            .country-selector, .phone-number-input {
                min-width: 100%;
            }
            
            .phone-code-display {
                width: 100%;
                order: 2;
            }
        }
        
        .btn-action {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4);
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
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
                <!-- Información Básica -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-hashtag me-2"></i>Código *
                            </label>
                            <input type="text" class="form-control" name="codigo" required
                                placeholder="Ej: PROV001">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-id-card me-2"></i>CUIT/CUIL
                            </label>
                            <input type="text" class="form-control" name="cuit"
                                placeholder="Ej: 20-12345678-9">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-building me-2"></i>Razón Social *
                    </label>
                    <input type="text" class="form-control" name="razon_social" required
                        placeholder="Nombre oficial de la empresa">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" class="form-control" name="email"
                        placeholder="correo@empresa.com">
                </div>
                
                <!-- 📍 PAÍS BASE -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-globe me-2"></i>País *
                    </label>
                    <select class="form-select country-select" id="pais-base" name="pais" required>
                        <option value="">Seleccionar país...</option>
                        <option value="Argentina" data-code="+54" data-icon="ar" selected>Argentina</option>
                        <option value="Estados Unidos" data-code="+1" data-icon="us">Estados Unidos</option>
                        <option value="México" data-code="+52" data-icon="mx">México</option>
                        <option value="España" data-code="+34" data-icon="es">España</option>
                        <option value="Chile" data-code="+56" data-icon="cl">Chile</option>
                        <option value="Colombia" data-code="+57" data-icon="co">Colombia</option>
                        <option value="Brasil" data-code="+55" data-icon="br">Brasil</option>
                        <option value="Perú" data-code="+51" data-flag="pe.png">Perú</option>
                        <option value="Uruguay" data-code="+598" data-flag="uy.png">Uruguay</option>
                        <option value="Paraguay" data-code="+595" data-flag="py.png">Paraguay</option>
                    </select>
                </div>
                
                <!-- 🌍 TELÉFONO CON BANDERAS -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-phone me-2"></i>Teléfono
                    </label>
                    <div class="phone-container">
                        <select class="form-select country-selector" id="telefono-pais" name="telefono_pais">
                            <option value="">Seleccionar país...</option>
                            <option value="Argentina" data-code="+54" data-icon="ar" selected>Argentina</option>
                            <option value="Estados Unidos" data-code="+1" data-icon="us">Estados Unidos</option>
                            <option value="México" data-code="+52" data-icon="mx">México</option>
                            <option value="España" data-code="+34" data-icon="es">España</option>
                            <option value="Chile" data-code="+56" data-icon="cl">Chile</option>
                            <option value="Colombia" data-code="+57" data-icon="co">Colombia</option>
                            <option value="Brasil" data-code="+55" data-icon="br">Brasil</option>
                        </select>
                        <div class="phone-code-display" id="telefono-codigo">+54</div>
                        <input type="text" class="form-control phone-number-input" name="telefono" 
                               placeholder="11 1234 5678">
                    </div>
                </div>
                
                <!-- 📱 WHATSAPP -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fab fa-whatsapp me-2"></i>WhatsApp
                    </label>
                    <div class="phone-container">
                        <select class="form-select country-selector" id="whatsapp-pais" name="whatsapp_pais">
                            <option value="">Seleccionar país...</option>
                            <option value="Argentina" data-code="+54" data-icon="ar" selected>Argentina</option>
                            <option value="Estados Unidos" data-code="+1" data-icon="us">Estados Unidos</option>
                            <option value="México" data-code="+52" data-icon="mx">México</option>
                            <option value="España" data-code="+34" data-icon="es">España</option>
                            <option value="Chile" data-code="+56" data-icon="cl">Chile</option>
                            <option value="Colombia" data-code="+57" data-icon="co">Colombia</option>
                            <option value="Brasil" data-code="+55" data-icon="br">Brasil</option>
                        </select>
                        <div class="phone-code-display" id="whatsapp-codigo">+54</div>
                        <input type="text" class="form-control phone-number-input" name="whatsapp" 
                               placeholder="11 1234 5678">
                    </div>
                </div>
                
                <!-- Información Adicional -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt me-2"></i>Dirección
                            </label>
                            <input type="text" class="form-control" name="direccion"
                                placeholder="Calle y número">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-city me-2"></i>Ciudad
                            </label>
                            <input type="text" class="form-control" name="ciudad"
                                placeholder="Ciudad">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-sticky-note me-2"></i>Observaciones
                    </label>
                    <textarea class="form-control" name="observaciones" rows="3"
                        placeholder="Notas adicionales sobre el proveedor..."></textarea>
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
    
    <script>
        $(document).ready(function() {
            // 🌍 CONFIGURAR SELECT2 CON BANDERAS
            $('.country-select, .country-selector').select2({
                placeholder: 'Buscar país...',
                allowClear: true,
                templateResult: formatCountryWithFlag,
                templateSelection: formatCountrySelection
            });
            
            // 🔄 CONFIGURAR EVENTOS
            setupCountrySelector('telefono-pais', 'telefono-codigo');
            setupCountrySelector('whatsapp-pais', 'whatsapp-codigo');
            
            // 🎯 SINCRONIZACIÓN
            $('#pais-base').on('change', function() {
                const value = $(this).val();
                if (value) {
                    $('#telefono-pais, #whatsapp-pais').val(value).trigger('change');
                }
            });
            
            $('#telefono-pais').on('change', function() {
                const value = $(this).val();
                if (value) {
                    $('#whatsapp-pais').val(value).trigger('change');
                }
            });
            
            // 🚀 INICIALIZAR CON ARGENTINA
            $('#pais-base, #telefono-pais, #whatsapp-pais').val('Argentina').trigger('change');
        });
        
        // 🎨 FORMATEAR CON ICONOS DE PAÍSES
        function formatCountryWithFlag(option) {
            if (!option.id) return option.text;
            
            const $option = $(option.element);
            const icon = $option.data('icon');
            const code = $option.data('code');
            
            if (!icon) return option.text;
            
            return $(`
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="country-icon ${icon}">${icon.toUpperCase()}</div>
                    <span>${option.text}</span>
                    <span class="country-code">${code || ''}</span>
                </div>
            `);
        }
        
        function formatCountrySelection(option) {
            const $option = $(option.element);
            const icon = $option.data('icon');
            
            if (!icon) return option.text;
            
            return $(`
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="country-icon ${icon}">${icon.toUpperCase()}</div>
                    <span>${option.text}</span>
                </div>
            `);
        }
        
        // ⚡ CONFIGURAR SELECTOR
        function setupCountrySelector(selectId, displayId) {
            $(`#${selectId}`).on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const code = selectedOption.data('code');
                $(`#${displayId}`).text(code || '');
            });
        }
        
        // 📝 ENVÍO
        $('#form-nuevo-proveedor').on('submit', function(e) {
            e.preventDefault();
            alert('Proveedor guardado exitosamente!');
            cancelar();
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
