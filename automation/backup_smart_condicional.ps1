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
$UltimaModificacion = (Get-ChildItem -Path "modulos" -Recurse -File | Sort-Object LastWriteTime -Descending | Select-Object -First 1).LastWriteTime
$TiempoSinCambios = (Get-Date) - $UltimaModificacion
$CambiosRecientes = $TiempoSinCambios.TotalMinutes -lt 30

# Detectar procesos de debug activos
$DebugProcesos = @(
    Get-Process -Name "php*" -ErrorAction SilentlyContinue | Where-Object { $_.ProcessName -like "*debug*" }
    Get-Process -Name "xdebug*" -ErrorAction SilentlyContinue
    Get-NetTCPConnection -LocalPort 9003 -ErrorAction SilentlyContinue 2>$null
)

if ($DebugProcesos.Count -gt 0) {
    $DebugActivo = $true
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
Write-Host "  📝 Cambios recientes: $(if($CambiosRecientes){'Sí ('+[math]::Round($TiempoSinCambios.TotalMinutes,1)+' min)'}else{'No'})"
Write-Host "  🎯 Tipo seleccionado: $TipoBackup"
Write-Host "  💡 Razón: $Razon"
Write-Host ""

# Crear backup con configuración específica según tipo
$ProyectoPath = "C:\xampp\htdocs\sistemadgestion5"
$BackupBasePath = "$ProyectoPath\.vscode\backups"
$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"

# Configuración según tipo
$ConfigBackup = switch ($TipoBackup) {
    "debug" {
        @{
            Carpeta = "debug"
            Incluir = @("modulos", "config", "*.php", "*.js", "*.sql", ".vscode\settings.json", ".vscode\launch.json")
            Prioridad = "Alta"
            Limpieza = 3  # días
        }
    }
    "emergency" {
        @{
            Carpeta = "emergency"
            Incluir = @("modulos", "config", "*.php", "*.md", "*.sql", "*.txt")
            Prioridad = "Crítica"
            Limpieza = 7  # días
        }
    }
    "manual" {
        @{
            Carpeta = "manual"
            Incluir = @("modulos", "config", "*.php", "*.md", "*.sql", "*.txt", "assets\img\productos")
            Prioridad = "Media"
            Limpieza = 999  # no se limpia
        }
    }
    "routine" {
        @{
            Carpeta = "routine"
            Incluir = @("modulos", "config", "*.php", "*.sql")
            Prioridad = "Baja"
            Limpieza = 14  # días
        }
    }
}

Write-Host "🔄 Ejecutando backup $TipoBackup (Prioridad: $($ConfigBackup.Prioridad))..." -ForegroundColor Yellow

try {
    $DestinoBackup = Join-Path $BackupBasePath $ConfigBackup.Carpeta
    if (!(Test-Path $DestinoBackup)) {
        New-Item -ItemType Directory -Path $DestinoBackup -Force | Out-Null
    }
    
    $NombreBackup = "backup_smart_${TipoBackup}_${Timestamp}.zip"
    $RutaBackup = Join-Path $DestinoBackup $NombreBackup
    
    # Obtener archivos según configuración
    $TodosLosArchivos = @()
    foreach ($patron in $ConfigBackup.Incluir) {
        $archivos = Get-ChildItem -Path "$ProyectoPath\$patron" -Recurse -File -ErrorAction SilentlyContinue
        $TodosLosArchivos += $archivos
    }
    
    # Filtrar archivos no deseados
    $ArchivosLimpios = $TodosLosArchivos | Where-Object {
        $_.FullName -notlike "*\.vscode\backups\*" -and
        $_.FullName -notlike "*\assets\uploads\*" -and
        $_.Extension -ne ".log" -and
        $_.Name -notlike "temp*"
    }
    
    if ($ArchivosLimpios.Count -gt 0) {
        # Crear el ZIP
        Compress-Archive -Path $ArchivosLimpios.FullName -DestinationPath $RutaBackup -Force
        
        $TamanoMB = [math]::Round((Get-Item $RutaBackup).Length / 1MB, 2)
        Write-Host "✅ Backup $TipoBackup creado: $NombreBackup ($TamanoMB MB)" -ForegroundColor Green
        
        # Limpiar backups antiguos
        if ($ConfigBackup.Limpieza -lt 999) {
            $FechaLimite = (Get-Date).AddDays(-$ConfigBackup.Limpieza)
            $ArchivosAntiguos = Get-ChildItem $DestinoBackup -Filter "*.zip" | Where-Object { $_.CreationTime -lt $FechaLimite }
            
            foreach ($archivo in $ArchivosAntiguos) {
                Remove-Item $archivo.FullName -Force -ErrorAction SilentlyContinue
                Write-Host "🗑️  Limpieza: $($archivo.Name)" -ForegroundColor DarkYellow
            }
        }
        
        $BackupExitoso = $true
    } else {
        Write-Host "❌ No se encontraron archivos para el backup" -ForegroundColor Red
        $BackupExitoso = $false
    }
    
} catch {
    Write-Host "❌ Error: $($_.Exception.Message)" -ForegroundColor Red
    $BackupExitoso = $false
}

# Mostrar resumen
Write-Host ""
Write-Host "📋 RESUMEN BACKUP INTELIGENTE" -ForegroundColor Cyan
Write-Host "==============================="

if ($BackupExitoso) {
    Write-Host "✅ Backup $TipoBackup completado exitosamente" -ForegroundColor Green
    Write-Host "📁 Ubicación: $RutaBackup"
    Write-Host "🎯 Condición detectada: $Razon"
} else {
    Write-Host "❌ El backup falló" -ForegroundColor Red
}

# Mostrar próximas condiciones recomendadas
Write-Host ""
Write-Host "💡 RECOMENDACIONES F5:" -ForegroundColor Cyan
if ($DebugActivo) {
    Write-Host "  🔧 Debug activo: F5 = backup de debug con configuración completa"
} elseif ($EsHorarioLaboral) {
    Write-Host "  🏢 Horario laboral: F5 = backup emergency para cambios críticos"
} else {
    Write-Host "  🌙 Fuera de horario: F5 = backup manual con archivos completos"
}

Write-Host ""
Write-Host "🎮 TECLAS DISPONIBLES:" -ForegroundColor Cyan
Write-Host "  F5: Backup inteligente (analiza condiciones)"
Write-Host "  Ctrl+Alt+F5: Backup forzado (durante debug)"
Write-Host "  F6: Backup emergency directo"
Write-Host "  Ctrl+F6: Backup manual tradicional"
Write-Host ""
Write-Host "✨ F5 Smart completado en $((((Get-Date) - $HoraActual).TotalSeconds).ToString('0.0'))s" -ForegroundColor Green
