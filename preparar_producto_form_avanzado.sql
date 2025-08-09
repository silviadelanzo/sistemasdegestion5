-- SCRIPT PARA PREPARAR PRODUCTO_FORM.PHP AVANZADO
-- Sistema multi-país con pestañas según capturas

-- ============================================
-- 1. CREAR TABLA DE IMPUESTOS MULTI-PAÍS
-- ============================================

CREATE TABLE IF NOT EXISTS impuestos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pais_id INT NOT NULL,
    nombre VARCHAR(50) NOT NULL,           -- "IVA", "ITBMS", "GST", etc.
    porcentaje DECIMAL(5,2) NOT NULL,      -- 21.00, 7.00, etc.
    tipo ENUM('iva', 'igv', 'gst', 'ieps', 'otros') DEFAULT 'iva',
    activo TINYINT(1) DEFAULT 1,
    fecha_vigencia_desde DATE NOT NULL,
    fecha_vigencia_hasta DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE CASCADE,
    UNIQUE KEY unique_impuesto_pais (nombre, pais_id, fecha_vigencia_desde)
);

-- ============================================
-- 2. ACTUALIZAR TABLA PRODUCTOS
-- ============================================

-- Verificar si necesitamos agregar columnas
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "productos" AND COLUMN_NAME = "codigo_interno"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar nuevas columnas si no existen
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE productos 
     ADD COLUMN codigo_interno VARCHAR(20) UNIQUE NULL AFTER id,
     ADD COLUMN codigo_producto VARCHAR(100) UNIQUE NULL AFTER codigo_interno,
     ADD COLUMN unidad_medida VARCHAR(20) DEFAULT "UN" AFTER descripcion,
     ADD COLUMN factor_conversion DECIMAL(10,2) DEFAULT 1.00 AFTER unidad_medida,
     ADD COLUMN en_oferta TINYINT(1) DEFAULT 0 AFTER factor_conversion,
     ADD COLUMN publicar_web TINYINT(1) DEFAULT 0 AFTER en_oferta,
     ADD COLUMN precio_minorista DECIMAL(10,2) DEFAULT 0.00 AFTER publicar_web,
     ADD COLUMN precio_mayorista DECIMAL(10,2) DEFAULT 0.00 AFTER precio_minorista,
     ADD COLUMN impuesto_id INT NULL AFTER precio_mayorista,
     ADD COLUMN utilidad_minorista DECIMAL(5,2) DEFAULT 0.00 AFTER impuesto_id,
     ADD COLUMN utilidad_mayorista DECIMAL(5,2) DEFAULT 0.00 AFTER utilidad_minorista,
     ADD COLUMN stock_maximo INT DEFAULT 1000 AFTER stock_minimo,
     ADD COLUMN usar_control_stock TINYINT(1) DEFAULT 1 AFTER stock_maximo,
     ADD COLUMN fecha_vencimiento DATE NULL AFTER usar_control_stock,
     ADD COLUMN alerta_vencimiento_dias INT DEFAULT 15 AFTER fecha_vencimiento,
     ADD COLUMN usar_alerta_vencimiento TINYINT(1) DEFAULT 0 AFTER alerta_vencimiento_dias',
    'SELECT "Columnas ya existen en productos" as mensaje');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 3. CREAR TABLA PRODUCTOS-PROVEEDORES
-- ============================================

CREATE TABLE IF NOT EXISTS producto_proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    proveedor_id INT NOT NULL,
    codigo_proveedor VARCHAR(100) NULL,
    precio_proveedor DECIMAL(10,2) DEFAULT 0.00,
    es_proveedor_principal TINYINT(1) DEFAULT 0,
    tiempo_entrega_dias INT DEFAULT 7,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_producto_proveedor (producto_id, proveedor_id)
);

-- ============================================
-- 4. AGREGAR FOREIGN KEYS Y ÍNDICES
-- ============================================

-- Agregar FK de impuesto si no existe
SET @sql = 'SELECT COUNT(*) INTO @fk_exists FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "productos" AND CONSTRAINT_NAME = "fk_productos_impuesto"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE productos 
     ADD CONSTRAINT fk_productos_impuesto FOREIGN KEY (impuesto_id) REFERENCES impuestos(id) ON DELETE SET NULL,
     ADD INDEX idx_productos_codigo_interno (codigo_interno),
     ADD INDEX idx_productos_codigo_producto (codigo_producto),
     ADD INDEX idx_productos_unidad_medida (unidad_medida),
     ADD INDEX idx_productos_en_oferta (en_oferta),
     ADD INDEX idx_productos_publicar_web (publicar_web),
     ADD INDEX idx_productos_vencimiento (fecha_vencimiento)',
    'SELECT "Foreign keys ya existen en productos" as mensaje');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 5. DATOS INICIALES DE IMPUESTOS
-- ============================================

-- Impuestos para Argentina
INSERT IGNORE INTO impuestos (pais_id, nombre, porcentaje, tipo, fecha_vigencia_desde) 
SELECT id, 'IVA General', 21.00, 'iva', '2020-01-01' FROM paises WHERE nombre = 'Argentina';

INSERT IGNORE INTO impuestos (pais_id, nombre, porcentaje, tipo, fecha_vigencia_desde) 
SELECT id, 'IVA Reducido', 10.50, 'iva', '2020-01-01' FROM paises WHERE nombre = 'Argentina';

INSERT IGNORE INTO impuestos (pais_id, nombre, porcentaje, tipo, fecha_vigencia_desde) 
SELECT id, 'Exento', 0.00, 'iva', '2020-01-01' FROM paises WHERE nombre = 'Argentina';

-- Impuestos para otros países hispanos
INSERT IGNORE INTO impuestos (pais_id, nombre, porcentaje, tipo, fecha_vigencia_desde) 
SELECT id, 'IVA', 19.00, 'iva', '2020-01-01' FROM paises WHERE nombre = 'Colombia';

INSERT IGNORE INTO impuestos (pais_id, nombre, porcentaje, tipo, fecha_vigencia_desde) 
SELECT id, 'IGV', 18.00, 'igv', '2020-01-01' FROM paises WHERE nombre = 'Perú';

INSERT IGNORE INTO impuestos (pais_id, nombre, porcentaje, tipo, fecha_vigencia_desde) 
SELECT id, 'IVA', 19.00, 'iva', '2020-01-01' FROM paises WHERE nombre = 'Chile';

INSERT IGNORE INTO impuestos (pais_id, nombre, porcentaje, tipo, fecha_vigencia_desde) 
SELECT id, 'IVA', 16.00, 'iva', '2020-01-01' FROM paises WHERE nombre = 'México';

INSERT IGNORE INTO impuestos (pais_id, nombre, porcentaje, tipo, fecha_vigencia_desde) 
SELECT id, 'IVA', 21.00, 'iva', '2020-01-01' FROM paises WHERE nombre = 'España';

INSERT IGNORE INTO impuestos (pais_id, nombre, porcentaje, tipo, fecha_vigencia_desde) 
SELECT id, 'ITBMS', 7.00, 'iva', '2020-01-01' FROM paises WHERE nombre = 'Panamá';

-- ============================================
-- 6. CREAR TRIGGER PARA CÓDIGO INTERNO AUTO
-- ============================================

DELIMITER //

DROP TRIGGER IF EXISTS generar_codigo_interno//

CREATE TRIGGER generar_codigo_interno
BEFORE INSERT ON productos
FOR EACH ROW
BEGIN
    IF NEW.codigo_interno IS NULL OR NEW.codigo_interno = '' THEN
        DECLARE next_id INT DEFAULT 1;
        
        -- Obtener el próximo ID disponible
        SELECT IFNULL(MAX(id), 0) + 1 INTO next_id FROM productos;
        
        -- Generar código con formato PROD-0000XXX
        SET NEW.codigo_interno = CONCAT('PROD-', LPAD(next_id, 7, '0'));
    END IF;
END//

DELIMITER ;

-- ============================================
-- 7. FUNCIÓN PARA CALCULAR PRECIOS
-- ============================================

DELIMITER //

DROP FUNCTION IF EXISTS calcular_precio_con_impuesto//

CREATE FUNCTION calcular_precio_con_impuesto(
    precio_base DECIMAL(10,2),
    impuesto_pct DECIMAL(5,2),
    utilidad_pct DECIMAL(5,2)
) RETURNS DECIMAL(10,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE precio_con_utilidad DECIMAL(10,2);
    DECLARE precio_final DECIMAL(10,2);
    
    -- Calcular precio con utilidad
    SET precio_con_utilidad = precio_base * (1 + utilidad_pct / 100);
    
    -- Calcular precio final con impuesto
    SET precio_final = precio_con_utilidad * (1 + impuesto_pct / 100);
    
    RETURN precio_final;
END//

DELIMITER ;

-- ============================================
-- 8. VISTA PARA PRODUCTOS COMPLETOS
-- ============================================

CREATE OR REPLACE VIEW productos_completos AS
SELECT 
    p.*,
    c.nombre as categoria_nombre,
    l.nombre as lugar_nombre,
    i.nombre as impuesto_nombre,
    i.porcentaje as impuesto_porcentaje,
    pa.nombre as impuesto_pais,
    calcular_precio_con_impuesto(p.precio_compra, i.porcentaje, p.utilidad_minorista) as precio_minorista_final,
    calcular_precio_con_impuesto(p.precio_compra, i.porcentaje, p.utilidad_mayorista) as precio_mayorista_final
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN lugares l ON p.lugar_id = l.id
LEFT JOIN impuestos i ON p.impuesto_id = i.id
LEFT JOIN paises pa ON i.pais_id = pa.id;

-- ============================================
-- 9. VERIFICACIÓN FINAL
-- ============================================

SELECT 'Base de datos preparada para producto_form.php avanzado' as resultado;

-- Mostrar estructura actualizada
DESCRIBE productos;

-- Mostrar impuestos disponibles
SELECT 
    i.nombre as impuesto,
    i.porcentaje,
    p.nombre as pais
FROM impuestos i 
JOIN paises p ON i.pais_id = p.id 
WHERE i.activo = 1
ORDER BY p.nombre, i.porcentaje;
