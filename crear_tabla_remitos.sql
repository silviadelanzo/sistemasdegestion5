-- Crear tabla principal de remitos
CREATE TABLE IF NOT EXISTS remitos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    numero_remito_proveedor VARCHAR(100),
    proveedor_id INT NOT NULL,
    fecha_entrega DATE NOT NULL,
    estado ENUM('borrador', 'confirmado', 'recibido') DEFAULT 'borrador',
    observaciones TEXT,
    usuario_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Crear tabla de detalles de remitos
CREATE TABLE IF NOT EXISTS remito_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remito_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    observaciones TEXT,
    FOREIGN KEY (remito_id) REFERENCES remitos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Índices para mejorar rendimiento
CREATE INDEX idx_remitos_codigo ON remitos(codigo);
CREATE INDEX idx_remitos_proveedor ON remitos(proveedor_id);
CREATE INDEX idx_remitos_fecha ON remitos(fecha_entrega);
CREATE INDEX idx_remitos_estado ON remitos(estado);
CREATE INDEX idx_remito_detalles_remito ON remito_detalles(remito_id);
