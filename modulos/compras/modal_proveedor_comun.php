<?php
// Modal común para nuevo proveedor - Se incluye en múltiples páginas
// Asegurar que $paises y $provincias estén disponibles desde la página padre
?>

<!-- Modal Nuevo Proveedor (Componente Común) -->
<div class="modal fade" id="modalNuevoProveedor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>Nuevo Proveedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-nuevo-proveedor">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Código</label>
                                <input type="text" class="form-control" name="codigo" id="codigo_proveedor" readonly
                                    style="background-color: #e9ecef; font-weight: bold; color: #0d6efd;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">CUIT/CUIL</label>
                                <input type="text" class="form-control" name="cuit" placeholder="Ej: 20-12345678-9">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Razón Social</label>
                                <input type="text" class="form-control" name="razon_social" required
                                    placeholder="Nombre oficial de la empresa">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nombre Comercial</label>
                                <input type="text" class="form-control" name="nombre_comercial"
                                    placeholder="Nombre comercial o de fantasía">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">País</label>
                                <select class="form-select" name="pais" id="pais">
                                    <option value="">-- Seleccionar --</option>
                                    <?php
                                    if (isset($paises) && is_array($paises)) {
                                        foreach ($paises as $pais) {
                                            $selected = ($pais['id'] == 1) ? 'selected' : ''; // Argentina por defecto
                                            echo "<option value=\"{$pais['id']}\" $selected>{$pais['nombre']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Provincia/Estado</label>
                                <div class="d-flex">
                                    <select class="form-select me-2" name="provincia" id="provincia">
                                        <option value="">-- Seleccione país primero --</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-success btn-sm" style="min-width: 40px;">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ciudad</label>
                                <div class="d-flex">
                                    <select class="form-select me-2" name="ciudad" id="ciudad">
                                        <option value="">-- Seleccione provincia primero --</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-success btn-sm" style="min-width: 40px;">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dirección</label>
                        <input type="text" class="form-control" name="direccion" placeholder="Dirección completa">
                    </div>
                    
                    <!-- SECCIÓN TELEFÓNICA CORREGIDA -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Teléfono</label>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <select class="form-select" name="telefono_cod_pais" id="telefono_cod_pais">
                                            <?php
                                            if (isset($paises) && is_array($paises)) {
                                                foreach ($paises as $pais) {
                                                    if (!empty($pais['codigo_telefono'])) {
                                                        // Bandera emoji
                                                        $bandera = '';
                                                        switch($pais['nombre']) {
                                                            case 'Argentina': $bandera = '🇦🇷'; break;
                                                            case 'España': $bandera = '🇪🇸'; break;
                                                            case 'México': $bandera = '🇲🇽'; break;
                                                            case 'Colombia': $bandera = '🇨🇴'; break;
                                                            case 'Chile': $bandera = '🇨🇱'; break;
                                                            case 'Perú': $bandera = '🇵🇪'; break;
                                                            case 'Brasil': $bandera = '🇧🇷'; break;
                                                            case 'Estados Unidos': $bandera = '🇺🇸'; break;
                                                            case 'China': $bandera = '🇨🇳'; break;
                                                            case 'Japón': $bandera = '🇯🇵'; break;
                                                            case 'Francia': $bandera = '🇫🇷'; break;
                                                            case 'Italia': $bandera = '🇮🇹'; break;
                                                            case 'Alemania': $bandera = '🇩🇪'; break;
                                                            case 'Bolivia': $bandera = '🇧🇴'; break;
                                                            case 'Paraguay': $bandera = '🇵🇾'; break;
                                                            case 'Uruguay': $bandera = '🇺🇾'; break;
                                                            case 'Venezuela': $bandera = '🇻🇪'; break;
                                                            case 'Ecuador': $bandera = '🇪🇨'; break;
                                                            default: $bandera = '🌍';
                                                        }
                                                        $selected = ($pais['codigo_telefono'] == '+54') ? 'selected' : '';
                                                        echo "<option value=\"{$pais['codigo_telefono']}\" $selected>$bandera {$pais['nombre']}</option>";
                                                    }
                                                }
                                            }
                                            ?>
                                            <option value="nuevo">➕ Agregar Nuevo País</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <input type="text" class="form-control" id="telefono_codigo" name="telefono_codigo" 
                                               placeholder="+54" readonly style="background-color: #f8f9fa; font-weight: bold;">
                                    </div>
                                    <div class="col-4">
                                        <input type="text" class="form-control" id="telefono_numero" name="telefono_numero" 
                                               placeholder="Número">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">WhatsApp</label>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <select class="form-select" name="whatsapp_cod_pais" id="whatsapp_cod_pais">
                                            <?php
                                            if (isset($paises) && is_array($paises)) {
                                                foreach ($paises as $pais) {
                                                    if (!empty($pais['codigo_telefono'])) {
                                                        // Bandera emoji
                                                        $bandera = '';
                                                        switch($pais['nombre']) {
                                                            case 'Argentina': $bandera = '🇦🇷'; break;
                                                            case 'España': $bandera = '🇪🇸'; break;
                                                            case 'México': $bandera = '🇲🇽'; break;
                                                            case 'Colombia': $bandera = '🇨🇴'; break;
                                                            case 'Chile': $bandera = '🇨🇱'; break;
                                                            case 'Perú': $bandera = '🇵🇪'; break;
                                                            case 'Brasil': $bandera = '🇧🇷'; break;
                                                            case 'Estados Unidos': $bandera = '🇺🇸'; break;
                                                            case 'China': $bandera = '🇨🇳'; break;
                                                            case 'Japón': $bandera = '🇯🇵'; break;
                                                            case 'Francia': $bandera = '🇫🇷'; break;
                                                            case 'Italia': $bandera = '🇮🇹'; break;
                                                            case 'Alemania': $bandera = '🇩🇪'; break;
                                                            case 'Bolivia': $bandera = '🇧🇴'; break;
                                                            case 'Paraguay': $bandera = '🇵🇾'; break;
                                                            case 'Uruguay': $bandera = '🇺🇾'; break;
                                                            case 'Venezuela': $bandera = '🇻🇪'; break;
                                                            case 'Ecuador': $bandera = '🇪🇨'; break;
                                                            default: $bandera = '🌍';
                                                        }
                                                        $selected = ($pais['codigo_telefono'] == '+54') ? 'selected' : '';
                                                        echo "<option value=\"{$pais['codigo_telefono']}\" $selected>$bandera {$pais['nombre']}</option>";
                                                    }
                                                }
                                            }
                                            ?>
                                            <option value="nuevo">➕ Agregar Nuevo País</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <input type="text" class="form-control" id="whatsapp_codigo" name="whatsapp_codigo" 
                                               placeholder="+54" readonly style="background-color: #f8f9fa; font-weight: bold;">
                                    </div>
                                    <div class="col-4">
                                        <input type="text" class="form-control" id="whatsapp_numero" name="whatsapp_numero" 
                                               placeholder="Número">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="contacto@empresa.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Sitio Web</label>
                                <input type="url" class="form-control" name="sitio_web" placeholder="https://www.empresa.com">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="guardarNuevoProveedor()">
                    <i class="fas fa-save"></i> Guardar Proveedor
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para el modal común -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar selectores telefónicos
    configurarSelectorTelefonico('telefono_cod_pais', 'telefono_codigo');
    configurarSelectorTelefonico('whatsapp_cod_pais', 'whatsapp_codigo');
    
    // Configurar valores por defecto
    document.getElementById('telefono_codigo').value = '+54';
    document.getElementById('whatsapp_codigo').value = '+54';
});

function configurarSelectorTelefonico(selectorId, codigoInputId) {
    const selector = document.getElementById(selectorId);
    const codigoInput = document.getElementById(codigoInputId);
    
    if (selector && codigoInput) {
        selector.addEventListener('change', function() {
            if (this.value === 'nuevo') {
                manejarNuevoPaisTelefonico(selector, codigoInput);
            } else if (this.value && this.value !== '') {
                codigoInput.value = this.value;
            }
        });
    }
}

function manejarNuevoPaisTelefonico(selector, codigoInput) {
    const nombrePais = prompt('🏳️ Ingrese el nombre del país:', '');
    
    if (nombrePais && nombrePais.trim() !== '') {
        const nombreLimpio = nombrePais.trim().toLowerCase();
        
        // Base de datos de códigos telefónicos
        const codigosPaises = {
            'luxemburgo': '+352', 'suiza': '+41', 'austria': '+43', 'bélgica': '+32',
            'holanda': '+31', 'dinamarca': '+45', 'suecia': '+46', 'noruega': '+47',
            'finlandia': '+358', 'islandia': '+354', 'irlanda': '+353', 'polonia': '+48',
            'república checa': '+420', 'hungría': '+36', 'rumania': '+40', 'bulgaria': '+359',
            'grecia': '+30', 'croacia': '+385', 'eslovenia': '+386', 'eslovaquia': '+421',
            'portugal': '+351', 'turquía': '+90', 'rusia': '+7', 'ucrania': '+380'
        };
        
        const codigoEncontrado = codigosPaises[nombreLimpio];
        
        if (codigoEncontrado) {
            // Verificar si ya existe
            const yaExiste = Array.from(selector.options).some(option => 
                option.value === codigoEncontrado && option.value !== 'nuevo'
            );
            
            if (!yaExiste) {
                // Agregar nueva opción
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = codigoEncontrado;
                nuevaOpcion.textContent = `🌍 ${nombrePais}`;
                
                const opcionNuevo = selector.querySelector('option[value="nuevo"]');
                selector.insertBefore(nuevaOpcion, opcionNuevo);
                
                selector.value = codigoEncontrado;
                codigoInput.value = codigoEncontrado;
                
                // Sincronizar con el otro selector
                sincronizarSelectores(selector, nombrePais, codigoEncontrado);
                
                alert(`✅ ${nombrePais} agregado exitosamente (${codigoEncontrado})`);
            } else {
                alert(`❌ El código ${codigoEncontrado} ya existe en la lista.`);
                selector.selectedIndex = 0;
            }
        } else {
            alert(`⚠️ No se encontró código para "${nombrePais}".`);
            selector.selectedIndex = 0;
        }
    } else {
        selector.selectedIndex = 0;
    }
}

function sincronizarSelectores(selectorActual, nombrePais, codigo) {
    const telefonoSelect = document.getElementById('telefono_cod_pais');
    const whatsappSelect = document.getElementById('whatsapp_cod_pais');
    
    const otroSelector = (selectorActual === telefonoSelect) ? whatsappSelect : telefonoSelect;
    
    if (otroSelector) {
        const yaExiste = Array.from(otroSelector.options).some(option => option.value === codigo);
        
        if (!yaExiste) {
            const nuevaOpcion = document.createElement('option');
            nuevaOpcion.value = codigo;
            nuevaOpcion.textContent = `🌍 ${nombrePais}`;
            
            const opcionNuevo = otroSelector.querySelector('option[value="nuevo"]');
            otroSelector.insertBefore(nuevaOpcion, opcionNuevo);
        }
    }
}

// Reset del modal
if (document.getElementById('modalNuevoProveedor')) {
    document.getElementById('modalNuevoProveedor').addEventListener('hidden.bs.modal', function() {
        const form = document.getElementById('form-nuevo-proveedor');
        form.reset();
        
        // Restaurar valores por defecto
        document.getElementById('telefono_codigo').value = '+54';
        document.getElementById('whatsapp_codigo').value = '+54';
        document.getElementById('telefono_cod_pais').value = '+54';
        document.getElementById('whatsapp_cod_pais').value = '+54';
        
        // Regenerar código si existe la función
        if (typeof generarCodigoAutomatico === 'function') {
            generarCodigoAutomatico();
        }
    });
}
</script>
