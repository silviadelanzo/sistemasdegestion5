-- Actualización de tabla productos para simulación
-- Ejecutar en phpMyAdmin

-- Agregar columnas para simulación si no existen
ALTER TABLE productos 
ADD COLUMN IF NOT EXISTS codigo_barras VARCHAR(20) NULL AFTER precio_venta,
ADD COLUMN IF NOT EXISTS categoria_simulacion VARCHAR(50) NULL AFTER codigo_barras,
ADD COLUMN IF NOT EXISTS es_simulacion BOOLEAN DEFAULT 0 AFTER categoria_simulacion,
ADD COLUMN IF NOT EXISTS fecha_actualizacion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER fecha_creacion;

-- Crear índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_codigo_barras ON productos(codigo_barras);
CREATE INDEX IF NOT EXISTS idx_es_simulacion ON productos(es_simulacion);
CREATE INDEX IF NOT EXISTS idx_categoria_simulacion ON productos(categoria_simulacion);

-- Mensaje de confirmación
SELECT 'Tabla productos actualizada para simulación OCR' as mensaje;
