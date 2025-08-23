param(
  [string]$Root = ".",
  [switch]$WhatIf  # Modo simulación: muestra cambios pero no escribe
)

# Config
$includeTarget = "config/include_dev.php"

# Carpetas/archivos a excluir
$excludeDirs = @(
  ".git", "config", "logs", "vendor", "dompdf", "node_modules", "assets",
  ".vscode", "backup", "database", "__MACOSX", "sql_backup"
)
$excludeFiles = @("login.php", "index_cli.php")

# Regex para detectar si ya existe un include a include_dev.php
$includeRegex = [regex]::Escape($includeTarget).Replace("/", "[/\\]") # tolerante a / o \
$alreadyRegex = [regex]"require(?:_once)?\s*\(?\s*['""](?:.*?$includeRegex)['""]\s*\)?\s*;"

# Helpers
function Get-RelativePath($from, $to) {
  $fromUri = New-Object System.Uri((Resolve-Path $from).Path)
  $toUri   = New-Object System.Uri((Resolve-Path $to).Path)
  $relUri  = $fromUri.MakeRelativeUri($toUri)
  return [System.Uri]::UnescapeDataString($relUri.ToString()).Replace("%20"," ").Replace("/", "/")
}

function Compute-Include-Path($filePath, $rootFull) {
  # filePath: ruta completa del archivo PHP
  # Calcula la ruta relativa desde el archivo PHP hacia $rootFull\$includeTarget
  $target = Join-Path $rootFull $includeTarget
  $fileDir = Split-Path -Parent $filePath
  $rel = Get-RelativePath $fileDir $target
  # Asegurar separadores tipo POSIX en PHP
  return $rel.Replace("\", "/")
}

# Inicio
$rootFull = Resolve-Path $Root
Write-Host "Raíz del proyecto: $rootFull" -ForegroundColor Cyan
$phpFiles = Get-ChildItem -Path $rootFull -Recurse -Include *.php -File | Where-Object {
  # Excluir carpetas
  $rel = $_.FullName.Substring($rootFull.Path.Length).TrimStart('\','/').Replace('\','/')
  -not ($excludeDirs | ForEach-Object { $rel -split '/' | ForEach-Object { $_ } } | Where-Object { $excludeDirs -contains $_ }) `
  -and -not ($excludeFiles -contains $_.Name)
}

$changed = 0
$skippedExists = 0
$skippedExcluded = 0
$total = 0

foreach ($file in $phpFiles) {
  $total++
  $relPath = $file.FullName.Substring($rootFull.Path.Length).TrimStart('\','/').Replace('\','/')
  # Excluir si el path contiene alguna carpeta excluida
  if ($excludeDirs | Where-Object { $relPath -match ("(^|/)" + [regex]::Escape($_) + "(/|$)") }) {
    $skippedExcluded++
    continue
  }

  $content = Get-Content -Raw -Path $file.FullName -ErrorAction SilentlyContinue
  if ([string]::IsNullOrEmpty($content)) { continue }

  # Si ya tiene el include a include_dev.php, saltar
  if ($alreadyRegex.IsMatch($content)) {
    $skippedExists++
    continue
  }

  # Determinar línea de inserción y ruta relativa
  $relInclude = Compute-Include-Path $file.FullName $rootFull
  $insertLine = "require_once __DIR__ . '/" + ("../" * ($relPath.Split('/').Length - 1)) + "';" # fallback si hiciera falta

  # Preferimos ruta relativa calculada a partir del directorio del archivo
  # Construimos un require_once robusto con __DIR__ y la ruta relativa desde el archivo al include
  # Ej: si $relInclude = "../../config/include_dev.php", insertamos:
  # require_once __DIR__ . '/../../config/include_dev.php';
  $dirToTarget = (Get-RelativePath (Split-Path -Parent $file.FullName) (Join-Path $rootFull $includeTarget)).Replace("\","/")
  $insertRequire = "require_once __DIR__ . '/$dirToTarget';"

  # Insertar inmediatamente después de <?php si existe, o al principio
  $newContent = $null
  if ($content -match '^\s*<\?php') {
    # Insertar tras la primera línea PHP
    $idx = $content.IndexOf("<?php")
    $after = $idx + 5
    # Saltar posibles saltos de línea inmediatos
    $rest = $content.Substring($after)
    # Si ya está la protección por sesión propia del archivo, igual insertamos antes
    $newContent = $content.Insert($after, "`r`n$insertRequire`r`n")
  } else {
    $newContent = "<?php`r`n$insertRequire`r`n?>`r`n" + $content
  }

  Write-Host "Modificar: $relPath -> $insertRequire" -ForegroundColor Yellow
  if (-not $WhatIf) {
    # Backup .bak
    Copy-Item -Path $file.FullName -Destination ($file.FullName + ".bak") -Force
    # Escribir
    Set-Content -Path $file.FullName -Value $newContent -Encoding UTF8
    $changed++
  }
}

Write-Host ""
Write-Host "Resumen:" -ForegroundColor Cyan
Write-Host ("  PHP totales considerados: {0}" -f $total)
Write-Host ("  Modificados:              {0}" -f $changed) -ForegroundColor Green
Write-Host ("  Ya tenían include:        {0}" -f $skippedExists)
Write-Host ("  Excluidos por carpeta:    {0}" -f $skippedExcluded)
Write-Host ""
Write-Host "Sugerencia: primero ejecutar con -WhatIf para revisar." -ForegroundColor Cyan
Write-Host "Ejemplos:"
Write-Host "  pwsh -File .\\add_include_dev.ps1 -Root 'C:\\xampp\\htdocs\\sistemadgestion5' -WhatIf"
Write-Host "  pwsh -File .\\add_include_dev.ps1 -Root 'C:\\xampp\\htdocs\\sistemadgestion5'"