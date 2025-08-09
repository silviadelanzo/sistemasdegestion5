-- SCRIPT DE CONFIGURACIÓN MEJORADA PARA SISTEMA DE COMPRAS
-- Actualiza las tablas existentes y crea la estructura jerárquica

-- ============================================
-- CREAR TABLAS JERÁRQUICAS DE UBICACIÓN
-- ============================================

CREATE TABLE IF NOT EXISTS paises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    codigo_iso VARCHAR(3) NULL,
    codigo_telefono VARCHAR(10) NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS provincias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    pais_id INT NOT NULL,
    codigo VARCHAR(10) NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provincia_pais (nombre, pais_id)
);

CREATE TABLE IF NOT EXISTS ciudades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    provincia_id INT NOT NULL,
    codigo_postal VARCHAR(20) NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provincia_id) REFERENCES provincias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ciudad_provincia (nombre, provincia_id)
);

-- ============================================
-- ACTUALIZAR TABLA PROVEEDORES
-- ============================================

-- Verificar si las columnas ya existen antes de agregarlas
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "proveedores" AND COLUMN_NAME = "pais_id"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar nuevas columnas si no existen
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE proveedores 
     ADD COLUMN pais_id INT NULL AFTER direccion,
     ADD COLUMN provincia_id INT NULL AFTER pais_id,
     ADD COLUMN ciudad_id INT NULL AFTER provincia_id,
     ADD COLUMN codigo_postal VARCHAR(20) NULL AFTER ciudad_id,
     ADD COLUMN telefono VARCHAR(20) NULL AFTER codigo_postal,
     ADD COLUMN telefono_alternativo VARCHAR(20) NULL AFTER telefono,
     ADD COLUMN whatsapp VARCHAR(20) NULL AFTER telefono_alternativo,
     ADD COLUMN sitio_web VARCHAR(255) NULL AFTER whatsapp,
     ADD COLUMN tipo_proveedor ENUM("producto", "servicio", "ambos") DEFAULT "producto" AFTER sitio_web,
     ADD COLUMN calificacion DECIMAL(3,2) DEFAULT 0.00 AFTER tipo_proveedor,
     ADD COLUMN notas TEXT NULL AFTER calificacion,
     ADD COLUMN fecha_ultimo_contacto DATE NULL AFTER notas,
     ADD COLUMN activo TINYINT(1) DEFAULT 1 AFTER fecha_ultimo_contacto,
     ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER activo,
     ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    'SELECT "Columnas ya existen en proveedores" as mensaje');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar índices y claves foráneas si no existen
SET @sql = 'SELECT COUNT(*) INTO @fk_exists FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "proveedores" AND CONSTRAINT_NAME = "fk_proveedores_pais"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE proveedores 
     ADD CONSTRAINT fk_proveedores_pais FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE SET NULL,
     ADD CONSTRAINT fk_proveedores_provincia FOREIGN KEY (provincia_id) REFERENCES provincias(id) ON DELETE SET NULL,
     ADD CONSTRAINT fk_proveedores_ciudad FOREIGN KEY (ciudad_id) REFERENCES ciudades(id) ON DELETE SET NULL,
     ADD INDEX idx_proveedores_pais (pais_id),
     ADD INDEX idx_proveedores_provincia (provincia_id),
     ADD INDEX idx_proveedores_ciudad (ciudad_id),
     ADD INDEX idx_proveedores_activo (activo)',
    'SELECT "Claves foráneas ya existen en proveedores" as mensaje');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- ACTUALIZAR TABLA COMPRAS
-- ============================================

-- Verificar si las columnas ya existen en compras
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "compras" AND COLUMN_NAME = "numero_remito"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar nuevas columnas a compras si no existen
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE compras 
     ADD COLUMN numero_remito VARCHAR(50) NULL AFTER fecha,
     ADD COLUMN fecha_remito DATE NULL AFTER numero_remito,
     ADD COLUMN condiciones_pago ENUM("contado", "30_dias", "60_dias", "90_dias", "personalizado") DEFAULT "contado" AFTER fecha_remito,
     ADD COLUMN dias_pago_personalizado INT NULL AFTER condiciones_pago,
     ADD COLUMN descuento_porcentaje DECIMAL(5,2) DEFAULT 0.00 AFTER dias_pago_personalizado,
     ADD COLUMN impuesto_porcentaje DECIMAL(5,2) DEFAULT 21.00 AFTER descuento_porcentaje,
     ADD COLUMN estado ENUM("borrador", "enviada", "confirmada", "recibida", "facturada", "pagada", "cancelada") DEFAULT "borrador" AFTER impuesto_porcentaje,
     ADD COLUMN notas TEXT NULL AFTER estado,
     ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER notas,
     ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    'SELECT "Columnas ya existen en compras" as mensaje');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- DATOS INICIALES PARA ARGENTINA
-- ============================================

-- Insertar Argentina
INSERT IGNORE INTO paises (nombre, codigo_iso, codigo_telefono) VALUES 
('Argentina', 'ARG', '+54');

-- Obtener ID de Argentina
SET @argentina_id = (SELECT id FROM paises WHERE nombre = 'Argentina');

-- Insertar provincias argentinas
INSERT IGNORE INTO provincias (nombre, pais_id, codigo) VALUES 
('Buenos Aires', @argentina_id, 'BA'),
('Catamarca', @argentina_id, 'CT'),
('Chaco', @argentina_id, 'CC'),
('Chubut', @argentina_id, 'CH'),
('Córdoba', @argentina_id, 'CB'),
('Corrientes', @argentina_id, 'CR'),
('Entre Ríos', @argentina_id, 'ER'),
('Formosa', @argentina_id, 'FM'),
('Jujuy', @argentina_id, 'JY'),
('La Pampa', @argentina_id, 'LP'),
('La Rioja', @argentina_id, 'LR'),
('Mendoza', @argentina_id, 'MZ'),
('Misiones', @argentina_id, 'MN'),
('Neuquén', @argentina_id, 'NQ'),
('Río Negro', @argentina_id, 'RN'),
('Salta', @argentina_id, 'SA'),
('San Juan', @argentina_id, 'SJ'),
('San Luis', @argentina_id, 'SL'),
('Santa Cruz', @argentina_id, 'SC'),
('Santa Fe', @argentina_id, 'SF'),
('Santiago del Estero', @argentina_id, 'SE'),
('Tierra del Fuego', @argentina_id, 'TF'),
('Tucumán', @argentina_id, 'TM'),
('Ciudad Autónoma de Buenos Aires', @argentina_id, 'CABA');

-- Insertar algunas ciudades principales
SET @buenos_aires_id = (SELECT id FROM provincias WHERE nombre = 'Buenos Aires' AND pais_id = @argentina_id);
SET @caba_id = (SELECT id FROM provincias WHERE nombre = 'Ciudad Autónoma de Buenos Aires' AND pais_id = @argentina_id);
SET @cordoba_id = (SELECT id FROM provincias WHERE nombre = 'Córdoba' AND pais_id = @argentina_id);
SET @santa_fe_id = (SELECT id FROM provincias WHERE nombre = 'Santa Fe' AND pais_id = @argentina_id);

INSERT IGNORE INTO ciudades (nombre, provincia_id, codigo_postal) VALUES 
-- Buenos Aires
('La Plata', @buenos_aires_id, '1900'),
('Mar del Plata', @buenos_aires_id, '7600'),
('Bahía Blanca', @buenos_aires_id, '8000'),
('San Nicolás', @buenos_aires_id, '2900'),
('Tandil', @buenos_aires_id, '7000'),
-- CABA
('Buenos Aires', @caba_id, '1000'),
-- Córdoba
('Córdoba Capital', @cordoba_id, '5000'),
('Villa Carlos Paz', @cordoba_id, '5152'),
('Río Cuarto', @cordoba_id, '5800'),
-- Santa Fe
('Santa Fe Capital', @santa_fe_id, '3000'),
('Rosario', @santa_fe_id, '2000'),
('Rafaela', @santa_fe_id, '2300');

-- ============================================
-- OTROS PAÍSES COMUNES PARA PROVEEDORES
-- ============================================

INSERT IGNORE INTO paises (nombre, codigo_iso, codigo_telefono) VALUES 
('Brasil', 'BRA', '+55'),
('Chile', 'CHL', '+56'),
('Uruguay', 'URY', '+598'),
('Paraguay', 'PRY', '+595'),
('Bolivia', 'BOL', '+591'),
('Perú', 'PER', '+51'),
('Colombia', 'COL', '+57'),
('Estados Unidos', 'USA', '+1'),
('México', 'MEX', '+52'),
('España', 'ESP', '+34'),
('China', 'CHN', '+86'),
('Alemania', 'DEU', '+49'),
('Italia', 'ITA', '+39'),
('Francia', 'FRA', '+33');

-- ============================================
-- ÍNDICES PARA OPTIMIZACIÓN
-- ============================================

-- Índices para búsquedas rápidas
ALTER TABLE paises ADD INDEX idx_paises_activo (activo);
ALTER TABLE provincias ADD INDEX idx_provincias_activo (activo);
ALTER TABLE ciudades ADD INDEX idx_ciudades_activo (activo);

-- Índices compuestos para consultas frecuentes
ALTER TABLE provincias ADD INDEX idx_provincias_pais_activo (pais_id, activo);
ALTER TABLE ciudades ADD INDEX idx_ciudades_provincia_activo (provincia_id, activo);

-- ============================================
-- VERIFICACIÓN FINAL
-- ============================================

SELECT 'Estructura creada exitosamente' as resultado;
SELECT COUNT(*) as total_paises FROM paises;
SELECT COUNT(*) as total_provincias FROM provincias;
SELECT COUNT(*) as total_ciudades FROM ciudades;

-- Mostrar estructura de proveedores actualizada
DESCRIBE proveedores;
