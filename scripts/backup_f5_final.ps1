# BACKUP INTELIGENTE F5
Write-Host "🧠 BACKUP INTELIGENTE F5" -ForegroundColor Cyan

$HoraActual = Get-Date
$EsHorarioLaboral = ($HoraActual.Hour -ge 8 -and $HoraActual.Hour -le 18)

# Detectar cambios recientes
$CambiosRecientes = $false
try {
    $UltimaModificacion = (Get-ChildItem -Path "modulos" -Recurse -File | Sort-Object LastWriteTime -Descending | Select-Object -First 1).LastWriteTime
    $TiempoSinCambios = (Get-Date) - $UltimaModificacion
    $CambiosRecientes = $TiempoSinCambios.TotalMinutes -lt 30
} catch {
    Write-Host "Usando backup por defecto" -ForegroundColor Yellow
}

# Determinar tipo
$TipoBackup = "auto"
if ($CambiosRecientes -and $EsHorarioLaboral) {
    $TipoBackup = "emergency"
    $Razon = "Cambios + horario laboral"
} elseif ($CambiosRecientes) {
    $TipoBackup = "manual"
    $Razon = "Cambios recientes"
} else {
    $TipoBackup = "routine"
    $Razon = "Backup rutina"
}

Write-Host "Condiciones:"
Write-Host "  Hora: $($HoraActual.ToString('HH:mm:ss'))"
Write-Host "  Horario laboral: $EsHorarioLaboral"
Write-Host "  Cambios recientes: $CambiosRecientes"
Write-Host "  Tipo: $TipoBackup"
Write-Host "  Razon: $Razon"

Write-Host "Ejecutando backup $TipoBackup..." -ForegroundColor Yellow

# Ejecutar el backup
try {
    if (Test-Path ".\.vscode\backup-automatico.ps1") {
        & PowerShell -ExecutionPolicy Bypass -File ".\.vscode\backup-automatico.ps1" -TipoBackup $TipoBackup
    } else {
        Write-Host "Script backup-automatico.ps1 no encontrado" -ForegroundColor Red
    }
} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "F5 Smart completado" -ForegroundColor Green
