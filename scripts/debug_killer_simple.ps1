# DETENER DEBUG Y LIMPIAR PROCESOS
Write-Host "🛑 DETENIENDO DEBUG Y LIMPIANDO PROCESOS" -ForegroundColor Red

# 1. Verificar y detener procesos PHP problemáticos
Write-Host "📋 Verificando procesos PHP..."
$procesosPHP = Get-Process | Where-Object { $_.ProcessName -like "*php*" }

if ($procesosPHP) {
    foreach ($proceso in $procesosPHP) {
        Write-Host "  PHP PID: $($proceso.Id) | CPU: $($proceso.CPU) | Memoria: $([math]::Round($proceso.WorkingSet/1MB,2)) MB"
        
        # Si usa mucha CPU o memoria, es probable que sea debug
        if ($proceso.CPU -gt 5 -or $proceso.WorkingSet -gt 200MB) {
            Write-Host "    ⚠️ Proceso sospechoso - deteniendo..." -ForegroundColor Yellow
            try {
                Stop-Process -Id $proceso.Id -Force
                Write-Host "    ✅ Proceso detenido" -ForegroundColor Green
            } catch {
                Write-Host "    ❌ Error: $($_.Exception.Message)" -ForegroundColor Red
            }
        }
    }
} else {
    Write-Host "  ✅ No hay procesos PHP activos" -ForegroundColor Green
}

# 2. Verificar puertos de debug
Write-Host ""
Write-Host "🔍 Verificando puertos debug..."
try {
    $xdebugConns = Get-NetTCPConnection -LocalPort 9003 -ErrorAction SilentlyContinue
    if ($xdebugConns) {
        Write-Host "  ⚠️ Conexiones Xdebug activas en puerto 9003" -ForegroundColor Yellow
    } else {
        Write-Host "  ✅ Puerto 9003 (Xdebug) libre" -ForegroundColor Green
    }
} catch {
    Write-Host "  ✅ Puerto 9003 libre" -ForegroundColor Green
}

# 3. Limpiar sesiones PHP
Write-Host ""
Write-Host "🗑️ Limpiando sesiones PHP..."
$sessionPath = "C:\xampp\tmp"
if (Test-Path $sessionPath) {
    $sessionFiles = Get-ChildItem "$sessionPath\sess_*" -ErrorAction SilentlyContinue
    if ($sessionFiles) {
        $sessionFiles | Remove-Item -Force -ErrorAction SilentlyContinue
        Write-Host "  ✅ $($sessionFiles.Count) archivos de sesión eliminados"
    } else {
        Write-Host "  ✅ No hay sesiones para limpiar"
    }
} else {
    Write-Host "  ⚠️ Carpeta de sesiones no encontrada"
}

# 4. Verificar VS Code
Write-Host ""
Write-Host "⚙️ Verificando VS Code..."
if (Test-Path ".\.vscode\launch.json") {
    Write-Host "  ⚠️ launch.json encontrado - posible debug configurado" -ForegroundColor Yellow
} else {
    Write-Host "  ✅ No hay configuración debug en VS Code"
}

# 5. Reporte final
Write-Host ""
Write-Host "📊 ESTADO FINAL:" -ForegroundColor Cyan
$procesosFinales = Get-Process | Where-Object { $_.ProcessName -like "*php*" }
Write-Host "  Procesos PHP activos: $($procesosFinales.Count)"

Write-Host ""
Write-Host "💡 SOLUCIONES PARA LOGIN:" -ForegroundColor Green
Write-Host "1. 🔄 Reiniciar Apache: C:\xampp\apache_restart.bat"
Write-Host "2. 🌐 Usar navegador externo para login"
Write-Host "3. 🚀 Cerrar y reabrir VS Code"
Write-Host "4. 🗑️ Limpiar cache del navegador"

Write-Host ""
Write-Host "✅ Limpieza completada" -ForegroundColor Green
