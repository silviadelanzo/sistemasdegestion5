<?php
// Script para corregir banderas corruptas y agregar opción "Nuevo" en proveedores.php

$archivo = '../modulos/compras/proveedores.php';
$contenido = file_get_contents($archivo);

if ($contenido === false) {
    die("Error: No se puede leer el archivo $archivo");
}

echo "🔧 CORRIGIENDO BANDERAS Y AGREGANDO OPCIÓN NUEVO...\n\n";

// Correcciones de banderas corruptas
$correcciones = [
    "'España': \$bandera = '�🇸';" => "'España': \$bandera = '🇪🇸';",
    "'China': \$bandera = '🇨�';" => "'China': \$bandera = '🇨🇳';", 
    "'Francia': \$bandera = '��';" => "'Francia': \$bandera = '🇫🇷';",
    "'Alemania': \$bandera = '��';" => "'Alemania': \$bandera = '🇩🇪';",
    "'Paraguay': \$bandera = '��';" => "'Paraguay': \$bandera = '🇵🇾';",
    "'Venezuela': \$bandera = '��';" => "'Venezuela': \$bandera = '🇻🇪';",
    "default: \$bandera = '�';" => "default: \$bandera = '🌍';"
];

$cambios = 0;
foreach ($correcciones as $buscar => $reemplazar) {
    $nuevoContenido = str_replace($buscar, $reemplazar, $contenido);
    if ($nuevoContenido !== $contenido) {
        $contenido = $nuevoContenido;
        $cambios++;
        echo "✅ Corregido: $buscar\n";
    }
}

// Agregar opción "Nuevo" después del foreach de países telefónicos
$patron = '/(foreach \(\$lista_paises_telefonicos as \$nombrePais => \$codigoTel\) \{.*?\}\s*\?>\s*)/s';
if (preg_match($patron, $contenido)) {
    $nuevoContenido = preg_replace(
        $patron,
        '$1<option value="nuevo">➕ Agregar Nuevo País</option>' . "\n                                            ",
        $contenido
    );
    if ($nuevoContenido !== $contenido) {
        $contenido = $nuevoContenido;
        $cambios++;
        echo "✅ Agregada opción 'Nuevo País' en selector de teléfono\n";
    }
}

// Guardar cambios
if ($cambios > 0) {
    if (file_put_contents($archivo, $contenido)) {
        echo "\n🎉 CORRECCIONES APLICADAS: $cambios cambios realizados\n";
        echo "📱 Banderas corregidas y opción 'Nuevo' agregada\n";
    } else {
        echo "\n❌ ERROR: No se pudieron guardar los cambios\n";
    }
} else {
    echo "\n⚠️ No se realizaron cambios - El archivo ya estaba correcto\n";
}
?>
