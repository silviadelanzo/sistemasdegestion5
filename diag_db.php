<?php
require_once '../../config/config.php'; // ajusta la ruta si es necesario
header('Content-Type: application/json; charset=utf-8');

$out = ['ok'=>true, 'steps'=>[]];

try {
  $pdo = conectarDB();
  $pdo->exec("SET NAMES utf8mb4");
  $out['db'] = $pdo->query("SELECT DATABASE() AS db")->fetch(PDO::FETCH_ASSOC)['db'] ?? '';

  $out['counts'] = [
    'ar_codigos_postales' => (int)$pdo->query("SELECT COUNT(*) FROM ar_codigos_postales")->fetchColumn(),
    'provincias'          => (int)$pdo->query("SELECT COUNT(*) FROM provincias")->fetchColumn(),
    'paises'              => (int)$pdo->query("SELECT COUNT(*) FROM paises")->fetchColumn(),
  ];

  // Muestra algunas filas ejemplo
  $ej = $pdo->query("
    SELECT a.localidad, a.cp, a.provincia_id
    FROM ar_codigos_postales a
    ORDER BY a.localidad
    LIMIT 3
  ")->fetchAll(PDO::FETCH_ASSOC);
  $out['ejemplos_cp'] = $ej;

  echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}