-- 🌍 SCRIPT DE UNIFICACIÓN GEOGRÁFICA
-- Expandir países para incluir hispanos + China + Japón

USE sistemadgestion5;

-- 1️⃣ INSERTAR PAÍSES FALTANTES
INSERT INTO paises (nombre, codigo_iso, codigo_telefono, activo) VALUES
-- Países hispanos faltantes
('Bolivia', 'BOL', '+591', 1),
('Colombia', 'COL', '+57', 1),
('Costa Rica', 'CRI', '+506', 1),
('Ecuador', 'ECU', '+593', 1),
('El Salvador', 'SLV', '+503', 1),
('Guatemala', 'GTM', '+502', 1),
('Honduras', 'HND', '+504', 1),
('México', 'MEX', '+52', 1),
('Nicaragua', 'NIC', '+505', 1),
('Panamá', 'PAN', '+507', 1),
('Paraguay', 'PRY', '+595', 1),
('Perú', 'PER', '+51', 1),
('República Dominicana', 'DOM', '+1', 1),
('Venezuela', 'VEN', '+58', 1),

-- Potencias comerciales adicionales
('Japón', 'JPN', '+81', 1),
('Francia', 'FRA', '+33', 1),
('Italia', 'ITA', '+39', 1),
('Alemania', 'DEU', '+49', 1)

ON DUPLICATE KEY UPDATE 
nombre = VALUES(nombre),
codigo_telefono = VALUES(codigo_telefono),
activo = 1;

-- 2️⃣ CORREGIR CARACTERES UTF-8 SI HAY PROBLEMAS
UPDATE paises SET nombre = 'España' WHERE nombre LIKE '%spa%' OR nombre LIKE '%Espa%';
UPDATE paises SET nombre = 'Córdoba' WHERE nombre LIKE '%C_rdoba%' OR nombre LIKE '%rdoba%';

-- 3️⃣ AGREGAR COLUMNAS FK A TABLA CLIENTES (MIGRACIÓN GRADUAL)
ALTER TABLE clientes 
ADD COLUMN pais_id INT(11) NULL AFTER pais,
ADD COLUMN provincia_id INT(11) NULL AFTER provincia,
ADD COLUMN ciudad_id INT(11) NULL AFTER ciudad;

-- 4️⃣ AGREGAR ÍNDICES Y FOREIGN KEYS
ALTER TABLE clientes 
ADD INDEX idx_pais_id (pais_id),
ADD INDEX idx_provincia_id (provincia_id),
ADD INDEX idx_ciudad_id (ciudad_id);

-- Foreign keys (opcional, comentado para evitar errores)
-- ALTER TABLE clientes ADD CONSTRAINT fk_clientes_pais FOREIGN KEY (pais_id) REFERENCES paises(id);
-- ALTER TABLE clientes ADD CONSTRAINT fk_clientes_provincia FOREIGN KEY (provincia_id) REFERENCES provincias(id);
-- ALTER TABLE clientes ADD CONSTRAINT fk_clientes_ciudad FOREIGN KEY (ciudad_id) REFERENCES ciudades(id);

-- 5️⃣ MIGRACIÓN BÁSICA DE DATOS EXISTENTES
UPDATE clientes c
JOIN paises p ON LOWER(c.pais) = LOWER(p.nombre)
SET c.pais_id = p.id
WHERE c.pais_id IS NULL AND c.pais IS NOT NULL;

-- Casos especiales comunes
UPDATE clientes SET pais_id = (SELECT id FROM paises WHERE nombre = 'Argentina') 
WHERE LOWER(pais) LIKE '%argent%' AND pais_id IS NULL;

UPDATE clientes SET pais_id = (SELECT id FROM paises WHERE nombre = 'España') 
WHERE LOWER(pais) LIKE '%espa%' AND pais_id IS NULL;

UPDATE clientes SET pais_id = (SELECT id FROM paises WHERE nombre = 'Estados Unidos') 
WHERE LOWER(pais) LIKE '%estados%' OR LOWER(pais) LIKE '%usa%' AND pais_id IS NULL;

-- 6️⃣ VERIFICACIÓN
SELECT 
    'PAÍSES TOTALES' as Tipo,
    COUNT(*) as Cantidad
FROM paises WHERE activo = 1

UNION ALL

SELECT 
    'PAÍSES CON CÓDIGO TELÉFONO' as Tipo,
    COUNT(*) as Cantidad  
FROM paises WHERE activo = 1 AND codigo_telefono IS NOT NULL AND codigo_telefono != ''

UNION ALL

SELECT 
    'CLIENTES CON PAÍS MIGRADO' as Tipo,
    COUNT(*) as Cantidad
FROM clientes WHERE pais_id IS NOT NULL

UNION ALL

SELECT 
    'CLIENTES SIN MIGRAR' as Tipo,
    COUNT(*) as Cantidad
FROM clientes WHERE pais_id IS NULL AND pais IS NOT NULL;

-- 7️⃣ MOSTRAR RESULTADO FINAL
SELECT 
    p.nombre as Pais,
    p.codigo_iso as ISO,
    p.codigo_telefono as Telefono,
    p.activo as Activo
FROM paises p 
ORDER BY 
    CASE 
        WHEN p.nombre = 'Argentina' THEN 1
        WHEN p.nombre = 'España' THEN 2
        WHEN p.nombre = 'México' THEN 3
        ELSE 4
    END,
    p.nombre;

-- 8️⃣ COMENTARIOS PARA IMPLEMENTACIÓN
/*
PRÓXIMOS PASOS DESPUÉS DE EJECUTAR ESTE SCRIPT:

1. 🔄 Modificar cliente_form.php:
   - Cambiar de array PHP a consulta BD
   - Usar mismo sistema que proveedores.php
   
2. 🎨 Unificar modales:
   - Aplicar mismo diseño a clientes
   - Sistema telefónico con 18 países
   
3. 🧪 Testing:
   - Verificar carga de países
   - Probar creación/edición
   - Validar migración datos
   
4. 📚 Documentación:
   - Actualizar manual sistema
   - Capacitar usuarios

PAÍSES DISPONIBLES TRAS MIGRACIÓN (18 total):
🇦🇷 Argentina, 🇪🇸 España, 🇲🇽 México, 🇨🇴 Colombia, 🇨🇱 Chile, 🇵🇪 Perú,
🇻🇪 Venezuela, 🇪🇨 Ecuador, 🇧🇴 Bolivia, 🇵🇾 Paraguay, 🇺🇾 Uruguay,
🇧🇷 Brasil, 🇺🇸 Estados Unidos, 🇨🇳 China, 🇯🇵 Japón,
🇫🇷 Francia, 🇮🇹 Italia, 🇩🇪 Alemania

TOTAL: Hispanos (11) + Comerciales (7) = 18 países estratégicos
*/
