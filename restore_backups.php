<?php
// Restaura todos los archivos *.php.bak sobrescribiendo el .php original
$root = __DIR__;
$restaurados = 0;
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $f) {
  if (!$f->isFile()) continue;
  $path = $f->getPathname();
  if (substr($path, -8) === '.php.bak') {
    $orig = substr($path, 0, -4); // quitar ".bak"
    if (@copy($path, $orig)) {
      $restaurados++;
    }
  }
}
header('Content-Type: text/plain; charset=utf-8');
echo "Restaurados: $restaurados archivos\n";