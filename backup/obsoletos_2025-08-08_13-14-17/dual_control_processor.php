<?php
// modulos/compras/ocr_remitos/dual_control_processor.php
require_once 'dual_control_helpers.php';

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

    /**
     * Procesa documentos para COMPRAS con doble control
     */
    public function processCompraDocument($image_path, $proveedor_id, $tipo_documento = 'remito')
    {
        echo "🛒 PROCESANDO DOCUMENTO DE COMPRA\n";
        echo "================================\n";

        // PASO 1: Extraer datos del documento del proveedor
        $documento_proveedor = $this->extractDocumentData($image_path, $tipo_documento);
        echo "📄 Documento del proveedor procesado: {$documento_proveedor['productos_detectados']} productos\n";

        // PASO 2: Generar documento de control interno
        $documento_control = $this->generateControlDocument($documento_proveedor, $proveedor_id);
        echo "📋 Documento de control generado con ID: {$documento_control['control_id']}\n";

        // PASO 3: Crear registro de comparación para doble control
        $comparison_id = $this->createComparisonRecord($documento_proveedor, $documento_control, $proveedor_id, 'compra');

        return [
            'status' => 'pending_double_control',
            'documento_proveedor' => $documento_proveedor,
            'documento_control' => $documento_control,
            'comparison_id' => $comparison_id,
            'next_step' => 'operator_comparison',
            'instructions' => 'El operador debe comparar ambos documentos antes de aprobar el ingreso'
        ];
    }

    /**
     * Procesa documentos para INVENTARIO INICIAL con doble control
     */
    public function processInventarioDocument($image_path, $categoria = null, $ubicacion = null)
    {
        echo "📦 PROCESANDO DOCUMENTO DE INVENTARIO INICIAL\n";
        echo "=============================================\n";

        // PASO 1: Extraer datos del documento de inventario
        $documento_inventario = $this->extractInventoryData($image_path);
        echo "📄 Documento de inventario procesado: {$documento_inventario['productos_detectados']} productos\n";

        // PASO 2: Comparar con inventario actual
        $comparacion_actual = $this->compareWithCurrentInventory($documento_inventario['productos']);
        echo "🔍 Comparación completada: {$comparacion_actual['nuevos']} nuevos, {$comparacion_actual['existentes']} existentes\n";

        // PASO 3: Generar documento de control para inventario
        $documento_control = $this->generateInventoryControlDocument($documento_inventario, $comparacion_actual);

        // PASO 4: Crear registro de comparación
        $comparison_id = $this->createComparisonRecord($documento_inventario, $documento_control, null, 'inventario_inicial');

        return [
            'status' => 'pending_inventory_review',
            'documento_original' => $documento_inventario,
            'documento_control' => $documento_control,
            'comparacion_actual' => $comparacion_actual,
            'comparison_id' => $comparison_id,
            'next_step' => 'supervisor_approval',
            'instructions' => 'El supervisor debe revisar los cambios antes de aplicar al inventario'
        ];
    }

    private function extractDocumentData($image_path, $tipo_documento)
    {
        // Usar el OCR processor existente
        $ocr_result = $this->ocr_processor->processImage($image_path);

        // Analizar específicamente según el tipo de documento
        $productos = [];
        switch ($tipo_documento) {
            case 'remito':
                $productos = $this->parseRemito($ocr_result['text']);
                break;
            case 'factura':
                $productos = $this->parseFactura($ocr_result['text']);
                break;
            case 'lista_precios':
                $productos = $this->parseListaPrecios($ocr_result['text']);
                break;
            default:
                $productos = $this->parseGeneric($ocr_result['text']);
        }

        return [
            'documento_id' => uniqid('DOC_PROV_'),
            'tipo_documento' => $tipo_documento,
            'fecha_procesamiento' => date('Y-m-d H:i:s'),
            'confidence_ocr' => $ocr_result['confidence'],
            'texto_completo' => $ocr_result['text'],
            'productos' => $productos,
            'productos_detectados' => count($productos),
            'archivo_original' => basename($image_path)
        ];
    }

    private function extractInventoryData($image_path)
    {
        $ocr_result = $this->ocr_processor->processImage($image_path);

        // Parser específico para documentos de inventario
        $productos = $this->parseInventoryDocument($ocr_result['text']);

        return [
            'documento_id' => uniqid('DOC_INV_'),
            'tipo_documento' => 'inventario_inicial',
            'fecha_procesamiento' => date('Y-m-d H:i:s'),
            'confidence_ocr' => $ocr_result['confidence'],
            'texto_completo' => $ocr_result['text'],
            'productos' => $productos,
            'productos_detectados' => count($productos),
            'archivo_original' => basename($image_path)
        ];
    }

    private function generateControlDocument($documento_proveedor, $proveedor_id)
    {
        $control_id = uniqid('CTRL_COMPRA_');

        // Obtener información del proveedor
        $proveedor = $this->getProveedorInfo($proveedor_id);

        // Generar productos de control con matching inteligente
        $productos_control = [];
        foreach ($documento_proveedor['productos'] as $producto_proveedor) {
            $matches = $this->product_matcher->findMatches($producto_proveedor);

            $producto_control = [
                'codigo_proveedor' => $producto_proveedor['codigo'],
                'descripcion_proveedor' => $producto_proveedor['descripcion'],
                'cantidad_proveedor' => $producto_proveedor['cantidad'],
                'precio_proveedor' => $producto_proveedor['precio'] ?? 0,
                'producto_matching' => $matches,
                'accion_recomendada' => $this->determineAction($producto_proveedor, $matches),
                'confidence_matching' => $matches['best_match']['similarity'] ?? 0
            ];

            $productos_control[] = $producto_control;
        }

        // Crear documento HTML para impresión
        $html_content = $this->generateControlDocumentHTML($control_id, $proveedor, $productos_control);

        // Guardar en base de datos
        $this->saveControlDocument($control_id, $documento_proveedor['documento_id'], $productos_control, $html_content);

        return [
            'control_id' => $control_id,
            'proveedor' => $proveedor,
            'productos_control' => $productos_control,
            'html_content' => $html_content,
            'fecha_generacion' => date('Y-m-d H:i:s'),
            'estado' => 'generado'
        ];
    }

    private function generateInventoryControlDocument($documento_inventario, $comparacion_actual)
    {
        $control_id = uniqid('CTRL_INV_');

        // Generar productos de control para inventario
        $productos_control = [];
        foreach ($documento_inventario['productos'] as $producto_inventario) {
            $estado_actual = $this->getProductCurrentStatus($producto_inventario);

            $producto_control = [
                'codigo_detectado' => $producto_inventario['codigo'],
                'descripcion_detectada' => $producto_inventario['descripcion'],
                'cantidad_detectada' => $producto_inventario['cantidad'],
                'precio_detectado' => $producto_inventario['precio'] ?? 0,
                'estado_actual' => $estado_actual,
                'accion_recomendada' => $this->determineInventoryAction($producto_inventario, $estado_actual),
                'discrepancia' => $this->calculateDiscrepancy($producto_inventario, $estado_actual)
            ];

            $productos_control[] = $producto_control;
        }

        // Crear documento HTML para revisión
        $html_content = $this->generateInventoryControlDocumentHTML($control_id, $productos_control);

        // Guardar en base de datos
        $this->saveInventoryControlDocument($control_id, $documento_inventario['documento_id'], $productos_control, $html_content);

        return [
            'control_id' => $control_id,
            'productos_control' => $productos_control,
            'html_content' => $html_content,
            'fecha_generacion' => date('Y-m-d H:i:s'),
            'estado' => 'pendiente_revision'
        ];
    }

    private function generateControlDocumentHTML($control_id, $proveedor, $productos_control)
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Documento de Control - {$control_id}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background: #007bff; color: white; padding: 15px; margin-bottom: 20px; }
                .product-row { border-bottom: 1px solid #ddd; padding: 10px 0; }
                .action-new { background: #d4edda; }
                .action-update { background: #fff3cd; }
                .action-conflict { background: #f8d7da; }
                .checkbox { margin-right: 10px; }
                .signature-area { margin-top: 50px; border-top: 1px solid #333; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>DOCUMENTO DE CONTROL DE COMPRA</h1>
                <p>Control ID: {$control_id}</p>
                <p>Proveedor: {$proveedor['nombre']}</p>
                <p>Fecha: " . date('d/m/Y H:i') . "</p>
            </div>
            
            <h2>PRODUCTOS A VERIFICAR</h2>
            <p><strong>Instrucciones:</strong> Compare cada producto del remito del proveedor con este documento de control. Marque ✓ si coincide, ✗ si hay diferencias.</p>
            
            <table border='1' cellpadding='5' cellspacing='0' width='100%'>
                <thead>
                    <tr>
                        <th>✓/✗</th>
                        <th>Código Proveedor</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Acción</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($productos_control as $index => $producto) {
            $action_class = $this->getActionClass($producto['accion_recomendada']);
            $html .= "
                <tr class='{$action_class}'>
                    <td class='checkbox'>☐</td>
                    <td>{$producto['codigo_proveedor']}</td>
                    <td>{$producto['descripcion_proveedor']}</td>
                    <td>{$producto['cantidad_proveedor']}</td>
                    <td>\${$producto['precio_proveedor']}</td>
                    <td>{$producto['accion_recomendada']}</td>
                    <td style='width: 150px; border-bottom: 1px solid #999;'>&nbsp;</td>
                </tr>";
        }

        $html .= "
                </tbody>
            </table>
            
            <div class='signature-area'>
                <div style='float: left; width: 45%;'>
                    <h3>OPERARIO DE RECEPCIÓN</h3>
                    <p>Nombre: _________________________</p>
                    <p>Firma: __________________________</p>
                    <p>Fecha: __________________________</p>
                </div>
                <div style='float: right; width: 45%;'>
                    <h3>SUPERVISOR</h3>
                    <p>Nombre: _________________________</p>
                    <p>Firma: __________________________</p>
                    <p>Fecha: __________________________</p>
                </div>
                <div style='clear: both;'></div>
            </div>
            
            <div style='margin-top: 30px; border: 2px solid #dc3545; padding: 15px; background: #f8f9fa;'>
                <h3>IMPORTANTE - PROCESO DE DOBLE CONTROL</h3>
                <ol>
                    <li>Compare físicamente cada producto con el remito del proveedor</li>
                    <li>Verifique códigos, descripciones, cantidades y precios</li>
                    <li>Marque cualquier discrepancia en Observaciones</li>
                    <li>Solo proceda al ingreso cuando AMBOS documentos coincidan</li>
                    <li>En caso de diferencias, consulte con el supervisor</li>
                </ol>
            </div>
        </body>
        </html>";

        return $html;
    }

    private function generateInventoryControlDocumentHTML($control_id, $productos_control)
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Control de Inventario - {$control_id}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background: #28a745; color: white; padding: 15px; margin-bottom: 20px; }
                .product-row { border-bottom: 1px solid #ddd; padding: 10px 0; }
                .discrepancy-high { background: #f8d7da; }
                .discrepancy-medium { background: #fff3cd; }
                .discrepancy-low { background: #d4edda; }
                .checkbox { margin-right: 10px; }
                .signature-area { margin-top: 50px; border-top: 1px solid #333; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>DOCUMENTO DE CONTROL DE INVENTARIO INICIAL</h1>
                <p>Control ID: {$control_id}</p>
                <p>Fecha: " . date('d/m/Y H:i') . "</p>
            </div>
            
            <h2>PRODUCTOS DETECTADOS PARA REVISIÓN</h2>
            <p><strong>Instrucciones:</strong> Revise cada producto detectado y compare con el inventario físico actual.</p>
            
            <table border='1' cellpadding='5' cellspacing='0' width='100%'>
                <thead>
                    <tr>
                        <th>✓/✗</th>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Cantidad Detectada</th>
                        <th>Stock Actual</th>
                        <th>Diferencia</th>
                        <th>Acción</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($productos_control as $producto) {
            $discrepancy_class = $this->getDiscrepancyClass($producto['discrepancia']);
            $stock_actual = $producto['estado_actual']['stock'] ?? 0;
            $diferencia = $producto['cantidad_detectada'] - $stock_actual;

            $html .= "
                <tr class='{$discrepancy_class}'>
                    <td class='checkbox'>☐</td>
                    <td>{$producto['codigo_detectado']}</td>
                    <td>{$producto['descripcion_detectada']}</td>
                    <td>{$producto['cantidad_detectada']}</td>
                    <td>{$stock_actual}</td>
                    <td>" . ($diferencia > 0 ? '+' : '') . "{$diferencia}</td>
                    <td>{$producto['accion_recomendada']}</td>
                    <td style='width: 150px; border-bottom: 1px solid #999;'>&nbsp;</td>
                </tr>";
        }

        $html .= "
                </tbody>
            </table>
            
            <div class='signature-area'>
                <div style='float: left; width: 45%;'>
                    <h3>RESPONSABLE DE INVENTARIO</h3>
                    <p>Nombre: _________________________</p>
                    <p>Firma: __________________________</p>
                    <p>Fecha: __________________________</p>
                </div>
                <div style='float: right; width: 45%;'>
                    <h3>SUPERVISOR GENERAL</h3>
                    <p>Nombre: _________________________</p>
                    <p>Firma: __________________________</p>
                    <p>Fecha: __________________________</p>
                </div>
                <div style='clear: both;'></div>
            </div>
        </body>
        </html>";

        return $html;
    }

    public function approveDoubleControl($comparison_id, $operario_id, $supervisor_id, $observaciones = '')
    {
        echo "✅ APROBANDO DOBLE CONTROL\n";
        echo "==========================\n";

        // Obtener datos de comparación
        $comparison = $this->getComparisonData($comparison_id);

        if (!$comparison) {
            throw new Exception("Comparación no encontrada: {$comparison_id}");
        }

        // Verificar que ambos documentos estén aprobados
        if ($comparison['status'] !== 'pending_approval') {
            throw new Exception("La comparación no está lista para aprobación");
        }

        try {
            $this->db->beginTransaction();

            if ($comparison['tipo'] === 'compra') {
                $result = $this->processApprovedCompra($comparison, $operario_id, $supervisor_id);
            } else {
                $result = $this->processApprovedInventario($comparison, $operario_id, $supervisor_id);
            }

            // Actualizar estado de comparación
            $this->updateComparisonStatus($comparison_id, 'approved', $operario_id, $supervisor_id, $observaciones);

            $this->db->commit();

            echo "✅ Doble control aprobado exitosamente\n";
            echo "📦 Productos procesados: {$result['productos_procesados']}\n";
            echo "💰 Valor total: \${$result['valor_total']}\n";

            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error al aprobar doble control: " . $e->getMessage());
        }
    }

    private function processApprovedCompra($comparison, $operario_id, $supervisor_id)
    {
        $productos_procesados = 0;
        $valor_total = 0;

        foreach ($comparison['productos_control'] as $producto) {
            if ($producto['accion_recomendada'] === 'crear_nuevo') {
                $this->createNewProduct($producto, $comparison['proveedor_id']);
            } elseif ($producto['accion_recomendada'] === 'actualizar_stock') {
                $this->updateProductStock($producto['producto_matching']['best_match']['id'], $producto['cantidad_proveedor']);
            }

            // Registrar movimiento de inventario
            $this->logInventoryMovement(
                $producto['producto_matching']['best_match']['id'] ?? null,
                $producto['cantidad_proveedor'],
                'entrada_compra',
                "Doble control aprobado - Control ID: {$comparison['control_id']}"
            );

            $productos_procesados++;
            $valor_total += $producto['precio_proveedor'] * $producto['cantidad_proveedor'];
        }

        return [
            'productos_procesados' => $productos_procesados,
            'valor_total' => $valor_total,
            'tipo' => 'compra'
        ];
    }

    private function processApprovedInventario($comparison, $operario_id, $supervisor_id)
    {
        $productos_procesados = 0;
        $valor_total = 0;

        foreach ($comparison['productos_control'] as $producto) {
            if ($producto['accion_recomendada'] === 'crear_nuevo') {
                $this->createNewInventoryProduct($producto);
            } elseif ($producto['accion_recomendada'] === 'ajustar_stock') {
                $this->adjustInventoryStock($producto);
            }

            $productos_procesados++;
            $valor_total += $producto['precio_detectado'] * $producto['cantidad_detectada'];
        }

        return [
            'productos_procesados' => $productos_procesados,
            'valor_total' => $valor_total,
            'tipo' => 'inventario_inicial'
        ];
    }

    // Métodos auxiliares
    private function parseRemito($text)
    {
        // Implementar parser específico para remitos
        return $this->parseGeneric($text);
    }

    private function parseFactura($text)
    {
        // Implementar parser específico para facturas
        return $this->parseGeneric($text);
    }

    private function parseListaPrecios($text)
    {
        // Implementar parser específico para listas de precios
        return $this->parseGeneric($text);
    }

    private function parseInventoryDocument($text)
    {
        // Implementar parser específico para documentos de inventario
        return $this->parseGeneric($text);
    }

    private function parseGeneric($text)
    {
        // Parser genérico usando el sistema existente
        $lines = explode("\n", $text);
        $productos = [];

        foreach ($lines as $line) {
            if (preg_match('/^([A-Z0-9\-]{3,})\s+(.+?)\s+(\d+[\.\,]?\d*)\s+[\$]?(\d+[\.\,]?\d*)/', trim($line), $matches)) {
                $productos[] = [
                    'codigo' => $matches[1],
                    'descripcion' => trim($matches[2]),
                    'cantidad' => (float) str_replace(',', '.', $matches[3]),
                    'precio' => (float) str_replace(',', '.', $matches[4])
                ];
            }
        }

        return $productos;
    }

    private function getProveedorInfo($proveedor_id)
    {
        $query = "SELECT * FROM proveedores WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$proveedor_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function determineAction($producto_proveedor, $matches)
    {
        if (empty($matches['matches'])) {
            return 'crear_nuevo';
        }

        $best_match = $matches['best_match'];
        if ($best_match['similarity'] > 0.9) {
            return 'actualizar_stock';
        } elseif ($best_match['similarity'] > 0.7) {
            return 'revisar_manual';
        } else {
            return 'crear_nuevo';
        }
    }

    private function getActionClass($action)
    {
        switch ($action) {
            case 'crear_nuevo':
                return 'action-new';
            case 'actualizar_stock':
                return 'action-update';
            case 'revisar_manual':
                return 'action-conflict';
            default:
                return '';
        }
    }

    // Métodos para guardar en base de datos
    private function saveControlDocument($control_id, $documento_id, $productos_control, $html_content)
    {
        $query = "
            INSERT INTO ocr_control_documents 
            (control_id, documento_original_id, productos_control, html_content, tipo, estado, fecha_generacion) 
            VALUES (?, ?, ?, ?, 'compra', 'generado', NOW())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $control_id,
            $documento_id,
            json_encode($productos_control),
            $html_content
        ]);
    }

    private function createComparisonRecord($documento_original, $documento_control, $proveedor_id, $tipo)
    {
        $comparison_id = uniqid('CMP_');

        $query = "
            INSERT INTO ocr_document_comparisons 
            (comparison_id, documento_original, documento_control, proveedor_id, tipo, status, fecha_creacion) 
            VALUES (?, ?, ?, ?, ?, 'pending_approval', NOW())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $comparison_id,
            json_encode($documento_original),
            json_encode($documento_control),
            $proveedor_id,
            $tipo
        ]);

        return $comparison_id;
    }


    // Métodos auxiliares para inventario
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

    // Métodos auxiliares restantes
    private function compareWithCurrentInventory($productos)
    {
        $nuevos = 0;
        $existentes = 0;

        foreach ($productos as $producto) {
            $existing = $this->findExistingProduct($producto['codigo']);
            if ($existing) {
                $existentes++;
            } else {
                $nuevos++;
            }
        }

        return [
            'nuevos' => $nuevos,
            'existentes' => $existentes,
            'total' => count($productos)
        ];
    }

    private function findExistingProduct($codigo)
    {
        $query = "SELECT * FROM productos WHERE codigo = ? AND activo = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
