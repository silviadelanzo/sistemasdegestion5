<?php
// modulos/compras/ocr_remitos/upload_ocr.php
// Endpoint para subir JPG/PNG/PDF y procesar OCR + parseo de remito

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    // Seguridad básica y contexto de app
    require_once __DIR__ . '/../../../config/config.php';
    iniciarSesionSegura();
    // Aceptar cualquiera de las claves de sesión usadas por el sistema
    if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    // Dependencias OCR/Parser
    require_once __DIR__ . '/ocr_processor.php';
    require_once __DIR__ . '/ai_parser.php';
    require_once __DIR__ . '/product_matcher.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    // Validar archivos
    if (empty($_FILES)) {
        echo json_encode(['success' => false, 'error' => 'No se recibieron archivos']);
        exit;
    }

    // Normalizar estructura de archivos (admite files[] o file)
    $files = [];
    foreach ($_FILES as $field => $info) {
        if (is_array($info['name'])) {
            $count = count($info['name']);
            for ($i = 0; $i < $count; $i++) {
                $files[] = [
                    'name' => $info['name'][$i],
                    'type' => $info['type'][$i],
                    'tmp_name' => $info['tmp_name'][$i],
                    'error' => $info['error'][$i],
                    'size' => $info['size'][$i],
                ];
            }
        } else {
            $files[] = $info;
        }
    }

    // Directorio de subida
    $uploadDir = realpath(__DIR__ . '/../../../') . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ocr' . DIRECTORY_SEPARATOR;
    if ($uploadDir === false) {
        $uploadDir = __DIR__ . '/../../../assets/uploads/ocr/';
    }
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('No se pudo crear la carpeta de subidas: ' . $uploadDir);
        }
    }

    $pdo = conectarDB();
    $ocr = new OCRProcessor();
    $parser = new AIParser();
    $proveedorContext = null;
    if (isset($_POST['proveedor_id']) && is_numeric($_POST['proveedor_id'])) {
        $proveedorContext = (int)$_POST['proveedor_id'];
    }
    $matcher = new ProductMatcher($pdo, $proveedorContext);

    $results = [];
    $totalProductos = 0;
    $textoConcat = '';
    $confAcumulada = 0;
    $archivosProcesados = 0;

    foreach ($files as $file) {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $results[] = [
                'original_name' => $file['name'] ?? 'archivo',
                'success' => false,
                'error' => 'Error al subir el archivo'
            ];
            continue;
        }

        // Validar tamaño (máx 20MB)
        if (($file['size'] ?? 0) > 20 * 1024 * 1024) {
            $results[] = [
                'original_name' => $file['name'],
                'success' => false,
                'error' => 'Archivo supera 20MB'
            ];
            continue;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf']; // TIFF requiere Imagick/GD especial
        if (!in_array($ext, $allowed, true)) {
            $results[] = [
                'original_name' => $file['name'],
                'success' => false,
                'error' => 'Formato no soportado. Use JPG, PNG o PDF'
            ];
            continue;
        }

        $safeBase = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $destRel = 'assets/uploads/ocr/' . $safeBase . '_' . uniqid() . '.' . $ext;
        $destAbs = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $destRel;

        if (!is_dir(dirname($destAbs))) {
            mkdir(dirname($destAbs), 0775, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
            $results[] = [
                'original_name' => $file['name'],
                'success' => false,
                'error' => 'No se pudo guardar el archivo'
            ];
            continue;
        }

        try {
            $imageForOCR = $destAbs;

            // Si es PDF, intentar extraer primera página como imagen si hay Imagick
            if ($ext === 'pdf') {
                if (class_exists('Imagick')) {
                    $img = new Imagick();
                    $img->setResolution(200, 200);
                    $img->readImage($destAbs . '[0]'); // primera página
                    $img->setImageFormat('png');
                    $pngPath = preg_replace('/\.pdf$/i', '.png', $destAbs);
                    $img->writeImage($pngPath);
                    $img->clear();
                    $img->destroy();
                    $imageForOCR = $pngPath;
                } else {
                    // Fallback simple: intentar leer texto si es PDF con texto (no escaneado)
                    $pdfText = '';
                    try {
                        // Método rápido: usar pdftotext si está en PATH (opcional)
                        $cmd = 'pdftotext -layout -nopgbrk "' . $destAbs . '" -';
                        $out = @shell_exec($cmd);
                        if (is_string($out) && trim($out) !== '') {
                            $pdfText = $out;
                        }
                    } catch (Throwable $e) {}

                    if ($pdfText === '') {
                        $results[] = [
                            'original_name' => $file['name'],
                            'saved_path' => $destRel,
                            'success' => false,
                            'error' => 'PDF recibido pero no hay Imagick ni pdftotext para procesarlo'
                        ];
                        continue;
                    }

                    // Parsear y matchear directo desde texto extraído
                    $confidence = method_exists($ocr, 'getConfidenceScore') ? $ocr->getConfidenceScore($pdfText) : 0;
                    $parsed = $parser->parseRemito($pdfText, []);
                    $matchSummary = $matcher->matchProducts($parsed['productos']);

                    // Construir detalles por ítem
                    $buildItems = function(array $parsed, array $matchSummary): array {
                        $items = [];
                        // Índice auxiliar para ligar por referencia de texto/código
                        foreach ($matchSummary['exact_matches'] as $m) {
                            $rp = $m['remito_product'] ?? [];
                            $items[] = [
                                'descripcion' => $rp['descripcion'] ?? '',
                                'cantidad' => isset($rp['cantidad']) ? (float)$rp['cantidad'] : 1.0,
                                'codigo' => $rp['codigo'] ?? '',
                                'status' => 'exact',
                                'producto_id' => (int)($m['db_product']['id'] ?? 0),
                                'confidence' => 1.0
                            ];
                        }
                        foreach ($matchSummary['fuzzy_matches'] as $m) {
                            $rp = $m['remito_product'] ?? [];
                            $items[] = [
                                'descripcion' => $rp['descripcion'] ?? '',
                                'cantidad' => isset($rp['cantidad']) ? (float)$rp['cantidad'] : 1.0,
                                'codigo' => $rp['codigo'] ?? '',
                                'status' => 'fuzzy',
                                'producto_id' => (int)($m['db_product']['id'] ?? 0),
                                'confidence' => isset($m['confidence']) ? (float)$m['confidence'] : 0.0
                            ];
                        }
                        foreach ($matchSummary['conflicts'] as $m) {
                            $rp = $m['remito_product'] ?? [];
                            $items[] = [
                                'descripcion' => $rp['descripcion'] ?? '',
                                'cantidad' => isset($rp['cantidad']) ? (float)$rp['cantidad'] : 1.0,
                                'codigo' => $rp['codigo'] ?? '',
                                'status' => 'conflict',
                                'producto_id' => 0,
                                'confidence' => 0.0
                            ];
                        }
                        foreach ($matchSummary['new_products'] as $m) {
                            $rp = $m['remito_product'] ?? [];
                            $items[] = [
                                'descripcion' => $rp['descripcion'] ?? '',
                                'cantidad' => isset($rp['cantidad']) ? (float)$rp['cantidad'] : 1.0,
                                'codigo' => $rp['codigo'] ?? '',
                                'status' => 'new',
                                'producto_id' => 0,
                                'confidence' => 0.0
                            ];
                        }
                        return $items;
                    };

                    // === Guardar en BD como remito pendiente (igual que rama general) ===
                    $genCodigo = function (PDO $db): string {
                        try {
                            $max = $db->query("SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) FROM remitos WHERE codigo LIKE 'REM-%'")->fetchColumn();
                            $next = (int)$max + 1;
                            return 'REM-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
                        } catch (Throwable $e) {
                            $row = $db->query("SELECT codigo FROM remitos ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                            if ($row && preg_match('/^REM-(\d{1,})$/', $row['codigo'], $m)) {
                                $next = (int)$m[1] + 1;
                            } else {
                                $next = 1;
                            }
                            return 'REM-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
                        }
                    };

                    $usuarioId = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : (isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 1);
                    $proveedorId = $proveedorContext ?: null;
                    $proveedorCodigo = null;
                    if ($proveedorId) {
                        $stPC = $pdo->prepare('SELECT codigo FROM proveedores WHERE id = ?');
                        $stPC->execute([$proveedorId]);
                        $proveedorCodigo = $stPC->fetchColumn() ?: null;
                    }

                    $remitoCodigo = $genCodigo($pdo);
                    $numeroRemitoProveedor = pathinfo($file['name'], PATHINFO_FILENAME);

                    $dbSaved = null;
                    $pdo->beginTransaction();
                    try {
                        $stmtR = $pdo->prepare("INSERT INTO remitos (codigo, numero_remito_proveedor, codigo_proveedor, proveedor_id, fecha_entrega, estado, observaciones, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmtR->execute([
                            $remitoCodigo,
                            $numeroRemitoProveedor,
                            $proveedorCodigo,
                            $proveedorId,
                            date('Y-m-d'),
                            'pendiente',
                            'Cargado por OCR',
                            $usuarioId,
                        ]);
                        $remitoId = (int)$pdo->lastInsertId();

                        if (!empty($matchSummary['exact_matches'])) {
                            $stmtD = $pdo->prepare("INSERT INTO remito_detalles (remito_id, producto_id, cantidad, codigo_producto_proveedor, observaciones) VALUES (?, ?, ?, ?, ?)");
                            foreach ($matchSummary['exact_matches'] as $m) {
                                $prodId = (int)$m['db_product']['id'];
                                $rp = $m['remito_product'];
                                $cant = isset($rp['cantidad']) ? (float)$rp['cantidad'] : 1.0;
                                $codProv = isset($rp['codigo']) ? (string)$rp['codigo'] : '';
                                $obs = '';
                                $stmtD->execute([$remitoId, $prodId, $cant, $codProv, $obs]);
                            }
                        }

                        $pdo->commit();
                        $dbSaved = [
                            'remito_id' => $remitoId,
                            'codigo' => $remitoCodigo,
                            'estado' => 'pendiente',
                            'items' => count($matchSummary['exact_matches'] ?? []),
                        ];
                    } catch (Throwable $e) {
                        $pdo->rollBack();
                        $dbSaved = ['error' => $e->getMessage()];
                    }

                    $results[] = [
                        'original_name' => $file['name'],
                        'saved_path' => $destRel,
                        'success' => true,
                        'text' => $pdfText,
                        'confidence' => $confidence,
                        'parsed' => $parsed,
                        'match' => [
                            'exact' => count($matchSummary['exact_matches']),
                            'fuzzy' => count($matchSummary['fuzzy_matches']),
                            'new' => count($matchSummary['new_products']),
                            'conflicts' => count($matchSummary['conflicts'])
                        ],
                        'items' => $buildItems($parsed, $matchSummary),
                        'db_saved' => $dbSaved
                    ];

                    $archivosProcesados++;
                    $textoConcat .= ($textoConcat ? "\n\n" : '') . $pdfText;
                    $confAcumulada += (float)$confidence;
                    $totalProductos += is_array($parsed['productos']) ? count($parsed['productos']) : 0;
                    continue;
                }
            }

            // Ejecutar OCR
            $texto = $ocr->extractText($imageForOCR);
            $confidence = method_exists($ocr, 'getConfidenceScore') ? $ocr->getConfidenceScore($texto) : 0;

            // Parsear remito → productos
            $parsed = $parser->parseRemito($texto, []);

            // Intentar matchear contra productos existentes
            $matchSummary = $matcher->matchProducts($parsed['productos']);

            // Construir detalles por ítem
            $buildItems = function(array $parsed, array $matchSummary): array {
                $items = [];
                foreach ($matchSummary['exact_matches'] as $m) {
                    $rp = $m['remito_product'] ?? [];
                    $items[] = [
                        'descripcion' => $rp['descripcion'] ?? '',
                        'cantidad' => isset($rp['cantidad']) ? (float)$rp['cantidad'] : 1.0,
                        'codigo' => $rp['codigo'] ?? '',
                        'status' => 'exact',
                        'producto_id' => (int)($m['db_product']['id'] ?? 0),
                        'confidence' => 1.0
                    ];
                }
                foreach ($matchSummary['fuzzy_matches'] as $m) {
                    $rp = $m['remito_product'] ?? [];
                    $items[] = [
                        'descripcion' => $rp['descripcion'] ?? '',
                        'cantidad' => isset($rp['cantidad']) ? (float)$rp['cantidad'] : 1.0,
                        'codigo' => $rp['codigo'] ?? '',
                        'status' => 'fuzzy',
                        'producto_id' => (int)($m['db_product']['id'] ?? 0),
                        'confidence' => isset($m['confidence']) ? (float)$m['confidence'] : 0.0
                    ];
                }
                foreach ($matchSummary['conflicts'] as $m) {
                    $rp = $m['remito_product'] ?? [];
                    $items[] = [
                        'descripcion' => $rp['descripcion'] ?? '',
                        'cantidad' => isset($rp['cantidad']) ? (float)$rp['cantidad'] : 1.0,
                        'codigo' => $rp['codigo'] ?? '',
                        'status' => 'conflict',
                        'producto_id' => 0,
                        'confidence' => 0.0
                    ];
                }
                foreach ($matchSummary['new_products'] as $m) {
                    $rp = $m['remito_product'] ?? [];
                    $items[] = [
                        'descripcion' => $rp['descripcion'] ?? '',
                        'cantidad' => isset($rp['cantidad']) ? (float)$rp['cantidad'] : 1.0,
                        'codigo' => $rp['codigo'] ?? '',
                        'status' => 'new',
                        'producto_id' => 0,
                        'confidence' => 0.0
                    ];
                }
                return $items;
            };

            // === Guardar en BD como remito pendiente ===
            // Helpers locales
            $genCodigo = function (PDO $db): string {
                try {
                    $max = $db->query("SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) FROM remitos WHERE codigo LIKE 'REM-%'")->fetchColumn();
                    $next = (int)$max + 1;
                    return 'REM-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
                } catch (Throwable $e) {
                    // Fallback por id
                    $row = $db->query("SELECT codigo FROM remitos ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    if ($row && preg_match('/^REM-(\d{1,})$/', $row['codigo'], $m)) {
                        $next = (int)$m[1] + 1;
                    } else {
                        $next = 1;
                    }
                    return 'REM-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
                }
            };

            $usuarioId = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : (isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 1);
            $proveedorId = $proveedorContext ?: null;
            $proveedorCodigo = null;
            if ($proveedorId) {
                $stPC = $pdo->prepare('SELECT codigo FROM proveedores WHERE id = ?');
                $stPC->execute([$proveedorId]);
                $proveedorCodigo = $stPC->fetchColumn() ?: null;
            }

            $remitoCodigo = $genCodigo($pdo);
            $numeroRemitoProveedor = pathinfo($file['name'], PATHINFO_FILENAME);

            $pdo->beginTransaction();
            try {
                // Insert remito
                $stmtR = $pdo->prepare("INSERT INTO remitos (codigo, numero_remito_proveedor, codigo_proveedor, proveedor_id, fecha_entrega, estado, observaciones, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtR->execute([
                    $remitoCodigo,
                    $numeroRemitoProveedor,
                    $proveedorCodigo,
                    $proveedorId,
                    date('Y-m-d'),
                    'pendiente',
                    'Cargado por OCR',
                    $usuarioId,
                ]);
                $remitoId = (int)$pdo->lastInsertId();

                // Insert detalles solo para coincidencias exactas
                if (!empty($matchSummary['exact_matches'])) {
                    $stmtD = $pdo->prepare("INSERT INTO remito_detalles (remito_id, producto_id, cantidad, codigo_producto_proveedor, observaciones) VALUES (?, ?, ?, ?, ?)");
                    foreach ($matchSummary['exact_matches'] as $m) {
                        $prodId = (int)$m['db_product']['id'];
                        $rp = $m['remito_product'];
                        $cant = isset($rp['cantidad']) ? (float)$rp['cantidad'] : 1.0;
                        $codProv = isset($rp['codigo']) ? (string)$rp['codigo'] : '';
                        $obs = '';
                        $stmtD->execute([$remitoId, $prodId, $cant, $codProv, $obs]);
                    }
                }

                $pdo->commit();
                $dbSaved = [
                    'remito_id' => $remitoId,
                    'codigo' => $remitoCodigo,
                    'estado' => 'pendiente',
                    'items' => count($matchSummary['exact_matches'] ?? []),
                ];
            } catch (Throwable $e) {
                $pdo->rollBack();
                $dbSaved = ['error' => $e->getMessage()];
            }

            $results[] = [
                'original_name' => $file['name'],
                'saved_path' => $destRel,
                'success' => true,
                'text' => $texto,
                'confidence' => $confidence,
                'parsed' => $parsed,
                'match' => [
                    'exact' => count($matchSummary['exact_matches']),
                    'fuzzy' => count($matchSummary['fuzzy_matches']),
                    'new' => count($matchSummary['new_products']),
                    'conflicts' => count($matchSummary['conflicts'])
                ],
                'items' => $buildItems($parsed, $matchSummary),
                'db_saved' => $dbSaved
            ];

            $archivosProcesados++;
            $textoConcat .= ($textoConcat ? "\n\n" : '') . $texto;
            $confAcumulada += (float)$confidence;
            $totalProductos += is_array($parsed['productos']) ? count($parsed['productos']) : 0;
        } catch (Throwable $e) {
            $results[] = [
                'original_name' => $file['name'],
                'saved_path' => $destRel,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    $avgConf = $archivosProcesados > 0 ? round($confAcumulada / $archivosProcesados, 2) : 0;

    echo json_encode([
        'success' => true,
        'files' => $results,
        'summary' => [
            'procesados' => $archivosProcesados,
            'productos_detectados' => $totalProductos,
            'confidence_promedio' => $avgConf,
            'texto_concatenado' => $textoConcat
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
