<?php
// Script para limpiar el modal duplicado en proveedores.php

$archivo = 'modulos/compras/proveedores.php';
$contenido = file_get_contents($archivo);

if ($contenido === false) {
    die("Error: No se puede leer el archivo $archivo");
}

echo "🧹 LIMPIANDO MODAL DUPLICADO EN PROVEEDORES.PHP...\n\n";

// Buscar desde modal-dialog hasta el cierre del modal
$patron = '/\s*<div class="modal-dialog modal-lg">.*?<\/div>\s*<\/div>\s*<\/div>/s';

if (preg_match($patron, $contenido)) {
    $nuevoContenido = preg_replace($patron, '', $contenido);
    
    if ($nuevoContenido && $nuevoContenido !== $contenido) {
        if (file_put_contents($archivo, $nuevoContenido)) {
            echo "✅ MODAL DUPLICADO ELIMINADO EXITOSAMENTE\n";
            echo "📄 El archivo ahora usa el modal común\n";
        } else {
            echo "❌ ERROR: No se pudo guardar el archivo\n";
        }
    } else {
        echo "⚠️ No se detectaron cambios\n";
    }
} else {
    echo "❌ ERROR: No se encontró el patrón del modal duplicado\n";
    echo "📝 Revisando contenido...\n";
    
    // Mostrar líneas alrededor del include para diagnóstico
    $lineas = explode("\n", $contenido);
    for ($i = 0; $i < count($lineas); $i++) {
        if (strpos($lineas[$i], 'modal_proveedor_comun.php') !== false) {
            echo "Línea " . ($i + 1) . ": " . trim($lineas[$i]) . "\n";
            for ($j = $i + 1; $j <= min($i + 10, count($lineas) - 1); $j++) {
                echo "Línea " . ($j + 1) . ": " . trim($lineas[$j]) . "\n";
            }
            break;
        }
    }
}
?>
