# ===================================
# CONFIGURAR BACKUP AUTOMÁTICO EN WINDOWS
# Tarea programada para ejecutar backups automáticos
# ===================================

Write-Host "🔧 CONFIGURADOR DE BACKUP AUTOMÁTICO" -ForegroundColor Cyan
Write-Host "====================================" -ForegroundColor Cyan
Write-Host ""

$ProyectoPath = "C:\xampp\htdocs\sistemadgestion5"
$ScriptBackup = "$ProyectoPath\.vscode\backup-automatico.ps1"

# Verificar que el script existe
if (!(Test-Path $ScriptBackup)) {
    Write-Host "❌ No se encuentra el script de backup en: $ScriptBackup" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Script de backup encontrado" -ForegroundColor Green
Write-Host ""

# Configurar tareas programadas
Write-Host "🕐 Configurando tareas programadas..." -ForegroundColor Yellow

try {
    # Tarea cada hora (durante horario laboral)
    $TareaHourly = @{
        TaskName = "SistemaGestion_BackupHourly"
        Description = "Backup automático cada hora del Sistema de Gestión"
        Action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -File `"$ScriptBackup`" -TipoBackup hourly"
        Trigger = New-ScheduledTaskTrigger -Daily -At "08:00" -RepetitionInterval (New-TimeSpan -Hours 1) -RepetitionDuration (New-TimeSpan -Hours 12)
        Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
        Principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive
    }
    
    # Verificar si la tarea ya existe
    $TareaExistente = Get-ScheduledTask -TaskName "SistemaGestion_BackupHourly" -ErrorAction SilentlyContinue
    if ($TareaExistente) {
        Unregister-ScheduledTask -TaskName "SistemaGestion_BackupHourly" -Confirm:$false
        Write-Host "🗑️  Tarea anterior eliminada" -ForegroundColor Yellow
    }
    
    Register-ScheduledTask @TareaHourly -Force | Out-Null
    Write-Host "✅ Backup cada hora configurado (8:00-20:00)" -ForegroundColor Green
    
    # Tarea diaria
    $TareaDaily = @{
        TaskName = "SistemaGestion_BackupDaily"
        Description = "Backup diario del Sistema de Gestión"
        Action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -File `"$ScriptBackup`" -TipoBackup daily"
        Trigger = New-ScheduledTaskTrigger -Daily -At "22:00"
        Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
        Principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive
    }
    
    $TareaExistente = Get-ScheduledTask -TaskName "SistemaGestion_BackupDaily" -ErrorAction SilentlyContinue
    if ($TareaExistente) {
        Unregister-ScheduledTask -TaskName "SistemaGestion_BackupDaily" -Confirm:$false
    }
    
    Register-ScheduledTask @TareaDaily -Force | Out-Null
    Write-Host "✅ Backup diario configurado (22:00)" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "📅 TAREAS PROGRAMADAS CONFIGURADAS:" -ForegroundColor Cyan
    Write-Host "===================================="
    Write-Host "⏰ Backup cada hora: 8:00 - 20:00 (horario laboral)"
    Write-Host "📅 Backup diario: 22:00"
    Write-Host ""
    
    # Ejecutar un backup inmediato para probar
    Write-Host "🧪 Ejecutando backup de prueba..." -ForegroundColor Yellow
    & powershell.exe -ExecutionPolicy Bypass -File "$ScriptBackup" -TipoBackup "auto"
    
    Write-Host ""
    Write-Host "✅ ¡Configuración completada!" -ForegroundColor Green
    Write-Host ""
    Write-Host "📋 INSTRUCCIONES:" -ForegroundColor Cyan
    Write-Host "================="
    Write-Host "• Los backups se guardan en: $ProyectoPath\.vscode\backups"
    Write-Host "• Puedes ejecutar backup manual desde VS Code (Ctrl+Shift+P > Tasks: Run Task)"
    Write-Host "• Para ver las tareas: Administrador de tareas > Biblioteca del Programador de tareas"
    Write-Host "• Para desactivar: ejecuta este script con parámetro -Desinstalar"
    
} catch {
    Write-Host "❌ Error al configurar tareas programadas: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "💡 ALTERNATIVA MANUAL:" -ForegroundColor Yellow
    Write-Host "======================="
    Write-Host "Ejecuta manualmente desde VS Code:"
    Write-Host "1. Ctrl+Shift+P"
    Write-Host "2. 'Tasks: Run Task'"
    Write-Host "3. Selecciona '🔄 Backup Manual'"
}
