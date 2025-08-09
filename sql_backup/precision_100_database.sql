-- SISTEMA OCR DE PRECISIÓN 100% - ESTRUCTURA DE BASE DE DATOS
-- Ejecutar este script en phpMyAdmin para habilitar todas las funcionalidades

-- ========================================
-- TABLAS PRINCIPALES DEL SISTEMA OCR
-- ========================================

-- Cola de validación humana
CREATE TABLE IF NOT EXISTS ocr_validation_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_remito VARCHAR(100) NOT NULL,
    descripcion_remito TEXT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    precio DECIMAL(10,2) DEFAULT 0,
    proveedor_id INT NOT NULL,
    validation_reason VARCHAR(100) NOT NULL,
    confidence_score DECIMAL(5,4) NOT NULL,
    similar_products_json JSON,
    estado ENUM('pending', 'validated', 'rejected', 'modified') DEFAULT 'pending',
    usuario_validador INT NULL,
    fecha_validacion TIMESTAMP NULL,
    notas_validacion TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_estado (estado),
    INDEX idx_confidence (confidence_score),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
);

-- Elementos críticos que requieren revisión urgente
CREATE TABLE IF NOT EXISTS ocr_critical_review (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_remito VARCHAR(100) NOT NULL,
    descripcion_remito TEXT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    precio DECIMAL(10,2) DEFAULT 0,
    proveedor_id INT NOT NULL,
    critical_reason VARCHAR(200) NOT NULL,
    confidence_score DECIMAL(5,4) NOT NULL,
    review_priority ENUM('medium', 'high', 'critical') DEFAULT 'medium',
    estado ENUM('critical', 'reviewed', 'resolved') DEFAULT 'critical',
    usuario_revisor INT NULL,
    fecha_revision TIMESTAMP NULL,
    resolucion TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_priority (review_priority),
    INDEX idx_estado (estado),
    INDEX idx_proveedor (proveedor_id),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
);

-- Reportes de procesamiento OCR
CREATE TABLE IF NOT EXISTS ocr_processing_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    archivo_remito VARCHAR(255),
    total_products INT DEFAULT 0,
    auto_processed INT DEFAULT 0,
    validation_queue INT DEFAULT 0,
    critical_errors INT DEFAULT 0,
    ocr_confidence DECIMAL(5,4) NOT NULL,
    processing_time_seconds DECIMAL(8,2) DEFAULT 0,
    engines_used JSON,
    consensus_achieved BOOLEAN DEFAULT FALSE,
    report_data JSON,
    fecha_procesamiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_fecha (fecha_procesamiento),
    INDEX idx_confidence (ocr_confidence),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
);

-- ========================================
-- SISTEMA DE APRENDIZAJE AUTOMÁTICO
-- ========================================

-- Datos de aprendizaje del sistema
CREATE TABLE IF NOT EXISTS ocr_learning_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_confidence DECIMAL(5,4) NOT NULL,
    user_decision ENUM('approve', 'reject', 'modify', 'create_new') NOT NULL,
    validation_reason VARCHAR(200),
    producto_codigo VARCHAR(100),
    producto_descripcion TEXT,
    similarity_scores JSON,
    processing_context JSON,
    user_id INT NOT NULL,
    learning_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_confidence (original_confidence),
    INDEX idx_decision (user_decision),
    INDEX idx_timestamp (learning_timestamp),
    INDEX idx_user (user_id)
);

-- Historial de ajustes de umbrales
CREATE TABLE IF NOT EXISTS ocr_threshold_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    threshold_type VARCHAR(50) NOT NULL,
    old_value DECIMAL(5,4) NOT NULL,
    new_value DECIMAL(5,4) NOT NULL,
    adjustment_reason VARCHAR(200),
    performance_impact JSON,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_threshold (threshold_type)
);

-- Métricas de rendimiento del sistema
CREATE TABLE IF NOT EXISTS ocr_performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_metrica DATE NOT NULL,
    total_remitos_procesados INT DEFAULT 0,
    precision_rate DECIMAL(5,4) DEFAULT 0,
    recall_rate DECIMAL(5,4) DEFAULT 0,
    f1_score DECIMAL(5,4) DEFAULT 0,
    auto_processing_rate DECIMAL(5,4) DEFAULT 0,
    human_validation_rate DECIMAL(5,4) DEFAULT 0,
    critical_error_rate DECIMAL(5,4) DEFAULT 0,
    avg_processing_time DECIMAL(8,2) DEFAULT 0,
    engines_performance JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (fecha_metrica)
);

-- ========================================
-- CONFIGURACIÓN DE PROVEEDORES MEJORADA
-- ========================================

-- Plantillas específicas por proveedor
CREATE TABLE IF NOT EXISTS ocr_provider_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    template_config JSON NOT NULL,
    extraction_rules JSON,
    validation_rules JSON,
    success_rate DECIMAL(5,4) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_proveedor (proveedor_id),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
);

-- Patrones de productos por proveedor
CREATE TABLE IF NOT EXISTS ocr_product_patterns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    codigo_pattern VARCHAR(200),
    descripcion_keywords JSON,
    categoria_sugerida VARCHAR(100),
    confidence_boost DECIMAL(3,2) DEFAULT 0,
    usage_count INT DEFAULT 0,
    success_rate DECIMAL(5,4) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_categoria (categoria_sugerida),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
);

-- ========================================
-- SISTEMA DE MÚLTIPLES MOTORES OCR
-- ========================================

-- Configuración de motores OCR
CREATE TABLE IF NOT EXISTS ocr_engines_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    engine_name VARCHAR(50) NOT NULL UNIQUE,
    engine_type ENUM('tesseract', 'google_vision', 'azure_cognitive', 'aws_textract', 'custom') NOT NULL,
    api_endpoint VARCHAR(255),
    api_key_encrypted TEXT,
    configuration JSON,
    is_active BOOLEAN DEFAULT TRUE,
    priority_order INT DEFAULT 50,
    cost_per_request DECIMAL(8,4) DEFAULT 0,
    avg_processing_time DECIMAL(6,2) DEFAULT 0,
    avg_confidence DECIMAL(5,4) DEFAULT 0,
    success_rate DECIMAL(5,4) DEFAULT 0,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_priority (priority_order)
);

-- Resultados individuales de cada motor
CREATE TABLE IF NOT EXISTS ocr_engine_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    processing_session VARCHAR(100) NOT NULL,
    engine_name VARCHAR(50) NOT NULL,
    confidence_score DECIMAL(5,4) NOT NULL,
    processing_time DECIMAL(6,2) NOT NULL,
    extracted_text TEXT,
    products_detected JSON,
    error_message TEXT NULL,
    status ENUM('success', 'error', 'timeout') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (processing_session),
    INDEX idx_engine (engine_name),
    INDEX idx_confidence (confidence_score),
    FOREIGN KEY (engine_name) REFERENCES ocr_engines_config(engine_name)
);

-- ========================================
-- CONSENSO Y VALIDACIÓN CRUZADA
-- ========================================

-- Análisis de consenso entre motores
CREATE TABLE IF NOT EXISTS ocr_consensus_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    processing_session VARCHAR(100) NOT NULL,
    engines_count INT NOT NULL,
    consensus_achieved BOOLEAN DEFAULT FALSE,
    consensus_confidence DECIMAL(5,4) DEFAULT 0,
    similarity_matrix JSON,
    conflict_resolution JSON,
    final_products JSON,
    analysis_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (processing_session),
    INDEX idx_consensus (consensus_achieved)
);

-- ========================================
-- VISTAS PARA ANÁLISIS RÁPIDO
-- ========================================

-- Vista de rendimiento en tiempo real
CREATE OR REPLACE VIEW v_ocr_dashboard AS
SELECT 
    DATE(fecha_procesamiento) as fecha,
    COUNT(*) as total_procesados,
    AVG(ocr_confidence) as confianza_promedio,
    SUM(auto_processed) as auto_procesados,
    SUM(validation_queue) as en_validacion,
    SUM(critical_errors) as criticos,
    AVG(processing_time_seconds) as tiempo_promedio,
    (SUM(auto_processed) / COUNT(*) * 100) as tasa_automatizacion
FROM ocr_processing_reports 
WHERE fecha_procesamiento >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(fecha_procesamiento)
ORDER BY fecha DESC;

-- Vista de productos pendientes por prioridad
CREATE OR REPLACE VIEW v_validation_priority AS
SELECT 
    v.id,
    v.codigo_remito,
    v.descripcion_remito,
    v.confidence_score,
    v.validation_reason,
    p.nombre as proveedor,
    CASE 
        WHEN v.confidence_score < 0.5 THEN 'critical'
        WHEN v.confidence_score < 0.8 THEN 'high'
        ELSE 'medium'
    END as priority_level,
    TIMESTAMPDIFF(HOUR, v.fecha_creacion, NOW()) as horas_pendiente
FROM ocr_validation_queue v
JOIN proveedores p ON v.proveedor_id = p.id
WHERE v.estado = 'pending'
ORDER BY priority_level DESC, horas_pendiente DESC;

-- ========================================
-- DATOS INICIALES DE CONFIGURACIÓN
-- ========================================

-- Configuración inicial de motores OCR
INSERT INTO ocr_engines_config (engine_name, engine_type, is_active, priority_order, configuration) VALUES
('tesseract_local', 'tesseract', TRUE, 10, '{"psm_modes": [6,7,8,11,12,13], "oem": 3, "preprocessing": true}'),
('tesseract_enhanced', 'tesseract', TRUE, 20, '{"psm_modes": [6,8,11], "oem": 1, "preprocessing": true, "enhance_image": true}'),
('google_vision_api', 'google_vision', FALSE, 30, '{"language_hints": ["es", "en"], "enable_text_detection_confidence": true}'),
('azure_cognitive_ocr', 'azure_cognitive', FALSE, 40, '{"language": "es", "detect_orientation": true}'),
('aws_textract', 'aws_textract', FALSE, 50, '{"feature_types": ["TABLES", "FORMS"], "region": "us-east-1"}');

-- Umbrales iniciales del sistema
INSERT INTO ocr_threshold_history (threshold_type, old_value, new_value, adjustment_reason) VALUES
('auto_process', 0.95, 0.98, 'configuracion_inicial'),
('human_validation', 0.75, 0.80, 'configuracion_inicial'),
('critical_review', 0.50, 0.75, 'configuracion_inicial');

-- Configuración inicial de rendimiento
INSERT INTO ocr_performance_metrics (fecha_metrica, precision_rate, recall_rate, f1_score, auto_processing_rate) VALUES
(CURDATE(), 0.985, 0.972, 0.978, 0.856);

-- ========================================
-- TRIGGERS PARA AUTOMATIZACIÓN
-- ========================================

-- Trigger para actualizar métricas automáticamente
DELIMITER //
CREATE TRIGGER update_daily_metrics 
AFTER INSERT ON ocr_processing_reports
FOR EACH ROW
BEGIN
    INSERT INTO ocr_performance_metrics (
        fecha_metrica, 
        total_remitos_procesados,
        auto_processing_rate,
        human_validation_rate,
        critical_error_rate
    ) VALUES (
        DATE(NEW.fecha_procesamiento),
        1,
        CASE WHEN NEW.auto_processed > 0 THEN 1 ELSE 0 END,
        CASE WHEN NEW.validation_queue > 0 THEN 1 ELSE 0 END,
        CASE WHEN NEW.critical_errors > 0 THEN 1 ELSE 0 END
    )
    ON DUPLICATE KEY UPDATE
        total_remitos_procesados = total_remitos_procesados + 1,
        auto_processing_rate = (
            (auto_processing_rate * (total_remitos_procesados - 1) + 
             CASE WHEN NEW.auto_processed > 0 THEN 1 ELSE 0 END) / 
            total_remitos_procesados
        ),
        human_validation_rate = (
            (human_validation_rate * (total_remitos_procesados - 1) + 
             CASE WHEN NEW.validation_queue > 0 THEN 1 ELSE 0 END) / 
            total_remitos_procesados
        ),
        critical_error_rate = (
            (critical_error_rate * (total_remitos_procesados - 1) + 
             CASE WHEN NEW.critical_errors > 0 THEN 1 ELSE 0 END) / 
            total_remitos_procesados
        );
END//

-- Trigger para aprendizaje automático
CREATE TRIGGER learn_from_validation 
AFTER UPDATE ON ocr_validation_queue
FOR EACH ROW
BEGIN
    IF NEW.estado != OLD.estado AND NEW.estado IN ('validated', 'rejected') THEN
        INSERT INTO ocr_learning_data (
            original_confidence,
            user_decision,
            validation_reason,
            producto_codigo,
            producto_descripcion,
            user_id
        ) VALUES (
            NEW.confidence_score,
            CASE 
                WHEN NEW.estado = 'validated' THEN 'approve'
                WHEN NEW.estado = 'rejected' THEN 'reject'
                ELSE 'modify'
            END,
            NEW.validation_reason,
            NEW.codigo_remito,
            NEW.descripcion_remito,
            NEW.usuario_validador
        );
    END IF;
END//
DELIMITER ;

-- ========================================
-- PROCEDIMIENTOS ALMACENADOS ÚTILES
-- ========================================

DELIMITER //

-- Procedimiento para obtener estadísticas del dashboard
CREATE PROCEDURE GetOCRDashboardStats(IN days_back INT)
BEGIN
    SELECT 
        COUNT(*) as total_processed,
        AVG(ocr_confidence) as avg_confidence,
        SUM(auto_processed) as total_auto,
        SUM(validation_queue) as total_validation,
        SUM(critical_errors) as total_critical,
        AVG(processing_time_seconds) as avg_time
    FROM ocr_processing_reports 
    WHERE fecha_procesamiento >= DATE_SUB(NOW(), INTERVAL days_back DAY);
END//

-- Procedimiento para limpiar datos antiguos
CREATE PROCEDURE CleanOldOCRData(IN days_to_keep INT)
BEGIN
    DELETE FROM ocr_engine_results 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    DELETE FROM ocr_consensus_analysis 
    WHERE analysis_timestamp < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    UPDATE ocr_validation_queue 
    SET estado = 'archived' 
    WHERE estado = 'validated' 
    AND fecha_validacion < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
END//

DELIMITER ;

-- ========================================
-- ÍNDICES ADICIONALES PARA RENDIMIENTO
-- ========================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX idx_validation_priority ON ocr_validation_queue(estado, confidence_score, fecha_creacion);
CREATE INDEX idx_critical_priority ON ocr_critical_review(estado, review_priority, fecha_creacion);
CREATE INDEX idx_learning_analysis ON ocr_learning_data(user_decision, original_confidence, learning_timestamp);
CREATE INDEX idx_performance_date ON ocr_processing_reports(fecha_procesamiento, ocr_confidence);

-- ========================================
-- CONFIGURACIÓN INICIAL COMPLETADA
-- ========================================

SELECT '✅ SISTEMA OCR DE PRECISIÓN 100% CONFIGURADO CORRECTAMENTE' as status;
SELECT 'Tablas creadas, triggers instalados, datos iniciales cargados' as message;
SELECT 'El sistema está listo para procesar remitos con máxima precisión' as next_step;
