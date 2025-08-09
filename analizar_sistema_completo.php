<?php
/**
 * ANALIZADOR COMPLETO DEL SISTEMA
 * Identifica archivos funcionales, obsoletos y de prueba
 */

echo "<h1>🔍 ANÁLISIS COMPLETO DEL SISTEMA</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .funcional { color: green; font-weight: bold; }
    .obsoleto { color: red; }
    .prueba { color: orange; }
    .backup { color: blue; }
    .config { color: purple; }
    .modulo { background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid #333; }
</style>";

// Archivos del sistema principal
$archivosPrincipales = [
    'FUNCIONALES' => [
        'index.php' => 'Página principal del sistema',
        'login.php' => 'Sistema de autenticación',
        'logout.php' => 'Cierre de sesión',
        'menu_principal.php' => 'Menú principal del sistema',
        'obtener_ultimo_codigo.php' => 'Generador de códigos automáticos'
    ],
    'CONFIGURACION' => [
        'config/' => 'Carpeta de configuración',
        '.vscode/' => 'Configuración de VS Code',
        'assets/' => 'Recursos estáticos (CSS, JS, imágenes)'
    ],
    'AJAX' => [
        'ajax/' => 'Endpoints AJAX para operaciones dinámicas'
    ],
    'OBSOLETOS_PROBABLES' => [
        'test_*.php' => 'Archivos de prueba',
        'verificar_*.php' => 'Scripts de verificación temporal',
        'debug_*.php' => 'Scripts de debug',
        'diagnostico_*.php' => 'Scripts de diagnóstico',
        'crear_*.php' => 'Scripts de creación (algunos obsoletos)',
        'corregir_*.php' => 'Scripts de corrección (algunos obsoletos)',
        'limpiar_*.php' => 'Scripts de limpieza temporal',
        'migrar_*.php' => 'Scripts de migración (ya ejecutados)',
        'reparar_*.php' => 'Scripts de reparación temporal'
    ],
    'BACKUP_SCRIPTS' => [
        '*.ps1' => 'Scripts de PowerShell para backups',
        'backup_*.php' => 'Scripts de backup'
    ],
    'DOCUMENTACION' => [
        '*.md' => 'Documentación del proyecto',
        '*.html' => 'Documentación HTML'
    ]
];

// Escanear directorio actual
$archivosEncontrados = scandir('.');
$archivosCategorizados = [
    'CORE_SISTEMA' => [],
    'MODULOS' => [],
    'CONFIGURACION' => [],
    'AJAX' => [],
    'REPORTES' => [],
    'PRUEBAS' => [],
    'BACKUP_SCRIPTS' => [],
    'MIGRACION_OBSOLETA' => [],
    'DOCUMENTACION' => [],
    'OTROS' => []
];

foreach ($archivosEncontrados as $archivo) {
    if ($archivo == '.' || $archivo == '..') continue;
    
    // Categorizar archivos
    if (in_array($archivo, ['index.php', 'login.php', 'logout.php', 'menu_principal.php'])) {
        $archivosCategorizados['CORE_SISTEMA'][] = $archivo;
    }
    elseif ($archivo == 'modulos' || $archivo == 'ajax' || $archivo == 'config' || $archivo == 'assets') {
        $archivosCategorizados['MODULOS'][] = $archivo . '/';
    }
    elseif (preg_match('/^(test_|verificar_|debug_|diagnostico_)/', $archivo)) {
        $archivosCategorizados['PRUEBAS'][] = $archivo;
    }
    elseif (preg_match('/^(crear_|corregir_|limpiar_|migrar_|reparar_)/', $archivo)) {
        $archivosCategorizados['MIGRACION_OBSOLETA'][] = $archivo;
    }
    elseif (preg_match('/^(reporte_|excel_)/', $archivo)) {
        $archivosCategorizados['REPORTES'][] = $archivo;
    }
    elseif (preg_match('/\.(ps1|bat)$/', $archivo)) {
        $archivosCategorizados['BACKUP_SCRIPTS'][] = $archivo;
    }
    elseif (preg_match('/\.(md|html)$/', $archivo)) {
        $archivosCategorizados['DOCUMENTACION'][] = $archivo;
    }
    elseif (preg_match('/^(backup_|\.vscode)/', $archivo)) {
        $archivosCategorizados['CONFIGURACION'][] = $archivo;
    }
    else {
        $archivosCategorizados['OTROS'][] = $archivo;
    }
}

// Mostrar resultados
foreach ($archivosCategorizados as $categoria => $archivos) {
    if (empty($archivos)) continue;
    
    echo "<div class='modulo'>";
    echo "<h2>📁 $categoria (" . count($archivos) . " archivos)</h2>";
    
    $claseCSS = 'funcional';
    $accion = '✅ MANTENER';
    
    switch ($categoria) {
        case 'CORE_SISTEMA':
        case 'MODULOS':
        case 'REPORTES':
            $claseCSS = 'funcional';
            $accion = '✅ MANTENER';
            break;
        case 'CONFIGURACION':
            $claseCSS = 'config';
            $accion = '📋 REVISAR';
            break;
        case 'PRUEBAS':
        case 'MIGRACION_OBSOLETA':
            $claseCSS = 'obsoleto';
            $accion = '🗑️ ELIMINAR';
            break;
        case 'BACKUP_SCRIPTS':
        case 'DOCUMENTACION':
            $claseCSS = 'backup';
            $accion = '📦 BACKUP';
            break;
        default:
            $claseCSS = 'prueba';
            $accion = '🔍 ANALIZAR';
    }
    
    echo "<p><strong class='$claseCSS'>$accion</strong></p>";
    echo "<ul>";
    foreach ($archivos as $archivo) {
        echo "<li class='$claseCSS'>$archivo</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Análisis de módulos
echo "<hr><h2>📂 ANÁLISIS DE MÓDULOS</h2>";

$modulos = ['admin', 'clientes', 'compras', 'facturas', 'Inventario', 'pedidos', 'productos'];

foreach ($modulos as $modulo) {
    $rutaModulo = "modulos/$modulo";
    if (is_dir($rutaModulo)) {
        echo "<div class='modulo'>";
        echo "<h3>📁 $modulo</h3>";
        
        $archivosModulo = scandir($rutaModulo);
        $funcionalesModulo = [];
        $obsoletosModulo = [];
        
        foreach ($archivosModulo as $archivo) {
            if ($archivo == '.' || $archivo == '..') continue;
            
            if (preg_match('/(test_|debug_|temp_|old_)/', $archivo)) {
                $obsoletosModulo[] = $archivo;
            } else {
                $funcionalesModulo[] = $archivo;
            }
        }
        
        if (!empty($funcionalesModulo)) {
            echo "<p><strong class='funcional'>✅ FUNCIONALES (" . count($funcionalesModulo) . "):</strong></p>";
            echo "<ul>";
            foreach ($funcionalesModulo as $archivo) {
                echo "<li class='funcional'>$archivo</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($obsoletosModulo)) {
            echo "<p><strong class='obsoleto'>🗑️ OBSOLETOS (" . count($obsoletosModulo) . "):</strong></p>";
            echo "<ul>";
            foreach ($obsoletosModulo as $archivo) {
                echo "<li class='obsoleto'>$archivo</li>";
            }
            echo "</ul>";
        }
        
        echo "</div>";
    }
}

// Resumen y recomendaciones
echo "<hr><h2>📊 RESUMEN Y RECOMENDACIONES</h2>";

$totalArchivos = count($archivosEncontrados) - 2; // Quitar . y ..
$mantener = count($archivosCategorizados['CORE_SISTEMA']) + count($archivosCategorizados['MODULOS']) + count($archivosCategorizados['REPORTES']);
$eliminar = count($archivosCategorizados['PRUEBAS']) + count($archivosCategorizados['MIGRACION_OBSOLETA']);
$revisar = count($archivosCategorizados['OTROS']);

echo "<div class='modulo'>";
echo "<h3>📈 ESTADÍSTICAS</h3>";
echo "<ul>";
echo "<li><strong>Total de archivos:</strong> $totalArchivos</li>";
echo "<li><strong class='funcional'>Mantener:</strong> $mantener archivos</li>";
echo "<li><strong class='obsoleto'>Eliminar:</strong> $eliminar archivos</li>";
echo "<li><strong class='prueba'>Revisar:</strong> $revisar archivos</li>";
echo "</ul>";

echo "<h3>🎯 PLAN DE LIMPIEZA RECOMENDADO</h3>";
echo "<ol>";
echo "<li><strong>PASO 1:</strong> Crear backup completo</li>";
echo "<li><strong>PASO 2:</strong> Eliminar archivos de prueba y migración</li>";
echo "<li><strong>PASO 3:</strong> Mover documentación a carpeta docs/</li>";
echo "<li><strong>PASO 4:</strong> Mover scripts de backup a carpeta scripts/</li>";
echo "<li><strong>PASO 5:</strong> Organizar estructura final</li>";
echo "</ol>";
echo "</div>";

echo "<p><em>Análisis completado: " . date('Y-m-d H:i:s') . "</em></p>";
?>
