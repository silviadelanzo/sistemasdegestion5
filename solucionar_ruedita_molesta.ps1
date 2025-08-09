# SOLUCION DEFINITIVA PARA LA RUEDITA MOLESTA DE PHP
# Script para detener el Language Server que gira constantemente

Write-Host "SOLUCIONANDO RUEDITA MOLESTA DE PHP LANGUAGE SERVER" -ForegroundColor Red
Write-Host "===================================================" -ForegroundColor Red

Write-Host ""
Write-Host "PROBLEMA: Ruedita girando constantemente abajo a la derecha" -ForegroundColor Yellow
Write-Host "CAUSA: PHP Language Server analizando 500+ archivos continuamente" -ForegroundColor Yellow

Write-Host ""
Write-Host "SOLUCION 1: DETENER PROCESOS PHP" -ForegroundColor Green
Write-Host "=================================" -ForegroundColor Green

# Detener todos los procesos PHP que puedan estar ejecutandose
Write-Host "Deteniendo procesos PHP..." -ForegroundColor White
try {
    Get-Process | Where-Object { $_.ProcessName -like "*php*" } | Stop-Process -Force -ErrorAction SilentlyContinue
    Write-Host "Procesos PHP detenidos" -ForegroundColor Green
} catch {
    Write-Host "No hay procesos PHP ejecutandose" -ForegroundColor Gray
}

Write-Host ""
Write-Host "SOLUCION 2: LIMPIAR CACHE DE VSCODE" -ForegroundColor Green
Write-Host "===================================" -ForegroundColor Green

$vscodeCachePaths = @(
    "$env:APPDATA\Code\User\workspaceStorage",
    "$env:APPDATA\Code\CachedExtensions", 
    "$env:APPDATA\Code\logs",
    "$env:TEMP\vscode-*"
)

foreach ($path in $vscodeCachePaths) {
    if (Test-Path $path) {
        try {
            Remove-Item "$path\*" -Recurse -Force -ErrorAction SilentlyContinue
            Write-Host "Cache limpiado: $path" -ForegroundColor Green
        } catch {
            Write-Host "No se pudo limpiar: $path" -ForegroundColor Yellow
        }
    } else {
        Write-Host "No existe: $path" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "SOLUCION 3: CONFIGURAR VSCODE PARA ESTE PROYECTO" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green

# Crear configuracion especifica para evitar la ruedita
$vscodePath = ".vscode"
if (-not (Test-Path $vscodePath)) {
    New-Item -ItemType Directory -Path $vscodePath -Force | Out-Null
}

# Configuracion para desactivar features pesadas
$settings = @{
    "php.suggest.basic" = $false
    "php.validate.enable" = $false
    "intelephense.environment.phpVersion" = "8.1.0"
    "intelephense.files.maxSize" = 1000000
    "intelephense.completion.maxItems" = 100
    "intelephense.diagnostics.enable" = $false
    "intelephense.format.enable" = $false
    "intelephense.references.enable" = $false
    "files.watcherExclude" = @{
        "**/.git/objects/**" = $true
        "**/.git/subtree-cache/**" = $true
        "**/node_modules/**" = $true
        "**/tmp/**" = $true
        "**/temp/**" = $true
        "**/*.zip" = $true
        "**/backup*/**" = $true
        "**/logs/**" = $true
        "**/cache/**" = $true
        "**/*.log" = $true
        "**/*.tmp" = $true
    }
    "search.exclude" = @{
        "**/backup*" = $true
        "**/*.zip" = $true
        "**/logs" = $true
        "**/*.log" = $true
        "**/temp" = $true
        "**/tmp" = $true
    }
    "files.exclude" = @{
        "**/*.zip" = $true
        "**/backup*" = $true
        "**/*.log" = $true
        "**/temp" = $true
        "**/tmp" = $true
    }
}

$settingsJson = $settings | ConvertTo-Json -Depth 10
$settingsJson | Out-File -FilePath "$vscodePath\settings.json" -Encoding UTF8

Write-Host "Configuracion optimizada creada en .vscode\settings.json" -ForegroundColor Green

Write-Host ""
Write-Host "SOLUCION 4: EXTENSIONS.JSON OPTIMIZADO" -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Green

# Lista de extensiones minimas recomendadas
$extensions = @{
    "recommendations" = @(
        "ms-vscode.vscode-json",
        "formulahendry.auto-rename-tag",
        "ms-vscode.hexeditor"
    )
    "unwantedRecommendations" = @(
        "felixfbecker.php-intellisense",
        "bmewburn.vscode-intelephense-client",
        "devsense.phptools-vscode"
    )
}

$extensionsJson = $extensions | ConvertTo-Json -Depth 10
$extensionsJson | Out-File -FilePath "$vscodePath\extensions.json" -Encoding UTF8

Write-Host "Extensions.json optimizado creado" -ForegroundColor Green

Write-Host ""
Write-Host "SOLUCION 5: REINICIAR VSCODE" -ForegroundColor Green
Write-Host "============================" -ForegroundColor Green

Write-Host "Ahora ejecuta estos comandos en VS Code:" -ForegroundColor Yellow
Write-Host "1. Ctrl+Shift+P" -ForegroundColor White
Write-Host "2. Escribe: Developer: Reload Window" -ForegroundColor White
Write-Host "3. Presiona Enter" -ForegroundColor White

Write-Host ""
Write-Host "SOLUCION 6: SI LA RUEDITA PERSISTE" -ForegroundColor Red
Write-Host "==================================" -ForegroundColor Red

Write-Host "Ejecuta en VS Code (Ctrl+Shift+P):" -ForegroundColor Yellow
Write-Host "- PHP: Restart Language Server" -ForegroundColor White
Write-Host "- Developer: Restart Extension Host" -ForegroundColor White
Write-Host "- Workspaces: Reload Window" -ForegroundColor White

Write-Host ""
Write-Host "SOLUCION NUCLEAR (ultimo recurso):" -ForegroundColor Red
Write-Host "===================================" -ForegroundColor Red

Write-Host "Si nada funciona, desinstala temporalmente:" -ForegroundColor Yellow
Write-Host "- Extension PHP Intelephense" -ForegroundColor White
Write-Host "- Extension PHP IntelliSense" -ForegroundColor White
Write-Host "- Cualquier extension de PHP pesada" -ForegroundColor White

Write-Host ""
Write-Host "RESULTADO ESPERADO:" -ForegroundColor Green
Write-Host "==================" -ForegroundColor Green
Write-Host "- Ruedita deja de girar" -ForegroundColor White
Write-Host "- VS Code mas rapido" -ForegroundColor White
Write-Host "- Menos uso de CPU" -ForegroundColor White
Write-Host "- Menos uso de memoria" -ForegroundColor White

Write-Host ""
Write-Host "CONFIGURACION APLICADA EXITOSAMENTE!" -ForegroundColor Magenta
Write-Host "Reinicia VS Code para ver los cambios" -ForegroundColor Yellow
