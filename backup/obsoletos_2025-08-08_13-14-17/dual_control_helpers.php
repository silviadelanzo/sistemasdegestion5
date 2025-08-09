<?php
// modulos/compras/ocr_remitos/dual_control_helpers.php

class OCRProcessor
{
    public function processImage($image_path)
    {
        // Usar Tesseract como engine principal
        $command = "tesseract \"$image_path\" stdout -l spa --psm 6";
        $output = shell_exec($command);

        // Calcular confidence básico basado en texto detectado
        $confidence = $this->calculateConfidence($output);

        return [
            'text' => $output ?: '',
            'confidence' => $confidence,
            'engine' => 'tesseract',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function calculateConfidence($text)
    {
        if (empty($text)) return 0;

        // Heurística básica de confidence
        $lines = count(explode("\n", trim($text)));
        $chars = strlen($text);
        $numbers = preg_match_all('/\d/', $text);

        $base_confidence = min(95, max(60, $lines * 10 + $chars * 0.1 + $numbers * 2));

        return round($base_confidence, 2);
    }
}

class ProductMatcher
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function findMatches($producto_proveedor)
    {
        $codigo = $producto_proveedor['codigo'];
        $descripcion = $producto_proveedor['descripcion'];

        // Buscar por código exacto
        $exact_match = $this->findByExactCode($codigo);
        if ($exact_match) {
            return [
                'matches' => [$exact_match],
                'best_match' => array_merge($exact_match, ['similarity' => 1.0]),
                'match_type' => 'exact_code'
            ];
        }

        // Buscar por similitud de descripción
        $description_matches = $this->findByDescription($descripcion);

        if (!empty($description_matches)) {
            usort($description_matches, function ($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            return [
                'matches' => $description_matches,
                'best_match' => $description_matches[0],
                'match_type' => 'description_similarity'
            ];
        }

        return [
            'matches' => [],
            'best_match' => ['similarity' => 0],
            'match_type' => 'no_match'
        ];
    }

    private function findByExactCode($codigo)
    {
        $query = "SELECT id, codigo, descripcion, precio_venta, stock_actual 
                  FROM productos 
                  WHERE codigo = ? AND activo = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function findByDescription($descripcion)
    {
        $query = "SELECT id, codigo, descripcion, precio_venta, stock_actual 
                  FROM productos 
                  WHERE activo = 1 
                  ORDER BY id DESC 
                  LIMIT 50";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $matches = [];
        foreach ($productos as $producto) {
            $similarity = $this->calculateSimilarity($descripcion, $producto['descripcion']);
            if ($similarity > 0.6) {
                $producto['similarity'] = $similarity;
                $matches[] = $producto;
            }
        }

        return $matches;
    }

    private function calculateSimilarity($str1, $str2)
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) return 1.0;

        // Usar similar_text para PHP nativo
        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }
}

// Extensión de la clase principal con métodos faltantes
class DualControlProcessor
{
    private $db;
    private $ocr_processor;
    private $product_matcher;

    public function __construct($database)
    {
        $this->db = $database;
        $this->ocr_processor = new OCRProcessor();
        $this->product_matcher = new ProductMatcher($database);
    }

    // Métodos faltantes para inventario
    public function getProductCurrentStatus($producto_inventario)
    {
        $codigo = $producto_inventario['codigo'];

        $query = "SELECT id, codigo, descripcion, stock_actual, precio_venta, activo 
                  FROM productos 
                  WHERE codigo = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$codigo]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            return [
                'exists' => true,
                'id' => $existing['id'],
                'stock' => $existing['stock_actual'],
                'precio' => $existing['precio_venta'],
                'activo' => $existing['activo']
            ];
        }

        return [
            'exists' => false,
            'stock' => 0,
            'precio' => 0,
            'activo' => false
        ];
    }

    public function determineInventoryAction($producto_inventario, $estado_actual)
    {
        $cantidad_detectada = $producto_inventario['cantidad'];

        if (!$estado_actual['exists']) {
            return 'crear_nuevo';
        }

        $diferencia = abs($cantidad_detectada - $estado_actual['stock']);

        if ($diferencia == 0) {
            return 'sin_cambios';
        } elseif ($diferencia < 10) {
            return 'ajustar_stock';
        } else {
            return 'revisar_discrepancia';
        }
    }

    public function calculateDiscrepancy($producto_inventario, $estado_actual)
    {
        $cantidad_detectada = $producto_inventario['cantidad'];
        $stock_actual = $estado_actual['stock'];

        $diferencia_absoluta = abs($cantidad_detectada - $stock_actual);
        $diferencia_relativa = $stock_actual > 0 ? ($diferencia_absoluta / $stock_actual) * 100 : 100;

        if ($diferencia_relativa > 50) {
            return 'alta';
        } elseif ($diferencia_relativa > 20) {
            return 'media';
        } else {
            return 'baja';
        }
    }

    public function saveInventoryControlDocument($control_id, $documento_id, $productos_control, $html_content)
    {
        $query = "
            INSERT INTO ocr_control_documents 
            (control_id, documento_original_id, productos_control, html_content, tipo, estado, fecha_generacion) 
            VALUES (?, ?, ?, ?, 'inventario_inicial', 'generado', NOW())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $control_id,
            $documento_id,
            json_encode($productos_control),
            $html_content
        ]);
    }

    public function getDiscrepancyClass($discrepancia)
    {
        switch ($discrepancia) {
            case 'alta':
                return 'discrepancy-high';
            case 'media':
                return 'discrepancy-medium';
            case 'baja':
                return 'discrepancy-low';
            default:
                return '';
        }
    }

    public function getComparisonData($comparison_id)
    {
        $query = "SELECT * FROM ocr_document_comparisons WHERE comparison_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$comparison_id]);
        $comparison = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($comparison) {
            $comparison['documento_original'] = json_decode($comparison['documento_original'], true);
            $comparison['documento_control'] = json_decode($comparison['documento_control'], true);
            $comparison['productos_control'] = $comparison['documento_control']['productos_control'] ?? [];
        }

        return $comparison;
    }

    public function updateComparisonStatus($comparison_id, $status, $operario_id, $supervisor_id, $observaciones)
    {
        $query = "
            UPDATE ocr_document_comparisons 
            SET status = ?, operario_id = ?, supervisor_id = ?, observaciones = ?, fecha_aprobacion = NOW() 
            WHERE comparison_id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$status, $operario_id, $supervisor_id, $observaciones, $comparison_id]);
    }

    public function createNewProduct($producto, $proveedor_id)
    {
        $query = "
            INSERT INTO productos 
            (codigo, descripcion, precio_venta, stock_actual, stock_minimo, categoria_id, proveedor_id, activo, fecha_creacion) 
            VALUES (?, ?, ?, ?, 1, 1, ?, 1, NOW())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $producto['codigo_proveedor'],
            $producto['descripcion_proveedor'],
            $producto['precio_proveedor'],
            $producto['cantidad_proveedor'],
            $proveedor_id
        ]);

        return $this->db->lastInsertId();
    }

    public function updateProductStock($producto_id, $cantidad)
    {
        $query = "UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$cantidad, $producto_id]);
    }

    public function logInventoryMovement($producto_id, $cantidad, $tipo, $observaciones)
    {
        $query = "
            INSERT INTO movimientos_inventario 
            (producto_id, tipo_movimiento, cantidad, observaciones, fecha_movimiento, usuario_id) 
            VALUES (?, ?, ?, ?, NOW(), 1)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$producto_id, $tipo, $cantidad, $observaciones]);
    }

    public function createNewInventoryProduct($producto)
    {
        $query = "
            INSERT INTO productos 
            (codigo, descripcion, precio_venta, stock_actual, stock_minimo, categoria_id, activo, fecha_creacion) 
            VALUES (?, ?, ?, ?, 1, 1, 1, NOW())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $producto['codigo_detectado'],
            $producto['descripcion_detectada'],
            $producto['precio_detectado'],
            $producto['cantidad_detectada']
        ]);

        return $this->db->lastInsertId();
    }

    public function adjustInventoryStock($producto)
    {
        // Buscar producto existente
        $existing = $this->getProductCurrentStatus($producto);

        if ($existing['exists']) {
            $query = "UPDATE productos SET stock_actual = ? WHERE codigo = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$producto['cantidad_detectada'], $producto['codigo_detectado']]);

            // Log del ajuste
            $this->logInventoryMovement(
                $existing['id'],
                $producto['cantidad_detectada'] - $existing['stock'],
                'ajuste_inventario',
                "Ajuste por carga inicial OCR"
            );
        }
    }
}
