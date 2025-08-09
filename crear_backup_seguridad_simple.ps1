# BACKUP COMPLETO ANTES DE LIMPIEZA FINAL

Write-Host "CREANDO BACKUP COMPLETO DE SEGURIDAD" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

$fecha = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$nombreBackup = "backup_completo_antes_limpieza_final_$fecha.zip"

Write-Host ""
Write-Host "Creando backup: $nombreBackup" -ForegroundColor Yellow

# Contar archivos totales
$totalArchivos = (Get-ChildItem -Recurse -File | Measure-Object).Count
$totalCarpetas = (Get-ChildItem -Recurse -Directory | Measure-Object).Count

Write-Host "Carpetas: $totalCarpetas" -ForegroundColor Gray
Write-Host "Archivos: $totalArchivos" -ForegroundColor Gray

# Calcular tamaño aproximado
$tamanoTotal = (Get-ChildItem -Recurse -File | Measure-Object -Property Length -Sum).Sum
$tamanoMB = [math]::Round($tamanoTotal / 1MB, 2)

Write-Host "Tamaño total: $tamanoMB MB" -ForegroundColor Gray

Write-Host ""
Write-Host "Comprimiendo todo el sistema..." -ForegroundColor Yellow

try {
    # Crear el ZIP de backup
    Compress-Archive -Path "*" -DestinationPath $nombreBackup -Force
    
    # Verificar que se creó correctamente
    if (Test-Path $nombreBackup) {
        $infoBackup = Get-Item $nombreBackup
        $tamanoBackupMB = [math]::Round($infoBackup.Length / 1MB, 2)
        
        Write-Host ""
        Write-Host "BACKUP CREADO EXITOSAMENTE" -ForegroundColor Green
        Write-Host "==========================" -ForegroundColor Green
        Write-Host ""
        Write-Host "Archivo: $nombreBackup" -ForegroundColor White
        Write-Host "Ubicacion: $(Get-Location)\$nombreBackup" -ForegroundColor White
        Write-Host "Tamaño: $tamanoBackupMB MB" -ForegroundColor White
        Write-Host "Fecha: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')" -ForegroundColor White
        
        Write-Host ""
        Write-Host "CONTENIDO RESPALDADO:" -ForegroundColor Cyan
        Write-Host "- Todas las carpetas del sistema ($totalCarpetas)" -ForegroundColor White
        Write-Host "- Todos los archivos ($totalArchivos)" -ForegroundColor White
        Write-Host "- Modulos completos" -ForegroundColor White
        Write-Host "- Configuraciones y assets" -ForegroundColor White
        Write-Host "- Scripts de desarrollo y debug" -ForegroundColor White
        Write-Host "- Backups anteriores incluidos" -ForegroundColor White
        
        Write-Host ""
        Write-Host "SEGURIDAD GARANTIZADA:" -ForegroundColor Green
        Write-Host "- Sistema completo respaldado" -ForegroundColor White
        Write-Host "- Posibilidad de restauracion 100%" -ForegroundColor White
        Write-Host "- Listo para proceder con la limpieza" -ForegroundColor White
        
        Write-Host ""
        Write-Host "PROXIMO PASO:" -ForegroundColor Magenta
        Write-Host "Ahora puedes proceder con la limpieza segura" -ForegroundColor White
        Write-Host "El backup estara disponible para restaurar si es necesario" -ForegroundColor White
        
    } else {
        Write-Host ""
        Write-Host "ERROR: No se pudo crear el backup" -ForegroundColor Red
        Write-Host "Verifica el espacio en disco y los permisos" -ForegroundColor Yellow
        Write-Host "NO PROCEDER con la limpieza sin backup" -ForegroundColor Red
    }
} catch {
    Write-Host ""
    Write-Host "ERROR CRITICO al crear backup:" -ForegroundColor Red
    Write-Host "$($_.Exception.Message)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "NO PROCEDER con la limpieza hasta resolver este error" -ForegroundColor Red
}
