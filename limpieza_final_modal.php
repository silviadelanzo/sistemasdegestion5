<?php
// Script final para limpiar completamente el modal en compra_form_new.php

$archivo = 'modulos/compras/compra_form_new.php';
$contenido = file_get_contents($archivo);

if ($contenido === false) {
    die("Error: No se puede leer el archivo $archivo");
}

echo "🧹 LIMPIEZA FINAL DEL MODAL EN COMPRA_FORM_NEW.PHP...\n\n";

$lineas = explode("\n", $contenido);
$inicioEliminar = -1;
$finEliminar = -1;

// Buscar desde la línea después del include hasta Scripts
for ($i = 0; $i < count($lineas); $i++) {
    $linea = trim($lineas[$i]);
    
    // Encontrar el include del modal común
    if (strpos($lineas[$i], 'modal_proveedor_comun.php') !== false) {
        // El resto del modal viejo empieza en la siguiente línea
        $inicioEliminar = $i + 1;
    }
    
    // Encontrar donde empiezan los scripts
    if ($linea === '<!-- Scripts -->') {
        $finEliminar = $i - 1; // Línea anterior a Scripts
        break;
    }
}

if ($inicioEliminar > -1 && $finEliminar > -1) {
    echo "📍 Contenido a eliminar: líneas " . ($inicioEliminar + 1) . " a " . ($finEliminar + 1) . "\n";
    
    // Mostrar algunas líneas que se van a eliminar
    echo "🗑️ Contenido a eliminar:\n";
    for ($i = $inicioEliminar; $i <= min($inicioEliminar + 5, $finEliminar); $i++) {
        echo "   " . trim($lineas[$i]) . "\n";
    }
    if ($finEliminar - $inicioEliminar > 5) {
        echo "   ... y " . ($finEliminar - $inicioEliminar - 5) . " líneas más\n";
    }
    
    // Eliminar las líneas del modal viejo
    $nuevasLineas = array_merge(
        array_slice($lineas, 0, $inicioEliminar),
        array_slice($lineas, $finEliminar + 1)
    );
    
    $nuevoContenido = implode("\n", $nuevasLineas);
    
    if (file_put_contents($archivo, $nuevoContenido)) {
        echo "\n✅ LIMPIEZA FINAL COMPLETADA\n";
        echo "📄 Eliminadas " . ($finEliminar - $inicioEliminar + 1) . " líneas\n";
        echo "🎉 MODAL UNIFICADO IMPLEMENTADO CORRECTAMENTE\n";
    } else {
        echo "❌ ERROR: No se pudo guardar el archivo\n";
    }
} else {
    echo "❌ No se pudo detectar automáticamente el contenido a eliminar\n";
    echo "🔍 Debug info:\n";
    echo "Include encontrado en línea: " . ($inicioEliminar) . "\n";
    echo "Scripts encontrados en línea: " . ($finEliminar + 1) . "\n";
}
?>
