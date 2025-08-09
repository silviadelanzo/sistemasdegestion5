# ========================================
# SCRIPT PARA DETENER DEBUG Y LIMPIAR PROCESOS
# Sistema de Gestión - Debug Killer
# ========================================

Write-Host "🛑 DETENIENDO PROCESOS DEBUG Y LIMPIANDO SESIONES" -ForegroundColor Red
Write-Host "================================================="

# 1. Verificar procesos PHP activos
Write-Host ""
Write-Host "📋 Procesos PHP activos:" -ForegroundColor Cyan
$procesosPHP = Get-Process | Where-Object { $_.ProcessName -like "*php*" }

if ($procesosPHP) {
    foreach ($proceso in $procesosPHP) {
        Write-Host "  🔍 PHP PID: $($proceso.Id) | CPU: $($proceso.CPU) | Memoria: $([math]::Round($proceso.WorkingSet/1MB,2)) MB"
        
        # Verificar si es un proceso de debug (alto uso de CPU o memoria)
        if ($proceso.CPU -gt 5 -or $proceso.WorkingSet -gt 200MB) {
            Write-Host "    ⚠️  Proceso sospechoso de debug detectado" -ForegroundColor Yellow
            
            try {
                Write-Host "    🛑 Deteniendo proceso..." -ForegroundColor Red
                Stop-Process -Id $proceso.Id -Force -ErrorAction Stop
                Write-Host "    ✅ Proceso detenido exitosamente" -ForegroundColor Green
            } catch {
                Write-Host "    ❌ Error al detener proceso: $($_.Exception.Message)" -ForegroundColor Red
            }
        } else {
            Write-Host "    ✅ Proceso normal" -ForegroundColor Green
        }
    }
} else {
    Write-Host "  ✅ No hay procesos PHP activos" -ForegroundColor Green
}

# 2. Verificar conexiones Xdebug (puerto 9003)
Write-Host ""
Write-Host "🔍 Verificando conexiones Xdebug (puerto 9003):" -ForegroundColor Cyan
try {
    $conexionesXdebug = Get-NetTCPConnection -LocalPort 9003 -ErrorAction SilentlyContinue
    if ($conexionesXdebug) {
        foreach ($conn in $conexionesXdebug) {
            Write-Host "  🔗 Conexión encontrada: $($conn.LocalAddress):$($conn.LocalPort) -> $($conn.RemoteAddress):$($conn.RemotePort) | Estado: $($conn.State)"
        }
        Write-Host "  🛑 Hay conexiones Xdebug activas" -ForegroundColor Yellow
    } else {
        Write-Host "  ✅ No hay conexiones Xdebug activas" -ForegroundColor Green
    }
} catch {
    Write-Host "  ⚠️  No se puede verificar conexiones Xdebug" -ForegroundColor Yellow
}

# 3. Verificar puertos de desarrollo web
Write-Host ""
Write-Host "🌐 Verificando puertos web:" -ForegroundColor Cyan
$puertos = @(80, 443, 3000, 8000, 8080, 9000, 9001, 9003)

foreach ($puerto in $puertos) {
    try {
        $conexiones = Get-NetTCPConnection -LocalPort $puerto -ErrorAction SilentlyContinue | Where-Object { $_.State -eq "Listen" }
        if ($conexiones) {
            foreach ($conn in $conexiones) {
                $proceso = Get-Process -Id $conn.OwningProcess -ErrorAction SilentlyContinue
                $nombreProceso = if ($proceso) { $proceso.ProcessName } else { "Desconocido" }
                Write-Host "  📡 Puerto $puerto activo | Proceso: $nombreProceso (PID: $($conn.OwningProcess))"
            }
        }
    } catch {
        # Ignorar errores de puertos no accesibles
    }
}

# 4. Limpiar archivos temporales de sesiones PHP
Write-Host ""
Write-Host "🗑️  Limpiando sesiones PHP:" -ForegroundColor Cyan
try {
    $sessionPath = "C:\xampp\tmp"
    if (Test-Path $sessionPath) {
        $sessionFiles = Get-ChildItem "$sessionPath\sess_*" -ErrorAction SilentlyContinue
        if ($sessionFiles) {
            Write-Host "  🔍 Encontrados $($sessionFiles.Count) archivos de sesión"
            foreach ($file in $sessionFiles) {
                try {
                    Remove-Item $file.FullName -Force
                    Write-Host "    ✅ Eliminado: $($file.Name)"
                } catch {
                    Write-Host "    ❌ Error eliminando: $($file.Name)"
                }
            }
        } else {
            Write-Host "  ✅ No hay archivos de sesión para limpiar"
        }
    } else {
        Write-Host "  ⚠️  Carpeta de sesiones no encontrada: $sessionPath"
    }
} catch {
    Write-Host "  ❌ Error limpiando sesiones: $($_.Exception.Message)" -ForegroundColor Red
}

# 5. Verificar configuración VS Code
Write-Host ""
Write-Host "⚙️  Verificando configuración VS Code:" -ForegroundColor Cyan
$vscodePath = ".\.vscode"
if (Test-Path $vscodePath) {
    Write-Host "  📁 Carpeta .vscode encontrada"
    
    # Verificar launch.json (configuración debug)
    $launchPath = "$vscodePath\launch.json"
    if (Test-Path $launchPath) {
        Write-Host "  🔧 launch.json encontrado - posible configuración debug activa"
        try {
            $launchContent = Get-Content $launchPath -Raw
            if ($launchContent -like "*xdebug*" -or $launchContent -like "*9003*") {
                Write-Host "    ⚠️  Configuración Xdebug detectada en launch.json" -ForegroundColor Yellow
            }
        } catch {
            Write-Host "    ❌ Error leyendo launch.json"
        }
    } else {
        Write-Host "  ✅ No hay launch.json (sin configuración debug)"
    }
    
    # Verificar settings.json
    $settingsPath = "$vscodePath\settings.json"
    if (Test-Path $settingsPath) {
        Write-Host "  ⚙️  settings.json encontrado"
    }
} else {
    Write-Host "  ⚠️  No hay carpeta .vscode en este proyecto"
}

# 6. Generar reporte final
Write-Host ""
Write-Host "📊 REPORTE FINAL:" -ForegroundColor Cyan
Write-Host "================="

# Verificar estado actual
$procesosActuales = Get-Process | Where-Object { $_.ProcessName -like "*php*" }
$conexionesActuales = try { Get-NetTCPConnection -LocalPort 9003 -ErrorAction SilentlyContinue } catch { $null }

Write-Host "🔍 Estado actual:"
Write-Host "  📋 Procesos PHP: $($procesosActuales.Count)"
Write-Host "  🔗 Conexiones Xdebug: $(if($conexionesActuales){$conexionesActuales.Count}else{0})"

# Recomendaciones
Write-Host ""
Write-Host "💡 RECOMENDACIONES:" -ForegroundColor Green
Write-Host "==================="
Write-Host "1. 🚀 Reiniciar Apache en XAMPP"
Write-Host "2. 🔄 Cerrar y reabrir VS Code"
Write-Host "3. 🌐 Usar navegador externo para login crítico"
Write-Host "4. ⚙️  Deshabilitar Xdebug en php.ini si no lo necesitas"
Write-Host "5. 🗑️  Limpiar cache del navegador"

Write-Host ""
Write-Host "✅ Limpieza de debug completada" -ForegroundColor Green
Write-Host ""
Write-Host "🎮 COMANDOS ÚTILES:"
Write-Host "  - Reiniciar Apache: C:\xampp\apache_restart.bat"
Write-Host "  - Ver procesos PHP: Get-Process | Where-Object { `$_.ProcessName -like '*php*' }"
Write-Host "  - Matar proceso específico: Stop-Process -Id [PID] -Force"
