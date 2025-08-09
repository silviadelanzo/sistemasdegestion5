-- modulos/compras/ocr_remitos/dual_control_database.sql
-- Tablas necesarias para el sistema de doble control OCR

-- Tabla para documentos de control generados
CREATE TABLE IF NOT EXISTS ocr_control_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    control_id VARCHAR(50) UNIQUE NOT NULL,
    documento_original_id VARCHAR(50) NOT NULL,
    productos_control LONGTEXT NOT NULL, -- JSON con productos y acciones
    html_content LONGTEXT NOT NULL, -- HTML del documento imprimible
    tipo ENUM('compra', 'inventario_inicial') NOT NULL,
    estado ENUM('generado', 'impreso', 'aprobado', 'rechazado') DEFAULT 'generado',
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_impresion TIMESTAMP NULL,
    INDEX idx_control_id (control_id),
    INDEX idx_tipo (tipo),
    INDEX idx_estado (estado)
);

-- Tabla para comparaciones de documentos (doble control)
CREATE TABLE IF NOT EXISTS ocr_document_comparisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comparison_id VARCHAR(50) UNIQUE NOT NULL,
    documento_original LONGTEXT NOT NULL, -- JSON con datos del documento original
    documento_control LONGTEXT NOT NULL, -- JSON con datos del documento de control
    proveedor_id INT NULL, -- Solo para compras
    categoria_id INT NULL, -- Solo para inventario
    tipo ENUM('compra', 'inventario_inicial') NOT NULL,
    status ENUM('pending_approval', 'approved', 'rejected') DEFAULT 'pending_approval',
    operario_id INT NULL,
    supervisor_id INT NULL,
    observaciones TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion TIMESTAMP NULL,
    INDEX idx_comparison_id (comparison_id),
    INDEX idx_status (status),
    INDEX idx_tipo (tipo),
    INDEX idx_proveedor (proveedor_id),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    FOREIGN KEY (operario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (supervisor_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla para movimientos de inventario (si no existe)
CREATE TABLE IF NOT EXISTS movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    tipo_movimiento ENUM('entrada_compra', 'salida_venta', 'ajuste_inventario', 'transferencia') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    precio_unitario DECIMAL(10,2) NULL,
    observaciones TEXT NULL,
    documento_referencia VARCHAR(100) NULL, -- Control ID, factura, etc.
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NULL,
    INDEX idx_producto (producto_id),
    INDEX idx_tipo (tipo_movimiento),
    INDEX idx_fecha (fecha_movimiento),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla para archivos OCR procesados
CREATE TABLE IF NOT EXISTS ocr_processed_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    tipo_documento ENUM('remito', 'factura', 'lista_precios', 'inventario') NOT NULL,
    ocr_engine VARCHAR(50) NOT NULL DEFAULT 'tesseract',
    confidence_score DECIMAL(5,2) NULL,
    texto_extraido LONGTEXT NULL,
    productos_detectados INT DEFAULT 0,
    status ENUM('procesando', 'completado', 'error') DEFAULT 'procesando',
    error_message TEXT NULL,
    usuario_id INT NULL,
    fecha_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_procesamiento TIMESTAMP NULL,
    INDEX idx_filename (filename),
    INDEX idx_tipo (tipo_documento),
    INDEX idx_status (status),
    INDEX idx_fecha_upload (fecha_upload),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla para estadísticas de precisión OCR
CREATE TABLE IF NOT EXISTS ocr_precision_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comparison_id VARCHAR(50) NOT NULL,
    productos_totales INT NOT NULL,
    productos_exactos INT DEFAULT 0, -- Matches exactos
    productos_similares INT DEFAULT 0, -- Matches por similitud
    productos_nuevos INT DEFAULT 0, -- Productos no encontrados
    productos_conflictos INT DEFAULT 0, -- Requieren revisión manual
    precision_promedio DECIMAL(5,2) DEFAULT 0,
    tiempo_procesamiento_segundos INT DEFAULT 0,
    fecha_calculo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_comparison (comparison_id),
    INDEX idx_fecha (fecha_calculo),
    FOREIGN KEY (comparison_id) REFERENCES ocr_document_comparisons(comparison_id) ON DELETE CASCADE
);

-- Tabla para aprendizaje del sistema (machine learning básico)
CREATE TABLE IF NOT EXISTS ocr_learning_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto_ocr VARCHAR(500) NOT NULL,
    producto_id INT NOT NULL,
    similarity_score DECIMAL(5,2) NOT NULL,
    fue_correcto BOOLEAN DEFAULT FALSE, -- Confirmado por usuario
    metodo_matching ENUM('exact_code', 'description_similarity', 'manual_selection') NOT NULL,
    fecha_aprendizaje TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_confirmacion INT NULL,
    INDEX idx_producto (producto_id),
    INDEX idx_similarity (similarity_score),
    INDEX idx_correcto (fue_correcto),
    INDEX idx_fecha (fecha_aprendizaje),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_confirmacion) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Vista para reportes de doble control
CREATE OR REPLACE VIEW vista_doble_control_reportes AS
SELECT 
    dc.comparison_id,
    dc.tipo,
    dc.status,
    dc.fecha_creacion,
    dc.fecha_aprobacion,
    CASE 
        WHEN dc.proveedor_id IS NOT NULL THEN p.nombre 
        ELSE 'Inventario General' 
    END as origen,
    JSON_EXTRACT(dc.documento_original, '$.productos_detectados') as productos_detectados,
    CASE 
        WHEN dc.operario_id IS NOT NULL THEN u1.nombre 
        ELSE 'Sin asignar' 
    END as operario,
    CASE 
        WHEN dc.supervisor_id IS NOT NULL THEN u2.nombre 
        ELSE 'Sin asignar' 
    END as supervisor,
    ocd.estado as estado_documento_control,
    ops.precision_promedio,
    ops.tiempo_procesamiento_segundos
FROM ocr_document_comparisons dc
LEFT JOIN proveedores p ON dc.proveedor_id = p.id
LEFT JOIN usuarios u1 ON dc.operario_id = u1.id
LEFT JOIN usuarios u2 ON dc.supervisor_id = u2.id
LEFT JOIN ocr_control_documents ocd ON ocd.control_id = JSON_EXTRACT(dc.documento_control, '$.control_id')
LEFT JOIN ocr_precision_stats ops ON ops.comparison_id = dc.comparison_id;

-- Procedimiento para limpiar datos antiguos
DELIMITER //
CREATE PROCEDURE LimpiarDatosOCRAntiguos(IN dias_antiguedad INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Eliminar archivos procesados antiguos
    DELETE FROM ocr_processed_files 
    WHERE fecha_upload < DATE_SUB(NOW(), INTERVAL dias_antiguedad DAY)
    AND status = 'completado';
    
    -- Eliminar datos de aprendizaje antiguos no confirmados
    DELETE FROM ocr_learning_data 
    WHERE fecha_aprendizaje < DATE_SUB(NOW(), INTERVAL dias_antiguedad DAY)
    AND fue_correcto = FALSE;
    
    -- Eliminar estadísticas de precisión antiguas
    DELETE FROM ocr_precision_stats 
    WHERE fecha_calculo < DATE_SUB(NOW(), INTERVAL dias_antiguedad DAY);
    
    COMMIT;
    
    SELECT CONCAT('Limpieza completada. Datos anteriores a ', dias_antiguedad, ' días eliminados.') as resultado;
END//
DELIMITER ;

-- Trigger para actualizar estadísticas automáticamente
DELIMITER //
CREATE TRIGGER after_comparison_approved
AFTER UPDATE ON ocr_document_comparisons
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
        -- Calcular estadísticas de precisión
        INSERT INTO ocr_precision_stats (
            comparison_id,
            productos_totales,
            productos_exactos,
            productos_similares,
            productos_nuevos,
            precision_promedio
        )
        SELECT 
            NEW.comparison_id,
            JSON_LENGTH(JSON_EXTRACT(NEW.documento_original, '$.productos')) as total,
            COALESCE(
                (SELECT COUNT(*) 
                 FROM JSON_TABLE(JSON_EXTRACT(NEW.documento_control, '$.productos_control'), '$[*]'
                     COLUMNS (accion VARCHAR(50) PATH '$.accion_recomendada')
                 ) jt 
                 WHERE jt.accion = 'actualizar_stock'), 0
            ) as exactos,
            COALESCE(
                (SELECT COUNT(*) 
                 FROM JSON_TABLE(JSON_EXTRACT(NEW.documento_control, '$.productos_control'), '$[*]'
                     COLUMNS (accion VARCHAR(50) PATH '$.accion_recomendada')
                 ) jt 
                 WHERE jt.accion IN ('ajustar_stock', 'revisar_manual')), 0
            ) as similares,
            COALESCE(
                (SELECT COUNT(*) 
                 FROM JSON_TABLE(JSON_EXTRACT(NEW.documento_control, '$.productos_control'), '$[*]'
                     COLUMNS (accion VARCHAR(50) PATH '$.accion_recomendada')
                 ) jt 
                 WHERE jt.accion = 'crear_nuevo'), 0
            ) as nuevos,
            85.0 -- Precisión base estimada
        FROM DUAL;
    END IF;
END//
DELIMITER ;

-- Insertar datos de configuración inicial
INSERT IGNORE INTO configuracion_sistema (clave, valor, descripcion) VALUES
('ocr_enabled', '1', 'Habilitar sistema OCR'),
('ocr_default_engine', 'tesseract', 'Engine OCR por defecto'),
('ocr_confidence_threshold', '75', 'Umbral mínimo de confianza OCR'),
('ocr_similarity_threshold', '70', 'Umbral mínimo de similitud para matching'),
('ocr_auto_approve_threshold', '95', 'Umbral para aprobación automática'),
('ocr_backup_days', '30', 'Días para mantener archivos OCR'),
('double_control_required', '1', 'Requerir doble control para todas las operaciones'),
('supervisor_approval_required', '1', 'Requerir aprobación de supervisor');

-- Mensaje de finalización
SELECT 'Base de datos del sistema de doble control OCR configurada correctamente.' as mensaje;
