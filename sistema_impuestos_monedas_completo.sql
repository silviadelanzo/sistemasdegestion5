-- ACTUALIZACIÓN: SISTEMA IMPUESTOS/MONEDA PARA PRODUCTO_FORM.PHP
-- Versión mejorada con gestión de monedas

-- ============================================
-- 1. CREAR TABLA DE MONEDAS
-- ============================================

CREATE TABLE IF NOT EXISTS monedas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pais_id INT NOT NULL,
    codigo_iso VARCHAR(3) NOT NULL,        -- ARS, USD, EUR, MXN, etc.
    nombre VARCHAR(50) NOT NULL,           -- "Peso Argentino", "Dólar USA"
    simbolo VARCHAR(5) NOT NULL,           -- "$", "US$", "€", "Mex$"
    decimales TINYINT DEFAULT 2,           -- Cantidad de decimales
    separador_decimal VARCHAR(1) DEFAULT '.',  -- "." o ","
    separador_miles VARCHAR(1) DEFAULT ',',    -- "," o "."
    posicion_simbolo ENUM('antes', 'despues') DEFAULT 'antes',  -- $100 o 100$
    tasa_cambio_usd DECIMAL(10,4) DEFAULT 1.0000,  -- Para conversiones
    activo TINYINT(1) DEFAULT 1,
    es_principal TINYINT(1) DEFAULT 0,     -- Moneda principal del país
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE CASCADE,
    UNIQUE KEY unique_codigo_pais (codigo_iso, pais_id),
    INDEX idx_monedas_activo (activo),
    INDEX idx_monedas_principal (es_principal)
);

-- ============================================
-- 2. ACTUALIZAR TABLA IMPUESTOS (Mejorada)
-- ============================================

ALTER TABLE impuestos 
ADD COLUMN aplica_en_venta TINYINT(1) DEFAULT 1 AFTER activo,
ADD COLUMN mostrar_desglosado TINYINT(1) DEFAULT 1 AFTER aplica_en_venta,
ADD COLUMN orden_aplicacion TINYINT DEFAULT 1 AFTER mostrar_desglosado,
ADD COLUMN descripcion TEXT NULL AFTER orden_aplicacion;

-- ============================================
-- 3. ACTUALIZAR TABLA PRODUCTOS (Con moneda)
-- ============================================

-- Agregar columna de moneda
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "productos" AND COLUMN_NAME = "moneda_id"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE productos 
     ADD COLUMN moneda_id INT NULL AFTER impuesto_id,
     ADD COLUMN redondeo_decimales TINYINT DEFAULT 2 AFTER moneda_id,
     ADD COLUMN tipo_redondeo ENUM("centavo", "peso", "cinco_pesos") DEFAULT "centavo" AFTER redondeo_decimales',
    'SELECT "Columnas de moneda ya existen en productos" as mensaje');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar FK de moneda
SET @sql = 'SELECT COUNT(*) INTO @fk_exists FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "productos" AND CONSTRAINT_NAME = "fk_productos_moneda"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE productos 
     ADD CONSTRAINT fk_productos_moneda FOREIGN KEY (moneda_id) REFERENCES monedas(id) ON DELETE SET NULL',
    'SELECT "FK moneda ya existe en productos" as mensaje');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 4. DATOS INICIALES DE MONEDAS HISPANAS
-- ============================================

-- Argentina
INSERT IGNORE INTO monedas (pais_id, codigo_iso, nombre, simbolo, tasa_cambio_usd, es_principal) 
SELECT id, 'ARS', 'Peso Argentino', '$', 350.00, 1 FROM paises WHERE nombre = 'Argentina';

-- México  
INSERT IGNORE INTO monedas (pais_id, codigo_iso, nombre, simbolo, tasa_cambio_usd, es_principal) 
SELECT id, 'MXN', 'Peso Mexicano', 'Mex$', 17.50, 1 FROM paises WHERE nombre = 'México';

-- Colombia
INSERT IGNORE INTO monedas (pais_id, codigo_iso, nombre, simbolo, tasa_cambio_usd, es_principal) 
SELECT id, 'COP', 'Peso Colombiano', 'Col$', 4200.00, 1 FROM paises WHERE nombre = 'Colombia';

-- Chile
INSERT IGNORE INTO monedas (pais_id, codigo_iso, nombre, simbolo, tasa_cambio_usd, es_principal) 
SELECT id, 'CLP', 'Peso Chileno', '$', 950.00, 1 FROM paises WHERE nombre = 'Chile';

-- Perú
INSERT IGNORE INTO monedas (pais_id, codigo_iso, nombre, simbolo, tasa_cambio_usd, es_principal) 
SELECT id, 'PEN', 'Sol Peruano', 'S/', 3.75, 1 FROM paises WHERE nombre = 'Perú';

-- España
INSERT IGNORE INTO monedas (pais_id, codigo_iso, nombre, simbolo, tasa_cambio_usd, es_principal, separador_decimal, separador_miles) 
SELECT id, 'EUR', 'Euro', '€', 0.92, 1, ',', '.' FROM paises WHERE nombre = 'España';

-- Estados Unidos (referencia)
INSERT IGNORE INTO monedas (pais_id, codigo_iso, nombre, simbolo, tasa_cambio_usd, es_principal) 
SELECT id, 'USD', 'Dólar Estadounidense', 'US$', 1.00, 1 FROM paises WHERE nombre = 'Estados Unidos';

-- Brasil
INSERT IGNORE INTO monedas (pais_id, codigo_iso, nombre, simbolo, tasa_cambio_usd, es_principal, separador_decimal, separador_miles) 
SELECT id, 'BRL', 'Real Brasileño', 'R$', 5.20, 1, ',', '.' FROM paises WHERE nombre = 'Brasil';

-- ============================================
-- 5. FUNCIÓN PARA FORMATEAR MONEDAS
-- ============================================

DELIMITER //

DROP FUNCTION IF EXISTS formatear_precio//

CREATE FUNCTION formatear_precio(
    precio DECIMAL(10,2),
    moneda_id INT
) RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE resultado VARCHAR(20);
    DECLARE simbolo VARCHAR(5);
    DECLARE posicion VARCHAR(10);
    DECLARE decimales TINYINT;
    DECLARE sep_decimal VARCHAR(1);
    DECLARE sep_miles VARCHAR(1);
    
    -- Obtener configuración de la moneda
    SELECT m.simbolo, m.posicion_simbolo, m.decimales, m.separador_decimal, m.separador_miles
    INTO simbolo, posicion, decimales, sep_decimal, sep_miles
    FROM monedas m WHERE m.id = moneda_id;
    
    -- Si no existe la moneda, usar formato por defecto
    IF simbolo IS NULL THEN
        SET simbolo = '$';
        SET posicion = 'antes';
        SET decimales = 2;
        SET sep_decimal = '.';
        SET sep_miles = ',';
    END IF;
    
    -- Formatear el número
    SET resultado = FORMAT(precio, decimales, 'es_AR');
    
    -- Aplicar separadores específicos si es necesario
    IF sep_decimal != '.' OR sep_miles != ',' THEN
        SET resultado = REPLACE(resultado, '.', '|TEMP|');
        SET resultado = REPLACE(resultado, ',', sep_miles);
        SET resultado = REPLACE(resultado, '|TEMP|', sep_decimal);
    END IF;
    
    -- Agregar símbolo según posición
    IF posicion = 'antes' THEN
        SET resultado = CONCAT(simbolo, resultado);
    ELSE
        SET resultado = CONCAT(resultado, simbolo);
    END IF;
    
    RETURN resultado;
END//

DELIMITER ;

-- ============================================
-- 6. VISTA ACTUALIZADA CON MONEDAS
-- ============================================

CREATE OR REPLACE VIEW productos_completos AS
SELECT 
    p.*,
    c.nombre as categoria_nombre,
    l.nombre as lugar_nombre,
    i.nombre as impuesto_nombre,
    i.porcentaje as impuesto_porcentaje,
    i.aplica_en_venta,
    i.mostrar_desglosado,
    pa.nombre as impuesto_pais,
    m.codigo_iso as moneda_codigo,
    m.nombre as moneda_nombre,
    m.simbolo as moneda_simbolo,
    formatear_precio(p.precio_compra, p.moneda_id) as precio_compra_formateado,
    formatear_precio(calcular_precio_con_impuesto(p.precio_compra, i.porcentaje, p.utilidad_minorista), p.moneda_id) as precio_minorista_formateado,
    formatear_precio(calcular_precio_con_impuesto(p.precio_compra, i.porcentaje, p.utilidad_mayorista), p.moneda_id) as precio_mayorista_formateado
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN lugares l ON p.lugar_id = l.id
LEFT JOIN impuestos i ON p.impuesto_id = i.id
LEFT JOIN paises pa ON i.pais_id = pa.id
LEFT JOIN monedas m ON p.moneda_id = m.id;

-- ============================================
-- 7. PROCEDIMIENTO PARA GESTIÓN DE IMPUESTOS
-- ============================================

DELIMITER //

DROP PROCEDURE IF EXISTS gestionar_impuesto//

CREATE PROCEDURE gestionar_impuesto(
    IN accion VARCHAR(10),        -- 'crear', 'modificar', 'desactivar'
    IN impuesto_id INT,
    IN pais_id INT,
    IN nombre VARCHAR(50),
    IN porcentaje DECIMAL(5,2),
    IN tipo VARCHAR(20),
    IN fecha_desde DATE
)
BEGIN
    CASE accion
        WHEN 'crear' THEN
            INSERT INTO impuestos (pais_id, nombre, porcentaje, tipo, fecha_vigencia_desde)
            VALUES (pais_id, nombre, porcentaje, tipo, fecha_desde);
            
        WHEN 'modificar' THEN
            UPDATE impuestos 
            SET nombre = nombre, porcentaje = porcentaje, updated_at = NOW()
            WHERE id = impuesto_id;
            
        WHEN 'desactivar' THEN
            UPDATE impuestos 
            SET activo = 0, fecha_vigencia_hasta = CURDATE()
            WHERE id = impuesto_id;
    END CASE;
END//

DELIMITER ;

-- ============================================
-- 8. VERIFICACIÓN Y RESULTADOS
-- ============================================

SELECT 'Sistema de Impuestos/Monedas configurado exitosamente' as resultado;

-- Mostrar monedas disponibles
SELECT 
    p.nombre as pais,
    m.codigo_iso,
    m.nombre as moneda,
    m.simbolo,
    CONCAT('1 USD = ', m.tasa_cambio_usd, ' ', m.codigo_iso) as tasa_cambio
FROM monedas m
JOIN paises p ON m.pais_id = p.id
WHERE m.activo = 1
ORDER BY p.nombre;

-- Mostrar impuestos por país
SELECT 
    p.nombre as pais,
    i.nombre as impuesto,
    CONCAT(i.porcentaje, '%') as tasa,
    i.tipo,
    CASE WHEN i.activo = 1 THEN 'Activo' ELSE 'Inactivo' END as estado
FROM impuestos i
JOIN paises p ON i.pais_id = p.id
ORDER BY p.nombre, i.porcentaje;
