<?php
// Script para limpiar completamente el modal roto en proveedores.php

$archivo = '../modulos/compras/proveedores.php';
$contenido = file_get_contents($archivo);

if ($contenido === false) {
    die("Error: No se puede leer el archivo $archivo");
}

echo "🧹 LIMPIANDO MODAL ROTO EN PROVEEDORES.PHP...\n\n";

$lineas = explode("\n", $contenido);
$inicioEliminar = -1;
$finEliminar = -1;

// Buscar desde donde quedó contenido del modal hasta el final del script
for ($i = 0; $i < count($lineas); $i++) {
    $linea = trim($lineas[$i]);
    
    // Encontrar donde empieza el contenido del modal viejo
    if (strpos($linea, '<div class="row">') !== false) {
        // Verificar si es el inicio del modal viejo
        if ($i + 2 < count($lineas) && 
            strpos($lineas[$i + 2], 'Razón Social') !== false) {
            $inicioEliminar = $i;
        }
    }
    
    // Encontrar donde empiezan los scripts
    if ($linea === '<script>' || strpos($linea, '<script>') !== false) {
        $finEliminar = $i - 1;
        break;
    }
}

if ($inicioEliminar > -1 && $finEliminar > -1) {
    echo "📍 Contenido a eliminar: líneas " . ($inicioEliminar + 1) . " a " . ($finEliminar + 1) . "\n";
    
    // Eliminar las líneas del modal viejo
    $nuevasLineas = array_merge(
        array_slice($lineas, 0, $inicioEliminar),
        array_slice($lineas, $finEliminar + 1)
    );
    
    $nuevoContenido = implode("\n", $nuevasLineas);
    
    if (file_put_contents($archivo, $nuevoContenido)) {
        echo "✅ LIMPIEZA COMPLETADA\n";
        echo "📄 Eliminadas " . ($finEliminar - $inicioEliminar + 1) . " líneas\n";
        echo "🎉 PROVEEDORES.PHP AHORA ESTÁ LIMPIO\n";
    } else {
        echo "❌ ERROR: No se pudo guardar el archivo\n";
    }
} else {
    echo "❌ No se pudo detectar automáticamente el contenido a eliminar\n";
    echo "🔍 Debug info:\n";
    echo "Inicio encontrado en línea: " . ($inicioEliminar + 1) . "\n";
    echo "Scripts encontrados en línea: " . ($finEliminar + 1) . "\n";
    
    // Mostrar algunas líneas para diagnóstico
    for ($i = 590; $i <= 610; $i++) {
        if (isset($lineas[$i])) {
            echo "Línea " . ($i + 1) . ": " . trim($lineas[$i]) . "\n";
        }
    }
}
?>
