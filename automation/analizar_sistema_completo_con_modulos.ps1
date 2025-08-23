# ANALISIS COMPLETO DEL SISTEMA INCLUYENDO MODULOS
# Script para analizar TODO el sistema: root + modulos

Write-Host "ANALISIS COMPLETO DEL SISTEMA INCLUYENDO MODULOS" -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan

# Contar archivos por ubicacion
Write-Host ""
Write-Host "1. CONTEO GENERAL DEL SISTEMA:" -ForegroundColor Yellow
Write-Host "==============================" -ForegroundColor Yellow

$archivosRoot = (Get-ChildItem -File).Count
$archivosTotales = (Get-ChildItem -Recurse -File).Count
$carpetasTotales = (Get-ChildItem -Recurse -Directory).Count

Write-Host "Archivos en root: $archivosRoot" -ForegroundColor White
Write-Host "Archivos totales: $archivosTotales" -ForegroundColor White
Write-Host "Carpetas totales: $carpetasTotales" -ForegroundColor White

# Analizar por carpetas principales
Write-Host ""
Write-Host "2. ANALISIS POR CARPETAS PRINCIPALES:" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Yellow

$carpetasPrincipales = @("modulos", "config", "ajax", "assets")
foreach ($carpeta in $carpetasPrincipales) {
    if (Test-Path $carpeta) {
        $archivos = (Get-ChildItem -Path $carpeta -Recurse -File).Count
        $subcarpetas = (Get-ChildItem -Path $carpeta -Recurse -Directory).Count
        Write-Host "$carpeta/: $archivos archivos, $subcarpetas subcarpetas" -ForegroundColor Green
        
        # Mostrar subcarpetas de modulos
        if ($carpeta -eq "modulos") {
            Write-Host "   Modulos encontrados:" -ForegroundColor Cyan
            Get-ChildItem -Path $carpeta -Directory | ForEach-Object {
                $archivosModulo = (Get-ChildItem -Path $_.FullName -Recurse -File).Count
                Write-Host "   - $($_.Name): $archivosModulo archivos" -ForegroundColor White
            }
        }
    } else {
        Write-Host "$carpeta/: NO ENCONTRADA" -ForegroundColor Red
    }
}

# Analizar tipos de archivos
Write-Host ""
Write-Host "3. ANALISIS POR TIPOS DE ARCHIVOS:" -ForegroundColor Yellow
Write-Host "==================================" -ForegroundColor Yellow

$extensiones = @{}
Get-ChildItem -Recurse -File | ForEach-Object {
    $ext = $_.Extension.ToLower()
    if ($ext -eq "") { $ext = "sin_extension" }
    if ($extensiones.ContainsKey($ext)) {
        $extensiones[$ext]++
    } else {
        $extensiones[$ext] = 1
    }
}

$extensiones.GetEnumerator() | Sort-Object Value -Descending | ForEach-Object {
    Write-Host "$($_.Key): $($_.Value) archivos" -ForegroundColor White
}

# Buscar archivos PHP especificos
Write-Host ""
Write-Host "4. ARCHIVOS PHP POR FUNCIONALIDAD:" -ForegroundColor Yellow
Write-Host "==================================" -ForegroundColor Yellow

# Archivos de formularios
Write-Host ""
Write-Host "FORMULARIOS:" -ForegroundColor Green
Get-ChildItem -Recurse -Name "*form*.php" | ForEach-Object {
    Write-Host "   $_" -ForegroundColor White
}

# Archivos de gestion
Write-Host ""
Write-Host "GESTION:" -ForegroundColor Green
Get-ChildItem -Recurse -Name "gestionar_*.php" | ForEach-Object {
    Write-Host "   $_" -ForegroundColor White
}

# Archivos de reportes
Write-Host ""
Write-Host "REPORTES:" -ForegroundColor Green
Get-ChildItem -Recurse -Name "*reporte*.php" | ForEach-Object {
    Write-Host "   $_" -ForegroundColor White
}

# Archivos AJAX
Write-Host ""
Write-Host "AJAX:" -ForegroundColor Green
Get-ChildItem -Recurse -Name "ajax_*.php" | ForEach-Object {
    Write-Host "   $_" -ForegroundColor White
}

# Archivos de desarrollo/debug
Write-Host ""
Write-Host "5. ARCHIVOS DE DESARROLLO/DEBUG:" -ForegroundColor Yellow
Write-Host "================================" -ForegroundColor Yellow

$patronesDebug = @("*test*", "*debug*", "*verificar*", "*analizar*", "*limpiar*", "*crear_*", "*generar_*", "*instalar_*")
$archivosDebug = @()

foreach ($patron in $patronesDebug) {
    Get-ChildItem -Recurse -Name $patron | ForEach-Object {
        if ($_ -like "*.php" -or $_ -like "*.ps1") {
            $archivosDebug += $_
        }
    }
}

$archivosDebugUnicos = $archivosDebug | Sort-Object | Get-Unique
Write-Host "Archivos de desarrollo encontrados: $($archivosDebugUnicos.Count)" -ForegroundColor Red
$archivosDebugUnicos | ForEach-Object {
    Write-Host "   $_" -ForegroundColor Gray
}

# Archivos de backup
Write-Host ""
Write-Host "6. ARCHIVOS DE BACKUP:" -ForegroundColor Yellow
Write-Host "=====================" -ForegroundColor Yellow

$archivosBackup = Get-ChildItem -Recurse -Name "*.zip"
Write-Host "Archivos ZIP encontrados: $($archivosBackup.Count)" -ForegroundColor Red
$archivosBackup | ForEach-Object {
    $archivo = Get-Item $_
    $tamanoMB = [math]::Round($archivo.Length / 1MB, 2)
    Write-Host "   $_ ($tamanoMB MB)" -ForegroundColor Gray
}

# Calcular estadisticas finales
Write-Host ""
Write-Host "7. ESTADISTICAS FINALES:" -ForegroundColor Yellow
Write-Host "========================" -ForegroundColor Yellow

$archivosEsenciales = $archivosTotales - $archivosDebugUnicos.Count - $archivosBackup.Count
$porcentajeEsencial = [math]::Round(($archivosEsenciales / $archivosTotales) * 100, 1)

Write-Host ""
Write-Host "RESUMEN GENERAL:" -ForegroundColor Cyan
Write-Host "Total de archivos: $archivosTotales" -ForegroundColor White
Write-Host "Archivos esenciales: $archivosEsenciales" -ForegroundColor Green
Write-Host "Archivos de desarrollo: $($archivosDebugUnicos.Count)" -ForegroundColor Red
Write-Host "Archivos de backup: $($archivosBackup.Count)" -ForegroundColor Red
Write-Host "Porcentaje esencial: $porcentajeEsencial%" -ForegroundColor Yellow

Write-Host ""
Write-Host "RECOMENDACION PARA GITHUB:" -ForegroundColor Magenta
if ($porcentajeEsencial -gt 80) {
    Write-Host "Sistema bien organizado - listo para GitHub" -ForegroundColor Green
} elseif ($porcentajeEsencial -gt 60) {
    Write-Host "Necesita limpieza menor antes de GitHub" -ForegroundColor Yellow
} else {
    Write-Host "Requiere limpieza significativa antes de GitHub" -ForegroundColor Red
}

Write-Host ""
Write-Host "SIGUIENTE PASO:" -ForegroundColor Cyan
Write-Host "¿Quieres que cree un ZIP incluyendo solo los modulos esenciales?" -ForegroundColor White
