<?php
// Script para limpiar el modal duplicado en compra_form_new.php

$archivo = 'modulos/compras/compra_form_new.php';
$contenido = file_get_contents($archivo);

if ($contenido === false) {
    die("Error: No se puede leer el archivo $archivo");
}

echo "🧹 LIMPIANDO MODAL DUPLICADO EN COMPRA_FORM_NEW.PHP...\n\n";

// Patrón para buscar desde el form hasta el final del modal
$patron = '/\s*<form id="form-nuevo-proveedor">.*?<\/div>\s*<\/div>\s*<\/div>/s';

$lineas = explode("\n", $contenido);
$inicioModal = -1;
$finModal = -1;

// Encontrar donde inicia el contenido del modal viejo
for ($i = 0; $i < count($lineas); $i++) {
    if (strpos($lineas[$i], 'modal_proveedor_comun.php') !== false) {
        $inicioModal = $i + 1;
        break;
    }
}

// Buscar el cierre del modal original (3 </div> seguidos)
if ($inicioModal > -1) {
    $divsCerrados = 0;
    for ($i = $inicioModal; $i < count($lineas); $i++) {
        $linea = trim($lineas[$i]);
        if ($linea === '</div>') {
            $divsCerrados++;
            if ($divsCerrados >= 3) {
                $finModal = $i;
                break;
            }
        } else {
            $divsCerrados = 0;
        }
    }
}

if ($inicioModal > -1 && $finModal > -1) {
    echo "📍 Modal viejo encontrado: líneas " . ($inicioModal + 1) . " a " . ($finModal + 1) . "\n";
    
    // Remover las líneas del modal viejo
    $nuevasLineas = array_merge(
        array_slice($lineas, 0, $inicioModal),
        array_slice($lineas, $finModal + 1)
    );
    
    $nuevoContenido = implode("\n", $nuevasLineas);
    
    if (file_put_contents($archivo, $nuevoContenido)) {
        echo "✅ MODAL DUPLICADO ELIMINADO EXITOSAMENTE\n";
        echo "📄 Eliminadas " . ($finModal - $inicioModal + 1) . " líneas del modal viejo\n";
        echo "📄 El archivo ahora usa el modal común\n";
    } else {
        echo "❌ ERROR: No se pudo guardar el archivo\n";
    }
} else {
    echo "❌ No se pudo detectar automáticamente el modal\n";
    echo "🔍 Buscando include del modal común...\n";
    
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
