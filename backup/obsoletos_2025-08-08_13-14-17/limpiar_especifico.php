<?php
// Script específico para limpiar desde línea 594 hasta scripts

$archivo = 'modulos/compras/proveedores.php';
$contenido = file_get_contents($archivo);

echo "🧹 LIMPIEZA ESPECÍFICA DE PROVEEDORES.PHP...\n\n";

$lineas = explode("\n", $contenido);

// Eliminar desde línea 593 (índice 593) hasta encontrar <script>
$inicioEliminar = 593; // línea 594 en el archivo
$finEliminar = -1;

for ($i = $inicioEliminar; $i < count($lineas); $i++) {
    $linea = trim($lineas[$i]);
    if ($linea === '<script>' || strpos($linea, '<script') === 0) {
        $finEliminar = $i - 1;
        break;
    }
}

if ($finEliminar > -1) {
    echo "📍 Eliminando líneas " . ($inicioEliminar + 1) . " a " . ($finEliminar + 1) . "\n";
    
    // Mostrar algunas líneas que se van a eliminar
    echo "🗑️ Primeras líneas a eliminar:\n";
    for ($i = $inicioEliminar; $i <= min($inicioEliminar + 5, $finEliminar); $i++) {
        echo "   " . trim($lineas[$i]) . "\n";
    }
    echo "   ... y " . ($finEliminar - $inicioEliminar - 5) . " líneas más\n";
    
    // Eliminar las líneas del modal viejo
    $nuevasLineas = array_merge(
        array_slice($lineas, 0, $inicioEliminar),
        array_slice($lineas, $finEliminar + 1)
    );
    
    $nuevoContenido = implode("\n", $nuevasLineas);
    
    if (file_put_contents($archivo, $nuevoContenido)) {
        echo "\n✅ LIMPIEZA ESPECÍFICA COMPLETADA\n";
        echo "📄 Eliminadas " . ($finEliminar - $inicioEliminar + 1) . " líneas\n";
        echo "🎉 PROVEEDORES.PHP COMPLETAMENTE LIMPIO\n";
    } else {
        echo "❌ ERROR: No se pudo guardar el archivo\n";
    }
} else {
    echo "❌ No se encontró el final (script)\n";
}
?>
