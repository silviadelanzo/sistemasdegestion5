<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo WhatsApp Validator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>📱 DEMO: Validador WhatsApp con Banderas</h2>
                <p class="text-muted">Prueba el sistema de validación de números WhatsApp con detección automática de país y bandera.</p>
                <hr>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>🔧 Formulario de Proveedor</h5>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label for="razon_social" class="form-label">Razón Social</label>
                                <input type="text" class="form-control" id="razon_social" placeholder="Ej: Empresa XYZ S.A.">
                            </div>
                            
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" placeholder="Ej: 011-4567-8900">
                            </div>
                            
                            <div class="mb-3">
                                <label for="whatsapp_proveedor" class="form-label">WhatsApp <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" id="whatsapp_proveedor" class="form-control whatsapp-input" 
                                           placeholder="Ej: +5491123456789" maxlength="20">
                                    <div class="input-group-append">
                                        <span class="input-group-text country-flag" id="whatsapp_proveedor_flag"></span>
                                    </div>
                                </div>
                                <div id="whatsapp_proveedor_feedback" class="feedback-message"></div>
                                <small class="form-text text-muted">
                                    Formato: +CódigoPaís + Número (solo números, 10-15 dígitos)
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pais_proveedor" class="form-label">País</label>
                                <select class="form-control" id="pais_proveedor">
                                    <option value="">Seleccione...</option>
                                    <option value="AR" data-flag="🇦🇷" data-code="+54">🇦🇷 Argentina (+54)</option>
                                    <option value="BR" data-flag="🇧🇷" data-code="+55">🇧🇷 Brasil (+55)</option>
                                    <option value="CL" data-flag="🇨🇱" data-code="+56">🇨🇱 Chile (+56)</option>
                                    <option value="UY" data-flag="🇺🇾" data-code="+598">🇺🇾 Uruguay (+598)</option>
                                    <option value="PY" data-flag="🇵🇾" data-code="+595">🇵🇾 Paraguay (+595)</option>
                                    <option value="US" data-flag="🇺🇸" data-code="+1">🇺🇸 Estados Unidos (+1)</option>
                                </select>
                            </div>
                            
                            <button type="button" class="btn btn-primary">💾 Guardar Proveedor</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>👤 Formulario de Cliente</h5>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label for="nombre_cliente" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre_cliente" placeholder="Ej: Juan Pérez">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email_cliente" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email_cliente" placeholder="Ej: juan@email.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="whatsapp_cliente" class="form-label">WhatsApp <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" id="whatsapp_cliente" class="form-control whatsapp-input" 
                                           placeholder="Ej: +5491123456789" maxlength="20">
                                    <div class="input-group-append">
                                        <span class="input-group-text country-flag" id="whatsapp_cliente_flag"></span>
                                    </div>
                                </div>
                                <div id="whatsapp_cliente_feedback" class="feedback-message"></div>
                                <small class="form-text text-muted">
                                    Formato internacional con código de país
                                </small>
                            </div>
                            
                            <button type="button" class="btn btn-success">💾 Guardar Cliente</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>📋 Ejemplos de Uso</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>✅ Formatos Válidos:</h6>
                                <ul class="list-unstyled">
                                    <li><code>+5491123456789</code> - Argentina</li>
                                    <li><code>+5511987654321</code> - Brasil</li>
                                    <li><code>+56912345678</code> - Chile</li>
                                    <li><code>+59899123456</code> - Uruguay</li>
                                    <li><code>+59512345678</code> - Paraguay</li>
                                    <li><code>+12125551234</code> - Estados Unidos</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>❌ Formatos Inválidos:</h6>
                                <ul class="list-unstyled">
                                    <li><code>11-2345-6789</code> - Falta código país</li>
                                    <li><code>+54 11 2345-6789</code> - Contiene espacios/guiones</li>
                                    <li><code>+541123456</code> - Muy corto (menos 10 dígitos)</li>
                                    <li><code>+541123456789012345</code> - Muy largo (más 15 dígitos)</li>
                                    <li><code>+54abc1234567</code> - Contiene letras</li>
                                </ul>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6>🔧 Funcionalidades:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="alert alert-info">
                                    <strong>Validación en tiempo real</strong><br>
                                    <small>Verifica formato mientras escribes</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-success">
                                    <strong>Detección automática de país</strong><br>
                                    <small>Muestra bandera y nombre del país</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-warning">
                                    <strong>Solo números permitidos</strong><br>
                                    <small>Bloquea caracteres no válidos</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>🔗 Test de Enlaces WhatsApp</h5>
                    </div>
                    <div class="card-body">
                        <p>Ingresa un número válido arriba y prueba estos botones:</p>
                        <div id="whatsapp-links"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="actualizador_whatsapp.php" class="btn btn-warning">⚙️ Actualizar Base de Datos</a>
                <a href="analisis_completo_whatsapp.php" class="btn btn-info">🔍 Analizar Estructura Actual</a>
                <a href="test_proveedores.php" class="btn btn-secondary">🔙 Volver a Proveedores</a>
            </div>
        </div>
    </div>

    <script src="../../assets/js/whatsapp-validator.js"></script>
    <script>
        // Inicializar validadores
        document.addEventListener('DOMContentLoaded', function() {
            whatsappValidator.initializeValidator('whatsapp_proveedor');
            whatsappValidator.initializeValidator('whatsapp_cliente');
            
            // Función para actualizar enlaces de WhatsApp
            function updateWhatsAppLinks() {
                const numero1 = document.getElementById('whatsapp_proveedor').value;
                const numero2 = document.getElementById('whatsapp_cliente').value;
                const linksContainer = document.getElementById('whatsapp-links');
                
                let html = '';
                
                if (numero1 && whatsappValidator.validateWhatsApp(numero1).valid) {
                    html += whatsappValidator.createWhatsAppButton(numero1, 'Hola, me interesa su catálogo de productos', '📞 Contactar Proveedor');
                    html += ' ';
                }
                
                if (numero2 && whatsappValidator.validateWhatsApp(numero2).valid) {
                    html += whatsappValidator.createWhatsAppButton(numero2, 'Hola, su pedido está listo para entrega', '📱 Notificar Cliente');
                }
                
                if (!html) {
                    html = '<p class="text-muted">Ingresa números válidos para ver los botones de WhatsApp</p>';
                }
                
                linksContainer.innerHTML = html;
            }
            
            // Actualizar enlaces cuando cambien los números
            document.getElementById('whatsapp_proveedor').addEventListener('input', updateWhatsAppLinks);
            document.getElementById('whatsapp_cliente').addEventListener('input', updateWhatsAppLinks);
            
            // Autocompletar código de país al seleccionar país
            document.getElementById('pais_proveedor').addEventListener('change', function() {
                const selected = this.options[this.selectedIndex];
                const code = selected.getAttribute('data-code');
                if (code) {
                    const whatsappInput = document.getElementById('whatsapp_proveedor');
                    if (!whatsappInput.value) {
                        whatsappInput.value = code;
                        whatsappInput.focus();
                    }
                }
            });
        });
    </script>
</body>
</html>
