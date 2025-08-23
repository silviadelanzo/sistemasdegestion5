<?php
/*
  Fix v2: Rutas hacia config/include_dev.php (detección amplia)
  - Detecta cualquier require/require_once __DIR__ . '/config/include_dev.php';
  - Excluye .bak, carpeta config, y el propio include_dev.php
  Uso:
    Navegador (simulación):  http://localhost/sistemadgestion5/fix_include_dev_paths.php
    Navegador (aplicar):     http://localhost/sistemadgestion5/fix_include_dev_paths.php?apply=1
    Consola (simulación):    php fix_include_dev_paths.php
    Consola (aplicar):       php fix_include_dev_paths.php --apply
*/

declare(strict_types=1);

// Config
$projectRoot = __DIR__;
$targetRel   = 'config/include_dev.php';
$excludeDirs = ['config','logs','vendor','.git','__MACOSX','node_modules','backup','sql_backup','.vscode'];
$excludeFiles = ['include_dev.php'];
$excludeExtensions = ['bak'];

// Modo aplicar o simulación
$apply = false;
if (php_sapi_name() === 'cli') {
  $apply = in_array('--apply', $argv ?? [], true);
} else {
  $apply = isset($_GET['apply']) && $_GET['apply'] == '1';
}

// Utilidad: path relativo entre dos absolutos
function relpath(string $from, string $to): string {
  $from = str_replace('\\','/', realpath($from));
  $to   = str_replace('\\','/', realpath($to));
  $fromParts = array_values(array_filter(explode('/', $from), 'strlen'));
  $toParts   = array_values(array_filter(explode('/', $to), 'strlen'));
  $len = min(count($fromParts), count($toParts));
  $i = 0;
  while ($i < $len && $fromParts[$i] === $toParts[$i]) { $i++; }
  $up = array_fill(0, count($fromParts) - $i, '..');
  $down = array_slice($toParts, $i);
  $rel = implode('/', array_merge($up, $down));
  return $rel === '' ? '.' : $rel;
}

// Recolectar PHP
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS));
$phpFiles = [];
foreach ($rii as $file) {
  if (!$file->isFile()) continue;
  $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
  if ($ext !== 'php') continue;
  if (in_array($ext, $excludeExtensions, true)) continue;

  $rel = str_replace('\\','/', substr($file->getPathname(), strlen($projectRoot)+1));
  // Excluir carpetas
  $skip = false;
  foreach ($excludeDirs as $ed) {
    if (preg_match('#(^|/)'.preg_quote($ed,'#').'(/|$)#', $rel)) { $skip = true; break; }
  }
  if ($skip) continue;
  // Excluir por nombre
  if (in_array(basename($rel), $excludeFiles, true)) continue;

  $phpFiles[] = $rel;
}

$report = [];
$total = 0;
$changed = 0;
$matches = 0;

$targetAbs = $projectRoot . DIRECTORY_SEPARATOR . $targetRel;
if (!file_exists($targetAbs)) {
  echo "ERROR: No se encontró {$targetRel} en el proyecto.\n";
  exit(1);
}

// Regex amplio: toma toda la sentencia require/include ... ;
$statementRe = '/\b(require|include)(?:_once)?\s*\([^;]*include_dev\.php[^;]*\)\s*;|\b(require|include)(?:_once)?\s+[^;]*include_dev\.php[^;]*;/i';

foreach ($phpFiles as $relPath) {
  $total++;
  $abs = $projectRoot . DIRECTORY_SEPARATOR . $relPath;
  $content = @file_get_contents($abs);
  if ($content === false || trim($content) === '') continue;

  if (!preg_match_all($statementRe, $content, $m)) {
    continue;
  }
  $matches += count($m[0]);

  // Calcular ruta correcta desde el archivo actual
  $fileDir = dirname($abs);
  $relative = relpath($fileDir, $targetAbs);
  $relative = str_replace('\\','/', $relative);
  $correctLine = "require_once __DIR__ . '/{$relative}';";

  // Reemplazar cada sentencia completa que contenga include_dev.php
  $new = preg_replace($statementRe, $correctLine, $content);
  if ($new !== null && $new !== $content) {
    $changed++;
    $report[] = [$relPath, $correctLine];
    if ($apply) {
      file_put_contents($abs, $new);
    }
  }
}

// Reporte
$csvPath = $projectRoot . DIRECTORY_SEPARATOR . 'fix_include_report.csv';
if (!empty($report)) {
  $fh = fopen($csvPath, 'w');
  fputcsv($fh, ['File','Include']);
  foreach ($report as $row) fputcsv($fh, $row);
  fclose($fh);
}

$mode = $apply ? 'APLICADO' : 'SIMULACION';
$msg = "Modo: {$mode}\nPHP escaneados: {$total}\nCoincidencias encontradas: {$matches}\nArchivos corregidos: {$changed}\n";
if (php_sapi_name() === 'cli') {
  echo $msg;
  if (!empty($report)) echo "Reporte: {$csvPath}\n";
} else {
  echo nl2br(htmlentities($msg));
  if (!empty($report)) echo "Reporte: {$csvPath}";
}