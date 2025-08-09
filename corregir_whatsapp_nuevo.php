<?php
// Script para arreglar la posición de la opción "Nuevo" en el selector de WhatsApp

$archivo = 'modulos/compras/proveedores.php';
$contenido = file_get_contents($archivo);

if ($contenido === false) {
    die("Error: No se puede leer el archivo $archivo");
}

echo "🔧 ARREGLANDO POSICIÓN DE OPCIÓN 'NUEVO' EN WHATSAPP...\n\n";

// Buscar la segunda instancia (WhatsApp) y corregir la indentación
$lineas = explode("\n", $contenido);
$whatsappFound = false;
$telefonoFound = false;

for ($i = 0; $i < count($lineas); $i++) {
    $linea = $lineas[$i];
    
    // Detectar si estamos en la sección de teléfono o WhatsApp
    if (strpos($linea, 'Teléfono') !== false) {
        $telefonoFound = true;
        $whatsappFound = false;
    } elseif (strpos($linea, 'WhatsApp') !== false) {
        $whatsappFound = true;
        $telefonoFound = false;
    }
    
    // Corregir línea mal indentada en WhatsApp
    if ($whatsappFound && strpos($linea, '<option value="nuevo">➕ Agregar Nuevo País</option>') !== false && strpos($linea, '                                        ') === false) {
        $lineas[$i] = '                                            <option value="nuevo">➕ Agregar Nuevo País</option>';
        echo "✅ Corregida indentación de opción 'Nuevo' en WhatsApp\n";
        break;
    }
}

$nuevoContenido = implode("\n", $lineas);

// Guardar cambios
if ($nuevoContenido !== $contenido) {
    if (file_put_contents($archivo, $nuevoContenido)) {
        echo "🎉 CORRECCIÓN APLICADA: Indentación corregida\n";
    } else {
        echo "❌ ERROR: No se pudieron guardar los cambios\n";
    }
} else {
    echo "⚠️ No se realizaron cambios - El archivo ya estaba correcto\n";
}

echo "\n🧪 VERIFICANDO RESULTADO...\n";

// Verificar que tenemos las dos opciones "Nuevo" correctamente posicionadas
$conteo = substr_count($nuevoContenido, '<option value="nuevo">➕ Agregar Nuevo País</option>');
echo "📊 Opciones 'Nuevo' encontradas: $conteo (debe ser 2)\n";

if ($conteo == 2) {
    echo "✅ PERFECTO: Ambos selectores (Teléfono y WhatsApp) tienen la opción 'Nuevo'\n";
} else {
    echo "⚠️ ATENCIÓN: Se esperaban 2 opciones 'Nuevo', verificar manualmente\n";
}
?>
