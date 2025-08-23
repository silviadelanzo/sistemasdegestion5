<?php
// Elimina todos los archivos *.php.bak de forma recursiva
$root = __DIR__;
$eliminados = 0;
$errores = 0;
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $f) {
  if (!$f->isFile()) continue;
  $path = $f->getPathname();
  if (substr($path, -8) === '.php.bak') {
    if (@unlink($path)) {
      $eliminados++;
    } else {
      $errores++;
    }
  }
}
header('Content-Type: text/plain; charset=utf-8');
echo "Backups .php.bak eliminados: $eliminados\n";
if ($errores) echo "No se pudieron eliminar: $errores\n";