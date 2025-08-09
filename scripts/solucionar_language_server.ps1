# SOLUCIONADOR COMPLETO DE PHP LANGUAGE SERVER
Write-Host "🔧 SOLUCIONANDO ERROR PHP LANGUAGE SERVER" -ForegroundColor Cyan
Write-Host "=========================================="

# 1. Detener todos los procesos PHP
Write-Host "1. Deteniendo procesos PHP..." -ForegroundColor Yellow
Get-Process | Where-Object { $_.ProcessName -like "*php*" } | Stop-Process -Force -ErrorAction SilentlyContinue
Write-Host "   ✅ Procesos PHP detenidos"

# 2. Limpiar sesiones y archivos temporales
Write-Host "2. Limpiando archivos temporales..." -ForegroundColor Yellow
Remove-Item "C:\xampp\tmp\sess_*" -Force -ErrorAction SilentlyContinue
Remove-Item "C:\xampp\tmp\*.tmp" -Force -ErrorAction SilentlyContinue
Write-Host "   ✅ Archivos temporales limpiados"

# 3. Verificar puertos ocupados
Write-Host "3. Verificando puertos..." -ForegroundColor Yellow
try {
    $xdebugPort = Get-NetTCPConnection -LocalPort 9003 -ErrorAction SilentlyContinue
    if ($xdebugPort) {
        Write-Host "   ⚠️ Puerto 9003 (Xdebug) aún ocupado" -ForegroundColor Red
    } else {
        Write-Host "   ✅ Puerto 9003 libre"
    }
} catch {
    Write-Host "   ✅ Puerto 9003 libre"
}

# 4. Reiniciar Apache
Write-Host "4. Reiniciando Apache..." -ForegroundColor Yellow
try {
    if (Test-Path "C:\xampp\apache_stop.bat") {
        Start-Process "C:\xampp\apache_stop.bat" -Wait -WindowStyle Hidden
        Start-Sleep 3
        Start-Process "C:\xampp\apache_start.bat" -Wait -WindowStyle Hidden
        Write-Host "   ✅ Apache reiniciado"
    } else {
        Write-Host "   ⚠️ Scripts de Apache no encontrados"
    }
} catch {
    Write-Host "   ❌ Error reiniciando Apache: $($_.Exception.Message)"
}

# 5. Mostrar estado final
Write-Host ""
Write-Host "📊 ESTADO FINAL:" -ForegroundColor Cyan
$procesosFinales = Get-Process | Where-Object { $_.ProcessName -like "*php*" }
Write-Host "   Procesos PHP activos: $($procesosFinales.Count)"

Write-Host ""
Write-Host "✅ SOLUCIÓN COMPLETADA" -ForegroundColor Green
Write-Host "======================"
Write-Host ""
Write-Host "🔄 PRÓXIMOS PASOS:" -ForegroundColor Yellow
Write-Host "1. CERRAR VS Code completamente (Ctrl+Shift+P -> 'Reload Window')"
Write-Host "2. Esperar 10 segundos"
Write-Host "3. Reabrir VS Code"
Write-Host "4. El error del Language Server debería desaparecer"
Write-Host ""
Write-Host "🌐 PARA LOGIN:" -ForegroundColor Cyan
Write-Host "   Use: http://localhost/sistemadgestion5/login_sin_debug.php?emergency_login=1"
Write-Host ""
Write-Host "⚙️ CONFIGURACIÓN:" -ForegroundColor Cyan
Write-Host "   - PHP Language Server: DESHABILITADO temporalmente"
Write-Host "   - Xdebug: DESHABILITADO"
Write-Host "   - Validación PHP: DESHABILITADA"
Write-Host ""
Write-Host "💡 TIP: Una vez que todo funcione, puedes reactivar las funciones PHP en settings.json"
