<?php
// modulos/compras/ocr_remitos/human_validation_system.php

class HumanValidationSystem
{
    private $db;
    private $confidence_threshold = 0.98;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function processOCRResult($ocr_result, $proveedor_id)
    {
        $validation_queue = [];
        $auto_processed = [];
        $critical_errors = [];

        foreach ($ocr_result['validated_products'] as $product) {
            $decision = $this->makeProcessingDecision($product, $proveedor_id);

            switch ($decision['action']) {
                case 'auto_process':
                    $auto_processed[] = $this->autoProcessProduct($product, $proveedor_id);
                    break;

                case 'human_validation':
                    $validation_queue[] = $this->addToValidationQueue($product, $proveedor_id, $decision['reason']);
                    break;

                case 'critical_review':
                    $critical_errors[] = $this->flagForCriticalReview($product, $proveedor_id, $decision['reason']);
                    break;
            }
        }

        // Crear reporte de procesamiento
        $processing_report = $this->createProcessingReport([
            'auto_processed' => $auto_processed,
            'validation_queue' => $validation_queue,
            'critical_errors' => $critical_errors,
            'ocr_confidence' => $ocr_result['final_confidence'],
            'proveedor_id' => $proveedor_id
        ]);

        return $processing_report;
    }

    private function makeProcessingDecision($product, $proveedor_id)
    {
        // DECISION TREE INTELIGENTE

        // 1. Check confidence level
        if ($product['confidence'] >= 0.98) {
            // Ultra high confidence - check for exact match
            $existing_product = $this->findExactProductMatch($product, $proveedor_id);
            if ($existing_product) {
                return [
                    'action' => 'auto_process',
                    'reason' => 'high_confidence_exact_match',
                    'confidence' => $product['confidence']
                ];
            }
        }

        if ($product['confidence'] >= 0.95) {
            // High confidence - check for fuzzy match
            $similar_products = $this->findSimilarProducts($product, $proveedor_id);
            if (count($similar_products) === 1 && $similar_products[0]['similarity'] > 0.9) {
                return [
                    'action' => 'auto_process',
                    'reason' => 'high_confidence_fuzzy_match',
                    'confidence' => $product['confidence'],
                    'matched_product' => $similar_products[0]
                ];
            } elseif (count($similar_products) > 1) {
                return [
                    'action' => 'human_validation',
                    'reason' => 'multiple_matches_found',
                    'similar_products' => $similar_products
                ];
            }
        }

        if ($product['confidence'] >= 0.80) {
            // Medium confidence - requires human validation
            return [
                'action' => 'human_validation',
                'reason' => 'medium_confidence_requires_validation',
                'confidence' => $product['confidence']
            ];
        }

        // Low confidence - critical review needed
        return [
            'action' => 'critical_review',
            'reason' => 'low_confidence_critical_review',
            'confidence' => $product['confidence']
        ];
    }

    private function findExactProductMatch($product, $proveedor_id)
    {
        $query = "
            SELECT p.*, pp.precio_proveedor, pp.codigo_proveedor 
            FROM productos p 
            LEFT JOIN productos_proveedores pp ON p.id = pp.producto_id AND pp.proveedor_id = ?
            WHERE (p.codigo = ? OR pp.codigo_proveedor = ?)
            AND p.activo = 1
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$proveedor_id, $product['codigo'], $product['codigo']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function findSimilarProducts($product, $proveedor_id)
    {
        // Búsqueda inteligente por similitud
        $query = "
            SELECT p.*, pp.precio_proveedor, pp.codigo_proveedor,
                   MATCH(p.descripcion) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
            FROM productos p 
            LEFT JOIN productos_proveedores pp ON p.id = pp.producto_id AND pp.proveedor_id = ?
            WHERE p.activo = 1
            AND (
                SOUNDEX(p.codigo) = SOUNDEX(?) OR
                SOUNDEX(p.descripcion) = SOUNDEX(?) OR
                MATCH(p.descripcion) AGAINST(? IN NATURAL LANGUAGE MODE) > 0.5 OR
                p.descripcion LIKE CONCAT('%', ?, '%')
            )
            ORDER BY relevance_score DESC, p.codigo
            LIMIT 10
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $product['descripcion'],
            $proveedor_id,
            $product['codigo'],
            $product['descripcion'],
            $product['descripcion'],
            $product['descripcion']
        ]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular similitud más precisa
        foreach ($results as &$result) {
            $result['similarity'] = $this->calculateDetailedSimilarity($product, $result);
        }

        // Filtrar por similitud mínima
        return array_filter($results, function ($r) {
            return $r['similarity'] > 0.6;
        });
    }

    private function calculateDetailedSimilarity($ocr_product, $db_product)
    {
        $code_sim = $this->stringSimilarity($ocr_product['codigo'], $db_product['codigo']);
        $desc_sim = $this->stringSimilarity($ocr_product['descripcion'], $db_product['descripcion']);

        // Peso mayor a la descripción para productos
        return ($code_sim * 0.3 + $desc_sim * 0.7);
    }

    private function stringSimilarity($str1, $str2)
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) return 1.0;

        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) return 1.0;

        return 1 - (levenshtein($str1, $str2) / $maxLen);
    }

    private function autoProcessProduct($product, $proveedor_id)
    {
        // Actualizar inventario automáticamente
        $existing_product = $this->findExactProductMatch($product, $proveedor_id);

        if ($existing_product) {
            // Actualizar stock existente
            $this->updateProductStock($existing_product['id'], $product['cantidad']);

            return [
                'action' => 'stock_updated',
                'producto_id' => $existing_product['id'],
                'cantidad_agregada' => $product['cantidad'],
                'confidence' => $product['confidence'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            // Crear nuevo producto pendiente de aprobación
            return $this->createPendingProduct($product, $proveedor_id);
        }
    }

    private function updateProductStock($producto_id, $cantidad)
    {
        $query = "UPDATE productos SET stock = stock + ?, fecha_actualizacion = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$cantidad, $producto_id]);

        // Log de movimiento
        $this->logInventoryMovement($producto_id, $cantidad, 'ocr_auto_entry');
    }

    private function logInventoryMovement($producto_id, $cantidad, $tipo)
    {
        $query = "
            INSERT INTO movimientos_inventario 
            (producto_id, tipo_movimiento, cantidad, motivo, fecha_movimiento, usuario_id) 
            VALUES (?, ?, ?, ?, NOW(), ?)
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$producto_id, 'entrada', $cantidad, $tipo, 0]); // 0 = sistema automatico
    }

    private function createPendingProduct($product, $proveedor_id)
    {
        $query = "
            INSERT INTO productos_pendientes_revision 
            (codigo_remito, descripcion_remito, cantidad, precio, proveedor_id, 
             categoria_sugerida, atributos_extraidos, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')
        ";

        $categoria_sugerida = $this->suggestCategory($product);
        $atributos = json_encode([
            'confidence' => $product['confidence'],
            'detected_by_engines' => $product['detected_by_engines'] ?? [],
            'processing_timestamp' => date('Y-m-d H:i:s')
        ]);

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $product['codigo'],
            $product['descripcion'],
            $product['cantidad'],
            $product['precio'],
            $proveedor_id,
            $categoria_sugerida,
            $atributos
        ]);

        return [
            'action' => 'pending_product_created',
            'pending_id' => $this->db->lastInsertId(),
            'confidence' => $product['confidence']
        ];
    }

    private function suggestCategory($product)
    {
        // Sistema de sugerencia de categorías basado en palabras clave
        $keywords_categories = [
            'Tornillos' => ['tornillo', 'screw', 'phillips', 'cabeza', 'hexagonal'],
            'Tuercas' => ['tuerca', 'nut', 'hexagonal', 'mariposa'],
            'Herramientas' => ['martillo', 'destornillador', 'llave', 'alicate'],
            'Electricidad' => ['cable', 'conector', 'fusible', 'interruptor', 'voltaje'],
            'Pintura' => ['pintura', 'barniz', 'esmalte', 'primer', 'color'],
            'Fontanería' => ['tuberia', 'codo', 'valvula', 'grifo', 'junta']
        ];

        $description = strtolower($product['descripcion']);

        foreach ($keywords_categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($description, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'General';
    }

    private function addToValidationQueue($product, $proveedor_id, $reason)
    {
        $similar_products = $this->findSimilarProducts($product, $proveedor_id);

        $queue_data = [
            'codigo_remito' => $product['codigo'],
            'descripcion_remito' => $product['descripcion'],
            'cantidad' => $product['cantidad'],
            'precio' => $product['precio'],
            'proveedor_id' => $proveedor_id,
            'validation_reason' => $reason,
            'confidence' => $product['confidence'],
            'similar_products' => json_encode($similar_products),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Insertar en cola de validación
        $query = "
            INSERT INTO ocr_validation_queue 
            (codigo_remito, descripcion_remito, cantidad, precio, proveedor_id, 
             validation_reason, confidence_score, similar_products_json, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $queue_data['codigo_remito'],
            $queue_data['descripcion_remito'],
            $queue_data['cantidad'],
            $queue_data['precio'],
            $queue_data['proveedor_id'],
            $queue_data['validation_reason'],
            $queue_data['confidence'],
            $queue_data['similar_products']
        ]);

        return [
            'queue_id' => $this->db->lastInsertId(),
            'action' => 'added_to_validation_queue',
            'reason' => $reason,
            'similar_count' => count($similar_products)
        ];
    }

    private function flagForCriticalReview($product, $proveedor_id, $reason)
    {
        // Productos que requieren revisión crítica
        $query = "
            INSERT INTO ocr_critical_review 
            (codigo_remito, descripcion_remito, cantidad, precio, proveedor_id, 
             critical_reason, confidence_score, review_priority, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'critical')
        ";

        $priority = $this->calculateReviewPriority($product, $reason);

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $product['codigo'],
            $product['descripcion'],
            $product['cantidad'],
            $product['precio'],
            $proveedor_id,
            $reason,
            $product['confidence'],
            $priority
        ]);

        return [
            'critical_id' => $this->db->lastInsertId(),
            'action' => 'flagged_critical_review',
            'reason' => $reason,
            'priority' => $priority
        ];
    }

    private function calculateReviewPriority($product, $reason)
    {
        $priority = 'medium';

        // Alta prioridad para productos caros o grandes cantidades
        if ($product['precio'] > 1000 || $product['cantidad'] > 100) {
            $priority = 'high';
        }

        // Prioridad crítica para confianza muy baja
        if ($product['confidence'] < 0.5) {
            $priority = 'critical';
        }

        return $priority;
    }

    private function createProcessingReport($data)
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'proveedor_id' => $data['proveedor_id'],
            'ocr_confidence' => $data['ocr_confidence'],
            'summary' => [
                'auto_processed' => count($data['auto_processed']),
                'validation_queue' => count($data['validation_queue']),
                'critical_errors' => count($data['critical_errors']),
                'total_products' => count($data['auto_processed']) + count($data['validation_queue']) + count($data['critical_errors'])
            ],
            'details' => $data,
            'next_actions' => $this->generateNextActions($data)
        ];

        // Guardar reporte en la base de datos
        $this->saveProcessingReport($report);

        return $report;
    }

    private function generateNextActions($data)
    {
        $actions = [];

        if (count($data['validation_queue']) > 0) {
            $actions[] = "Revisar " . count($data['validation_queue']) . " productos en cola de validación";
        }

        if (count($data['critical_errors']) > 0) {
            $actions[] = "URGENTE: Revisar " . count($data['critical_errors']) . " productos con errores críticos";
        }

        if (count($data['auto_processed']) > 0) {
            $actions[] = "Verificar " . count($data['auto_processed']) . " productos procesados automáticamente";
        }

        return $actions;
    }

    private function saveProcessingReport($report)
    {
        $query = "
            INSERT INTO ocr_processing_reports 
            (proveedor_id, total_products, auto_processed, validation_queue, 
             critical_errors, ocr_confidence, report_data, fecha_procesamiento) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $report['proveedor_id'],
            $report['summary']['total_products'],
            $report['summary']['auto_processed'],
            $report['summary']['validation_queue'],
            $report['summary']['critical_errors'],
            $report['ocr_confidence'],
            json_encode($report)
        ]);
    }

    // Sistema de aprendizaje automático
    public function learnFromValidation($validation_id, $user_decision, $user_id)
    {
        // Obtener datos de la validación
        $validation = $this->getValidationData($validation_id);

        // Registrar la decisión del usuario
        $learning_data = [
            'original_confidence' => $validation['confidence_score'],
            'user_decision' => $user_decision, // 'approve', 'reject', 'modify'
            'validation_reason' => $validation['validation_reason'],
            'producto_codigo' => $validation['codigo_remito'],
            'user_id' => $user_id,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Actualizar modelo de aprendizaje
        $this->updateLearningModel($learning_data);

        // Ajustar umbrales si es necesario
        $this->adjustConfidenceThresholds($learning_data);
    }

    private function updateLearningModel($learning_data)
    {
        $query = "
            INSERT INTO ocr_learning_data 
            (original_confidence, user_decision, validation_reason, producto_codigo, 
             user_id, learning_timestamp) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $learning_data['original_confidence'],
            $learning_data['user_decision'],
            $learning_data['validation_reason'],
            $learning_data['producto_codigo'],
            $learning_data['user_id']
        ]);
    }

    private function adjustConfidenceThresholds($learning_data)
    {
        // Análisis de decisiones recientes para ajustar umbrales
        $query = "
            SELECT 
                AVG(original_confidence) as avg_confidence,
                user_decision,
                COUNT(*) as decision_count
            FROM ocr_learning_data 
            WHERE learning_timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY user_decision
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $decisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lógica de ajuste inteligente
        foreach ($decisions as $decision) {
            if ($decision['user_decision'] === 'approve' && $decision['avg_confidence'] < $this->confidence_threshold) {
                // Los usuarios están aprobando cosas con confianza menor al umbral
                // Considerar reducir el umbral ligeramente
                $this->updateThreshold('auto_process', max(0.85, $this->confidence_threshold - 0.02));
            }

            if ($decision['user_decision'] === 'reject' && $decision['avg_confidence'] > 0.9) {
                // Los usuarios están rechazando cosas con alta confianza
                // Aumentar el umbral
                $this->updateThreshold('auto_process', min(0.99, $this->confidence_threshold + 0.01));
            }
        }
    }

    private function updateThreshold($threshold_type, $new_value)
    {
        $query = "
            INSERT INTO ocr_threshold_history 
            (threshold_type, old_value, new_value, adjustment_reason, updated_at) 
            VALUES (?, ?, ?, 'automatic_learning', NOW())
            ON DUPLICATE KEY UPDATE 
            old_value = VALUES(new_value), 
            new_value = VALUES(new_value), 
            updated_at = NOW()
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$threshold_type, $this->confidence_threshold, $new_value]);

        $this->confidence_threshold = $new_value;
    }

    private function getValidationData($validation_id)
    {
        $query = "SELECT * FROM ocr_validation_queue WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$validation_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
