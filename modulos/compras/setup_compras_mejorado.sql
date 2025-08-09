-- SCRIPT PARA CREAR/ACTUALIZAR TABLAS DE PROVEEDORES Y COMPRAS
-- Sistema de Gestión - Módulo de Compras Mejorado

-- Crear tabla de países si no existe
CREATE TABLE IF NOT EXISTS paises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(3),
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de provincias si no existe  
CREATE TABLE IF NOT EXISTS provincias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    pais_id INT,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_pais (pais_id),
    FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de ciudades si no existe
CREATE TABLE IF NOT EXISTS ciudades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    provincia_id INT,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_provincia (provincia_id),
    FOREIGN KEY (provincia_id) REFERENCES provincias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear/Actualizar tabla de proveedores
CREATE TABLE IF NOT EXISTS proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    razon_social VARCHAR(255) NOT NULL,
    nombre_comercial VARCHAR(255),
    cuit VARCHAR(20),
    direccion TEXT,
    pais_id INT,
    provincia_id INT,
    ciudad_id INT,
    telefono VARCHAR(20),
    whatsapp VARCHAR(20),
    email VARCHAR(100),
    sitio_web VARCHAR(255),
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_razon_social (razon_social),
    INDEX idx_cuit (cuit),
    INDEX idx_activo (activo),
    INDEX idx_pais (pais_id),
    INDEX idx_provincia (provincia_id),
    INDEX idx_ciudad (ciudad_id),
    FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE SET NULL,
    FOREIGN KEY (provincia_id) REFERENCES provincias(id) ON DELETE SET NULL,
    FOREIGN KEY (ciudad_id) REFERENCES ciudades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columnas a proveedores si no existen
ALTER TABLE proveedores 
ADD COLUMN IF NOT EXISTS whatsapp VARCHAR(20) AFTER telefono,
ADD COLUMN IF NOT EXISTS sitio_web VARCHAR(255) AFTER email,
ADD COLUMN IF NOT EXISTS pais_id INT AFTER direccion,
ADD COLUMN IF NOT EXISTS provincia_id INT AFTER pais_id,
ADD COLUMN IF NOT EXISTS ciudad_id INT AFTER provincia_id;

-- Agregar índices y claves foráneas si no existen
ALTER TABLE proveedores 
ADD INDEX IF NOT EXISTS idx_pais (pais_id),
ADD INDEX IF NOT EXISTS idx_provincia (provincia_id),
ADD INDEX IF NOT EXISTS idx_ciudad (ciudad_id);

-- Crear/Actualizar tabla de compras
CREATE TABLE IF NOT EXISTS compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    numero_remito VARCHAR(50),
    fecha_compra DATE NOT NULL,
    fecha_entrega_estimada DATE,
    fecha_recepcion DATE,
    estado ENUM('pendiente', 'confirmada', 'parcial', 'recibida', 'cancelada') DEFAULT 'pendiente',
    total DECIMAL(10,2) DEFAULT 0.00,
    observaciones TEXT,
    usuario_id INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_fecha_compra (fecha_compra),
    INDEX idx_estado (estado),
    INDEX idx_usuario (usuario_id),
    INDEX idx_numero_remito (numero_remito),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columnas a compras si no existen
ALTER TABLE compras 
ADD COLUMN IF NOT EXISTS numero_remito VARCHAR(50) AFTER proveedor_id,
ADD COLUMN IF NOT EXISTS fecha_entrega_estimada DATE AFTER fecha_compra,
ADD COLUMN IF NOT EXISTS fecha_recepcion DATE AFTER fecha_entrega_estimada;

-- Crear tabla de detalles de compra
CREATE TABLE IF NOT EXISTS compra_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compra_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad DECIMAL(10,3) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_compra (compra_id),
    INDEX idx_producto (producto_id),
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar países básicos si no existen
INSERT IGNORE INTO paises (nombre, codigo) VALUES 
('Argentina', 'AR'),
('Brasil', 'BR'),
('Chile', 'CL'),
('Uruguay', 'UY'),
('Paraguay', 'PY'),
('Bolivia', 'BO'),
('Perú', 'PE'),
('Colombia', 'CO'),
('Venezuela', 'VE'),
('Ecuador', 'EC');

-- Insertar provincias argentinas básicas si no existen
INSERT IGNORE INTO provincias (nombre, pais_id) 
SELECT 'Buenos Aires', id FROM paises WHERE codigo = 'AR' LIMIT 1;

INSERT IGNORE INTO provincias (nombre, pais_id) 
SELECT 'Córdoba', id FROM paises WHERE codigo = 'AR' LIMIT 1;

INSERT IGNORE INTO provincias (nombre, pais_id) 
SELECT 'Santa Fe', id FROM paises WHERE codigo = 'AR' LIMIT 1;

INSERT IGNORE INTO provincias (nombre, pais_id) 
SELECT 'Mendoza', id FROM paises WHERE codigo = 'AR' LIMIT 1;

INSERT IGNORE INTO provincias (nombre, pais_id) 
SELECT 'Tucumán', id FROM paises WHERE codigo = 'AR' LIMIT 1;

INSERT IGNORE INTO provincias (nombre, pais_id) 
SELECT 'Entre Ríos', id FROM paises WHERE codigo = 'AR' LIMIT 1;

INSERT IGNORE INTO provincias (nombre, pais_id) 
SELECT 'Salta', id FROM paises WHERE codigo = 'AR' LIMIT 1;

INSERT IGNORE INTO provincias (nombre, pais_id) 
SELECT 'Misiones', id FROM paises WHERE codigo = 'AR' LIMIT 1;

INSERT IGNORE INTO provincias (nombre, pais_id) 
SELECT 'Chaco', id FROM paises WHERE codigo = 'AR' LIMIT 1;

INSERT IGNORE INTO provincias (nombre, pais_id) 
SELECT 'Corrientes', id FROM paises WHERE codigo = 'AR' LIMIT 1;

-- Insertar ciudades básicas si no existen
INSERT IGNORE INTO ciudades (nombre, provincia_id) 
SELECT 'CABA', id FROM provincias WHERE nombre = 'Buenos Aires' LIMIT 1;

INSERT IGNORE INTO ciudades (nombre, provincia_id) 
SELECT 'La Plata', id FROM provincias WHERE nombre = 'Buenos Aires' LIMIT 1;

INSERT IGNORE INTO ciudades (nombre, provincia_id) 
SELECT 'Mar del Plata', id FROM provincias WHERE nombre = 'Buenos Aires' LIMIT 1;

INSERT IGNORE INTO ciudades (nombre, provincia_id) 
SELECT 'Córdoba Capital', id FROM provincias WHERE nombre = 'Córdoba' LIMIT 1;

INSERT IGNORE INTO ciudades (nombre, provincia_id) 
SELECT 'Rosario', id FROM provincias WHERE nombre = 'Santa Fe' LIMIT 1;

INSERT IGNORE INTO ciudades (nombre, provincia_id) 
SELECT 'Santa Fe Capital', id FROM provincias WHERE nombre = 'Santa Fe' LIMIT 1;

-- Verificar estructura de tablas
SELECT 'Verificando estructura de tablas...' as mensaje;

SELECT 
    'proveedores' as tabla,
    COUNT(*) as total_registros,
    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivos
FROM proveedores
UNION ALL
SELECT 
    'compras' as tabla,
    COUNT(*) as total_registros,
    SUM(CASE WHEN estado IN ('pendiente', 'confirmada') THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as inactivos
FROM compras
UNION ALL
SELECT 
    'paises' as tabla,
    COUNT(*) as total_registros,
    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivos
FROM paises
UNION ALL
SELECT 
    'provincias' as tabla,
    COUNT(*) as total_registros,
    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivos
FROM provincias
UNION ALL
SELECT 
    'ciudades' as tabla,
    COUNT(*) as total_registros,
    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivos
FROM ciudades;

-- Mostrar estructura de proveedores
DESCRIBE proveedores;

SELECT 'Estructura de base de datos actualizada correctamente!' as resultado;
