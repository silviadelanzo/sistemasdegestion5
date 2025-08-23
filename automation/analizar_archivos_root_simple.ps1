# ANALISIS DE ARCHIVOS EN LA CARPETA RAIZ
Write-Host "ANALISIS DE ARCHIVOS EN LA CARPETA RAIZ" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan

# Obtener todos los archivos en el root
$archivos = Get-ChildItem -File

# Categorizar archivos
$esenciales = @()
$reportes = @()
$configuracion = @()
$obsoletos = @()
$desarrollo = @()
$backups = @()

foreach ($archivo in $archivos) {
    $nombre = $archivo.Name
    
    # ARCHIVOS ESENCIALES DEL SISTEMA
    if ($nombre -match "^(index|login|logout|menu_principal|obtener_ultimo_codigo)\.php$") {
        $esenciales += $nombre
    }
    # FORMULARIOS PRINCIPALES
    elseif ($nombre -match "^(categoria_form|lugar_form|usuario_form|proveedor_form)\.php$") {
        $esenciales += $nombre
    }
    # GESTION PRINCIPAL
    elseif ($nombre -match "^(usuarios|proveedores|reportes)\.php$") {
        $esenciales += $nombre
    }
    # GESTORES NECESARIOS
    elseif ($nombre -match "^gestionar_(categoria|configuracion|lugar|usuario)\.php$") {
        $esenciales += $nombre
    }
    # README
    elseif ($nombre -eq "README.md") {
        $esenciales += $nombre
    }
    
    # REPORTES Y EXCEL
    elseif ($nombre -match "^(excel_nativo|reporte_).*\.php$") {
        $reportes += $nombre
    }
    
    # CONFIGURACION Y AJAX
    elseif ($nombre -match "^(ajax_|configuracion_sistema).*\.php$") {
        $configuracion += $nombre
    }
    
    # ARCHIVOS DE DESARROLLO/DEBUG/OBSOLETOS
    elseif ($nombre -match "^(analizar_|crear_admin|control_center|instalar_|ejecutar_|estado_|generar_|limpiar_|limpieza_|preparar_|reemplazar_|simple_pdf|ai_parser|product_matcher).*\.(php|ps1)$") {
        $desarrollo += $nombre
    }
    
    # BACKUPS Y ARCHIVOS TEMPORALES
    elseif ($nombre -match "\.(zip|sql)$" -and $nombre -notmatch "^setup_") {
        $backups += $nombre
    }
    
    # SCRIPTS DE DESARROLLO
    elseif ($nombre -match "\.(ps1|txt)$") {
        $desarrollo += $nombre
    }
    
    # ARCHIVOS ESPECIFICOS QUE PUEDEN SER OBSOLETOS
    elseif ($nombre -match "^(excel_php81|excel_planmaker|excel_real_sinvendor|excel_xlsx_nativo|gestionar_papelera|gestionar_proveedor|gestionar_proveedor_ajax|login_remitos|login_sin_debug|logs_sistema|proveedores_new|recepcion_mercaderia|registrar_pago)\.php$") {
        $obsoletos += $nombre
    }
    
    # ARCHIVOS SQL ESPECIFICOS
    elseif ($nombre -match "^(setup_compras_mejorado|sistema_impuestos_monedas_completo|sql_unificacion_geografica)\.sql$") {
        $configuracion += $nombre
    }
    
    else {
        $obsoletos += $nombre
    }
}

# MOSTRAR RESULTADOS
Write-Host ""
Write-Host "ARCHIVOS ESENCIALES PARA EL SISTEMA ($($esenciales.Count)):" -ForegroundColor Green
$esenciales | Sort-Object | ForEach-Object { Write-Host "   $($_)" -ForegroundColor White }

Write-Host ""
Write-Host "ARCHIVOS DE REPORTES Y EXCEL ($($reportes.Count)):" -ForegroundColor Yellow
$reportes | Sort-Object | ForEach-Object { Write-Host "   $($_)" -ForegroundColor White }

Write-Host ""
Write-Host "ARCHIVOS DE CONFIGURACION Y AJAX ($($configuracion.Count)):" -ForegroundColor Cyan
$configuracion | Sort-Object | ForEach-Object { Write-Host "   $($_)" -ForegroundColor White }

Write-Host ""
Write-Host "ARCHIVOS POSIBLEMENTE OBSOLETOS ($($obsoletos.Count)):" -ForegroundColor Magenta
$obsoletos | Sort-Object | ForEach-Object { Write-Host "   $($_)" -ForegroundColor Gray }

Write-Host ""
Write-Host "ARCHIVOS DE DESARROLLO/DEBUG ($($desarrollo.Count)):" -ForegroundColor Red
$desarrollo | Sort-Object | ForEach-Object { Write-Host "   $($_)" -ForegroundColor Gray }

Write-Host ""
Write-Host "BACKUPS Y ARCHIVOS TEMPORALES ($($backups.Count)):" -ForegroundColor DarkYellow
$backups | Sort-Object | ForEach-Object { Write-Host "   $($_)" -ForegroundColor Gray }

# CALCULAR ESTADISTICAS
$totalArchivos = $archivos.Count
$necesarios = $esenciales.Count + $reportes.Count + $configuracion.Count
$innecesarios = $obsoletos.Count + $desarrollo.Count + $backups.Count

Write-Host ""
Write-Host "RESUMEN DE ESTADISTICAS:" -ForegroundColor White
Write-Host "========================" -ForegroundColor White
Write-Host ""
Write-Host "Total de archivos en root: $totalArchivos" -ForegroundColor White
Write-Host "Necesarios para GitHub: $necesarios" -ForegroundColor Green
Write-Host "Innecesarios/Obsoletos: $innecesarios" -ForegroundColor Red
Write-Host "Porcentaje util: $([math]::Round(($necesarios/$totalArchivos)*100,1))%" -ForegroundColor Yellow

Write-Host ""
Write-Host "RECOMENDACIONES:" -ForegroundColor Magenta
Write-Host "===============" -ForegroundColor Magenta
Write-Host ""
Write-Host "MANTENER PARA GITHUB:" -ForegroundColor Green
Write-Host "- Archivos esenciales del sistema" -ForegroundColor White
Write-Host "- Sistema de reportes y Excel" -ForegroundColor White
Write-Host "- Configuracion y AJAX necesarios" -ForegroundColor White
Write-Host "- README.md principal" -ForegroundColor White

Write-Host ""
Write-Host "EXCLUIR DE GITHUB:" -ForegroundColor Red
Write-Host "- Todos los archivos de desarrollo/debug" -ForegroundColor White
Write-Host "- Backups y archivos .zip" -ForegroundColor White
Write-Host "- Scripts PowerShell de desarrollo" -ForegroundColor White
Write-Host "- Archivos de prueba y analisis" -ForegroundColor White
Write-Host "- Multiples versiones de Excel (mantener solo excel_nativo.php)" -ForegroundColor White

Write-Host ""
Write-Host "ARCHIVOS QUE REQUIEREN REVISION:" -ForegroundColor Yellow
Write-Host "- gestionar_papelera.php - Se usa la papelera?" -ForegroundColor White
Write-Host "- proveedores_new.php - Es una version alternativa?" -ForegroundColor White
Write-Host "- login_remitos.php - Sistema especifico de remitos?" -ForegroundColor White
Write-Host "- recepcion_mercaderia.php - Parte del modulo de compras?" -ForegroundColor White

Write-Host ""
Write-Host "PROPUESTA FINAL:" -ForegroundColor Cyan
Write-Host "Reducir de $totalArchivos archivos a aproximadamente $necesarios archivos" -ForegroundColor White
Write-Host "Esto mejorara significativamente la organizacion del proyecto" -ForegroundColor White
