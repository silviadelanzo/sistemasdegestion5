<?php
// Script para reemplazar completamente la sección JavaScript de manejo de países

$archivo = 'modulos/compras/proveedores.php';
$contenido = file_get_contents($archivo);

if ($contenido === false) {
    die("Error: No se puede leer el archivo $archivo");
}

echo "🔧 REEMPLAZANDO SECCIÓN JAVASCRIPT COMPLETA...\n\n";

// Encontrar el inicio de la sección JavaScript de países
$inicioPatron = "/\/\/ Manejar.*selector.*teléfono.*document\.addEventListener\('DOMContentLoaded', function\(\) \{/s";
$finPatron = "/\s*}\s*}\);\s*}\s*<\/script>/s";

// JavaScript nuevo y limpio
$nuevoJavaScript = "        // 📱 MANEJO INTELIGENTE DE SELECTORES DE PAÍS
        document.addEventListener('DOMContentLoaded', function() {
            const telefonoCodSelect = document.getElementById('telefono_cod_pais');
            const whatsappCodSelect = document.getElementById('whatsapp_cod_pais');
            const telefonoInput = document.getElementById('telefono_numero');
            const whatsappInput = document.getElementById('whatsapp_numero');
            
            // 🔍 BASE DE DATOS DE CÓDIGOS TELEFÓNICOS
            const codigosPaises = {
                'luxemburgo': '+352', 'suiza': '+41', 'austria': '+43', 'bélgica': '+32',
                'holanda': '+31', 'dinamarca': '+45', 'suecia': '+46', 'noruega': '+47',
                'finlandia': '+358', 'islandia': '+354', 'irlanda': '+353', 'polonia': '+48',
                'república checa': '+420', 'hungría': '+36', 'rumania': '+40', 'bulgaria': '+359',
                'grecia': '+30', 'croacia': '+385', 'eslovenia': '+386', 'eslovaquia': '+421',
                'estonia': '+372', 'letonia': '+371', 'lituania': '+370', 'malta': '+356',
                'chipre': '+357', 'portugal': '+351', 'turquía': '+90', 'rusia': '+7',
                'ucrania': '+380', 'belarus': '+375', 'moldova': '+373', 'georgia': '+995',
                'armenia': '+374', 'azerbaiyán': '+994', 'kazajistán': '+7', 'uzbekistán': '+998',
                'turkmenistán': '+993', 'tayikistán': '+992', 'kirguistán': '+996',
                'india': '+91', 'pakistán': '+92', 'bangladesh': '+880', 'sri lanka': '+94',
                'nepal': '+977', 'bután': '+975', 'maldivas': '+960', 'afganistán': '+93',
                'irán': '+98', 'irak': '+964', 'kuwait': '+965', 'arabia saudí': '+966',
                'emiratos árabes unidos': '+971', 'qatar': '+974', 'bahréin': '+973',
                'omán': '+968', 'yemen': '+967', 'jordania': '+962', 'líbano': '+961',
                'siria': '+963', 'israel': '+972', 'palestina': '+970', 'egipto': '+20',
                'libia': '+218', 'túnez': '+216', 'argelia': '+213', 'marruecos': '+212',
                'sudán': '+249', 'etiopía': '+251', 'kenia': '+254', 'uganda': '+256',
                'tanzania': '+255', 'ruanda': '+250', 'burundi': '+257', 'madagascar': '+261',
                'mauricio': '+230', 'seychelles': '+248', 'comoras': '+269', 'mayotte': '+262',
                'sudáfrica': '+27', 'namibia': '+264', 'botswana': '+267', 'zimbabwe': '+263',
                'zambia': '+260', 'malawi': '+265', 'mozambique': '+258', 'suazilandia': '+268',
                'lesotho': '+266', 'australia': '+61', 'nueva zelanda': '+64', 'papúa nueva guinea': '+675',
                'fiyi': '+679', 'vanuatu': '+678', 'nueva caledonia': '+687', 'samoa': '+685',
                'tonga': '+676', 'kiribati': '+686', 'tuvalu': '+688', 'nauru': '+674',
                'palau': '+680', 'micronesia': '+691', 'islas marshall': '+692', 'corea del sur': '+82',
                'corea del norte': '+850', 'mongolia': '+976', 'vietnam': '+84', 'camboya': '+855',
                'laos': '+856', 'tailandia': '+66', 'myanmar': '+95', 'malasia': '+60',
                'singapur': '+65', 'brunéi': '+673', 'indonesia': '+62', 'filipinas': '+63',
                'timor oriental': '+670', 'taiwán': '+886', 'hong kong': '+852', 'macao': '+853'
            };
            
            // 📱 MANEJAR SELECCIÓN DE PAÍS
            function configurarSelector(selectElement, inputElement) {
                selectElement.addEventListener('change', function() {
                    if (this.value === 'nuevo') {
                        manejarNuevoPais(selectElement, inputElement);
                    } else if (this.value && this.value !== '') {
                        // ✅ MOSTRAR CÓDIGO EN INPUT AUTOMÁTICAMENTE
                        inputElement.value = this.value;
                        inputElement.focus();
                    }
                });
            }
            
            // 🆕 MANEJAR NUEVO PAÍS (SOLO PIDE NOMBRE)
            function manejarNuevoPais(selectElement, inputElement) {
                const nombrePais = prompt('🏳️ Ingrese el nombre del país:', '');
                
                if (nombrePais && nombrePais.trim() !== '') {
                    const nombreLimpio = nombrePais.trim().toLowerCase();
                    
                    // 🔍 VALIDAR SI YA EXISTE
                    const yaExiste = Array.from(selectElement.options).some(option => {
                        const textoOpcion = (option.textContent || option.innerText).toLowerCase();
                        return textoOpcion.includes(nombreLimpio) && option.value !== 'nuevo';
                    });
                    
                    if (yaExiste) {
                        alert(`❌ El país \"${nombrePais}\" ya existe en la lista.`);
                        selectElement.selectedIndex = 0;
                        return;
                    }
                    
                    // 🔍 BUSCAR CÓDIGO AUTOMÁTICAMENTE
                    const codigoEncontrado = codigosPaises[nombreLimpio];
                    
                    if (codigoEncontrado) {
                        // Verificar que el código no esté duplicado
                        const codigoExiste = Array.from(selectElement.options).some(option => 
                            option.value === codigoEncontrado
                        );
                        
                        if (codigoExiste) {
                            alert(`❌ El código ${codigoEncontrado} ya está asignado a otro país.`);
                            selectElement.selectedIndex = 0;
                            return;
                        }
                        
                        // ✅ AGREGAR PAÍS CON ÉXITO
                        agregarPaisASelector(selectElement, nombrePais, codigoEncontrado, inputElement);
                        sincronizarOtroSelector(selectElement, nombrePais, codigoEncontrado);
                        
                        alert(`✅ ${nombrePais} agregado exitosamente (${codigoEncontrado})`);
                    } else {
                        alert(`⚠️ No se encontró código para \"${nombrePais}\".\\n\\nPaíses disponibles: Luxemburgo, Suiza, Austria, Bélgica, etc.`);
                        selectElement.selectedIndex = 0;
                    }
                } else {
                    selectElement.selectedIndex = 0;
                }
            }
            
            // ➕ AGREGAR PAÍS AL SELECTOR
            function agregarPaisASelector(selectElement, nombrePais, codigo, inputElement) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = codigo;
                nuevaOpcion.textContent = `🌍 ${nombrePais}`;
                
                const opcionNuevo = selectElement.querySelector('option[value=\"nuevo\"]');
                selectElement.insertBefore(nuevaOpcion, opcionNuevo);
                
                selectElement.value = codigo;
                inputElement.value = codigo;
                inputElement.focus();
            }
            
            // 🔄 SINCRONIZAR CON EL OTRO SELECTOR
            function sincronizarOtroSelector(selectorActual, nombrePais, codigo) {
                const otroSelector = (selectorActual === telefonoCodSelect) ? whatsappCodSelect : telefonoCodSelect;
                
                if (otroSelector) {
                    const yaExiste = Array.from(otroSelector.options).some(option => option.value === codigo);
                    
                    if (!yaExiste) {
                        const nuevaOpcion = document.createElement('option');
                        nuevaOpcion.value = codigo;
                        nuevaOpcion.textContent = `🌍 ${nombrePais}`;
                        
                        const opcionNuevo = otroSelector.querySelector('option[value=\"nuevo\"]');
                        otroSelector.insertBefore(nuevaOpcion, opcionNuevo);
                    }
                }
            }
            
            // 🚀 CONFIGURAR AMBOS SELECTORES
            if (telefonoCodSelect && telefonoInput) {
                configurarSelector(telefonoCodSelect, telefonoInput);
            }
            
            if (whatsappCodSelect && whatsappInput) {
                configurarSelector(whatsappCodSelect, whatsappInput);
            }
        });
    </script>";

// Buscar y reemplazar desde el inicio de la sección hasta el final
$patron = '/\/\/ Manejar.*?<\/script>/s';

if (preg_match($patron, $contenido)) {
    $nuevoContenido = preg_replace($patron, $nuevoJavaScript, $contenido);
    
    if ($nuevoContenido && $nuevoContenido !== $contenido) {
        if (file_put_contents($archivo, $nuevoContenido)) {
            echo "✅ JAVASCRIPT REEMPLAZADO EXITOSAMENTE\n";
            echo "📱 Funcionalidades implementadas:\n";
            echo "   • Solo pide nombre del país\n";
            echo "   • Búsqueda automática de código\n";
            echo "   • Al seleccionar muestra código en input\n";
            echo "   • Sincronización entre selectores\n";
            echo "   • Sin abreviaturas en las opciones\n";
        } else {
            echo "❌ ERROR: No se pudo guardar el archivo\n";
        }
    } else {
        echo "⚠️ No se detectaron cambios necesarios\n";
    }
} else {
    echo "❌ ERROR: No se encontró la sección JavaScript para reemplazar\n";
}
?>
