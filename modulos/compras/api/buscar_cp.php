<?php
require_once '../../config/config.php';
header('Content-Type: application/json; charset=utf-8');

$cp = isset($_GET['cp']) ? preg_replace('/\D/','', $_GET['cp']) : '';
$provincia_id = isset($_GET['provincia_id']) ? (int)$_GET['provincia_id'] : 0;

try {
  $pdo = conectarDB();
  $pdo->exec("SET NAMES utf8mb4");

  if ($provincia_id > 0) {
    // Listar localidades y CP por provincia
    $st = $pdo->prepare("
      SELECT a.localidad, MIN(a.cp) AS cp
      FROM ar_codigos_postales a
      WHERE a.provincia_id = ?
      GROUP BY a.localidad
      ORDER BY a.localidad
    ");
    $st->execute([$provincia_id]);
    $locs = [];
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
      $locs[] = ['nombre'=>$r['localidad'], 'cp'=>(int)$r['cp']];
    }
    echo json_encode(['ok'=>true,'provincia_id'=>$provincia_id,'localidades'=>$locs], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($cp === '' || !ctype_digit($cp)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Parámetros inválidos']); exit;
  }

  $st = $pdo->prepare("
    SELECT a.cp, p.id AS provincia_id, p.nombre AS provincia, a.localidad
    FROM ar_codigos_postales a
    JOIN provincias p ON p.id = a.provincia_id
    WHERE a.cp = ?
    ORDER BY a.localidad
  ");
  $st->execute([(int)$cp]);

  $provincia = null; $provincia_id = null; $locs = [];
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $provincia_id = (int)$r['provincia_id'];
    $provincia = $r['provincia'];
    $locs[] = $r['localidad'];
  }
  echo json_encode(['ok'=>true,'cp'=>(int)$cp,'provincia_id'=>$provincia_id,'provincia'=>$provincia,'localidades'=>$locs], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Error de servidor']);
}