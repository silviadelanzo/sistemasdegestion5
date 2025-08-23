<?php
// modulos/compras/api/buscar_localidades.php
// Carga de config sin tocarlo
require_once __DIR__ . '/../../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$provinciaId = isset($_GET['provincia_id']) ? (int)$_GET['provincia_id'] : 0;

if ($q === '' || mb_strlen($q) < 2) {
  echo json_encode(['ok'=>false, 'msg'=>'Usa ?q= al menos 2 letras']);
  exit;
}

try {
  $pdo = conectarDB();
  $pdo->exec("SET NAMES utf8mb4");

  // Búsqueda por nombre de localidad, tolerante a mayúsculas/tildes (según collation de tu DB)
  $sql = "
    SELECT a.localidad,
           MIN(a.cp) AS cp,
           a.provincia_id,
           COALESCE(p.nombre, '') AS provincia
    FROM ar_codigos_postales a
    LEFT JOIN provincias p ON p.id = a.provincia_id
    WHERE a.localidad COLLATE utf8mb4_general_ci LIKE ?
  ";
  $params = ['%' . $q . '%'];

  if ($provinciaId > 0) {
    $sql .= " AND a.provincia_id = ? ";
    $params[] = $provinciaId;
  }

  $sql .= "
    GROUP BY a.localidad, a.provincia_id, p.nombre
    ORDER BY a.localidad
    LIMIT 50
  ";

  $st = $pdo->prepare($sql);
  $st->execute($params);

  $items = [];
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $items[] = [
      'localidad'    => $r['localidad'],
      'cp'           => (int)$r['cp'],
      'provincia_id' => (int)$r['provincia_id'],
      'provincia'    => $r['provincia'],
    ];
  }

  echo json_encode(['ok'=>true, 'items'=>$items, 'total'=>count($items)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}