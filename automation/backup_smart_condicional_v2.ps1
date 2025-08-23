# ===================================
# BACKUP INTELIGENTE CON CONDICIONES F5
# Sistema de Gestión - Backup Smart
# ===================================

Write-Host ""
Write-Host "🧠 BACKUP INTELIGENTE F5" -ForegroundColor Cyan
Write-Host "=========================" -ForegroundColor Cyan
Write-Host "Analizando condiciones..." -ForegroundColor Yellow

# Detectar estado del sistema
$DebugActivo = $false
$HoraActual = Get-Date
$EsHorarioLaboral = ($HoraActual.Hour -ge 8 -and $HoraActual.Hour -le 18)

# Buscar última modificación
$UltimaModificacion = Get-Date
try {
    $UltimaModificacion = (Get-ChildItem -Path "modulos" -Recurse -File | Sort-Object LastWriteTime -Descending | Select-Object -First 1).LastWriteTime
} catch {
    Write-Host "⚠️ No se pudo acceder a modulos" -ForegroundColor Yellow
}

$TiempoSinCambios = (Get-Date) - $UltimaModificacion
$CambiosRecientes = $TiempoSinCambios.TotalMinutes -lt 30

# Detectar procesos de debug activos
try {
    $DebugProcesos = Get-Process -Name "*php*" -ErrorAction SilentlyContinue | Where-Object { $_.ProcessName -like "*debug*" }
    if ($DebugProcesos.Count -gt 0) {
        $DebugActivo = $true
    }
} catch {
    # No importa si falla
}

# Determinar tipo de backup según condiciones
$TipoBackup = "auto"
$Razon = ""

if ($DebugActivo) {
    $TipoBackup = "debug"
    $Razon = "Debug activo detectado"
} elseif ($CambiosRecientes -and $EsHorarioLaboral) {
    $TipoBackup = "emergency"
    $Razon = "Cambios recientes en horario laboral"
} elseif ($CambiosRecientes) {
    $TipoBackup = "manual"
    $Razon = "Cambios recientes fuera de horario"
} else {
    $TipoBackup = "routine"
    $Razon = "Backup de rutina"
}

Write-Host ""
Write-Host "📊 ANÁLISIS DE CONDICIONES:" -ForegroundColor Cyan
Write-Host "  🕐 Hora actual: $($HoraActual.ToString('HH:mm:ss'))"
Write-Host "  🏢 Horario laboral: $(if($EsHorarioLaboral){'Sí'}else{'No'})"
Write-Host "  🔧 Debug activo: $(if($DebugActivo){'Sí'}else{'No'})"
Write-Host "  📝 Cambios recientes: $(if($CambiosRecientes){'Sí'}else{'No'})"
Write-Host "  🎯 Tipo seleccionado: $TipoBackup"
Write-Host "  💡 Razón: $Razon"
Write-Host ""

# Ejecutar backup usando el script existente
Write-Host "🔄 Ejecutando backup $TipoBackup..." -ForegroundColor Yellow

try {
    # Usar el script de backup automático existente
    $ScriptBackup = ".\backup-automatico.ps1"
    if (Test-Path $ScriptBackup) {
        & PowerShell -ExecutionPolicy Bypass -File $ScriptBackup -TipoBackup $TipoBackup
        Write-Host "✅ Backup inteligente completado" -ForegroundColor Green
    } else {
        Write-Host "❌ No se encontró el script de backup automático" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ Error al ejecutar backup: $($_.Exception.Message)" -ForegroundColor Red
}

# Mostrar resumen
Write-Host ""
Write-Host "📋 RESUMEN BACKUP INTELIGENTE" -ForegroundColor Cyan
Write-Host "==============================="
Write-Host "🎯 Condición detectada: $Razon"
Write-Host "📦 Tipo ejecutado: $TipoBackup"

# Mostrar recomendaciones
Write-Host ""
Write-Host "💡 RECOMENDACIONES F5:" -ForegroundColor Cyan
if ($DebugActivo) {
    Write-Host "  🔧 Debug activo: F5 = backup de debug"
} elseif ($EsHorarioLaboral) {
    Write-Host "  🏢 Horario laboral: F5 = backup emergency"
} else {
    Write-Host "  🌙 Fuera de horario: F5 = backup manual"
}

Write-Host ""
Write-Host "🎮 TECLAS DISPONIBLES:" -ForegroundColor Cyan
Write-Host "  F5: Backup inteligente (analiza condiciones)"
Write-Host "  Ctrl+Alt+F5: Backup forzado (durante debug)"
Write-Host "  F6: Backup emergency directo"
Write-Host "  Ctrl+F6: Backup manual tradicional"
Write-Host ""
Write-Host "✨ F5 Smart completado" -ForegroundColor Green
