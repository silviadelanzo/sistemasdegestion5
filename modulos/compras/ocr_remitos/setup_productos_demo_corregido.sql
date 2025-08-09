-- =====================================================
-- SETUP PRODUCTOS DEMO - CORREGIDO PARA CATEGORÍAS EXISTENTES
-- =====================================================

-- 1. INSERTAR PROVEEDORES DEMO
-- =====================================================

INSERT INTO proveedores (codigo, razon_social, nombre_comercial, cuit, direccion, ciudad, provincia, telefono, email, activo) VALUES
('PROV001', 'Distribuidora Central S.A.', 'Central Mayorista', '20-12345678-9', 'Av. Industrial 1250', 'Buenos Aires', 'CABA', '011-4567-8900', 'ventas@central.com.ar', 1),
('PROV002', 'Alimentos del Norte S.R.L.', 'Norte Alimentos', '30-87654321-2', 'Ruta 9 Km 45', 'Tucumán', 'Tucumán', '0381-456-7890', 'pedidos@nortealimentos.com', 1),
('PROV003', 'Limpieza Total S.A.', 'Clean Max', '20-11223344-5', 'Zona Industrial 890', 'Córdoba', 'Córdoba', '0351-987-6543', 'info@cleanmax.com.ar', 1),
('PROV004', 'Bebidas Premium S.R.L.', 'Premium Drinks', '30-55667788-1', 'Av. Libertador 2300', 'Rosario', 'Santa Fe', '0341-654-3210', 'ventas@premiumdrinks.com', 1),
('PROV005', 'Distribuidora Local S.A.', 'Local Express', '20-99887766-3', 'Calle Comercio 567', 'Mendoza', 'Mendoza', '0261-789-0123', 'contacto@localexpress.com', 1)
ON DUPLICATE KEY UPDATE activo = 1;

-- 2. INSERTAR PRODUCTOS DEMO - USANDO CATEGORÍAS EXISTENTES
-- =====================================================

-- CATEGORÍA 1: ELECTRÓNICA
INSERT INTO productos (codigo, nombre, descripcion, categoria_id, precio_compra, precio_venta, stock, stock_minimo, activo, codigo_barra, tipo_codigo_barra, proveedor_principal_id, codigo_proveedor) VALUES
('DEMO001', 'Cable USB-C 2m', 'Cable USB-C carga rápida datos alta velocidad resistente', 1, 485.50, 720.00, 150, 20, 1, '7790315001234', 'EAN13', 1, 'CAB001'),
('DEMO002', 'Auriculares Bluetooth', 'Auriculares inalámbricos bluetooth alta calidad sonido', 1, 2850.00, 4200.00, 45, 8, 1, '7790315001241', 'EAN13', 1, 'AUR001'),
('DEMO003', 'Cargador Celular 2A', 'Cargador universal celular 2 amperios carga rápida', 1, 680.00, 950.00, 80, 15, 1, '7790315001258', 'EAN13', 1, 'CAR001'),
('DEMO004', 'Pendrive 32GB', 'Memoria USB 32GB alta velocidad portátil datos', 1, 1250.00, 1850.00, 60, 10, 1, '7790315001265', 'EAN13', 1, 'PEN001'),
('DEMO005', 'Mouse Inalámbrico', 'Mouse wireless ergonómico oficina computadora portátil', 1, 890.00, 1350.00, 75, 12, 1, '7790315001272', 'EAN13', 1, 'MOU001'),
('DEMO006', 'Teclado USB', 'Teclado alámbirico USB oficina computadora escritorio', 1, 1680.00, 2450.00, 35, 6, 1, '7790315001289', 'EAN13', 1, 'TEC001'),
('DEMO007', 'Adaptador HDMI', 'Adaptador HDMI VGA convertidor video alta definición', 1, 750.00, 1150.00, 55, 8, 1, '7790315001296', 'EAN13', 1, 'ADA001'),
('DEMO008', 'Batería Portátil 10000mAh', 'Power bank carga portátil 10000mAh doble puerto USB', 1, 2380.00, 3450.00, 40, 6, 1, '7790315001303', 'EAN13', 1, 'BAT001'),

-- CATEGORÍA 2: OFICINA
('DEMO009', 'Resma Papel A4', 'Resma papel A4 500 hojas 75gr blanco impresión', 2, 1450.00, 1980.00, 120, 20, 1, '7790315002234', 'EAN13', 2, 'PAP001'),
('DEMO010', 'Carpeta 3 Anillos', 'Carpeta archivador 3 anillos A4 cartón reforzado', 2, 850.00, 1250.00, 85, 15, 1, '7790315002241', 'EAN13', 2, 'CAR002'),
('DEMO011', 'Bolígrafos x12', 'Bolígrafos azul tinta gel escritura suave oficina', 2, 680.00, 950.00, 150, 25, 1, '7790315002258', 'EAN13', 2, 'BOL001'),
('DEMO012', 'Marcadores x6', 'Marcadores fluorescentes colores surtidos oficina estudio', 2, 580.00, 850.00, 100, 15, 1, '7790315002265', 'EAN13', 2, 'MAR001'),
('DEMO013', 'Grapadora Metálica', 'Grapadora oficina metálica resistente 24/6 26/6', 2, 1180.00, 1750.00, 45, 8, 1, '7790315002272', 'EAN13', 2, 'GRA001'),
('DEMO014', 'Clips x100', 'Clips metálicos galvanizados oficina sujeción papeles', 2, 320.00, 480.00, 200, 30, 1, '7790315002289', 'EAN13', 2, 'CLI001'),
('DEMO015', 'Calculadora Científica', 'Calculadora científica funciones avanzadas estudiantes', 2, 2850.00, 4200.00, 25, 5, 1, '7790315002296', 'EAN13', 2, 'CAL001'),
('DEMO016', 'Agenda 2025', 'Agenda anillada 2025 planificador semanal mensual', 2, 950.00, 1450.00, 60, 10, 1, '7790315002303', 'EAN13', 2, 'AGE001'),

-- CATEGORÍA 3: MOBILIARIO
('DEMO017', 'Silla Oficina Giratoria', 'Silla oficina ergonómica giratoria altura regulable', 3, 28500.00, 42000.00, 15, 3, 1, '7790315003234', 'EAN13', 3, 'SIL001'),
('DEMO018', 'Escritorio 120cm', 'Escritorio melamina 120x60cm cajones oficina hogar', 3, 18500.00, 27500.00, 8, 2, 1, '7790315003241', 'EAN13', 3, 'ESC001'),
('DEMO019', 'Estantería 5 Estantes', 'Estantería metálica 5 estantes reforzada almacenamiento', 3, 8500.00, 12500.00, 12, 2, 1, '7790315003258', 'EAN13', 3, 'EST001'),
('DEMO020', 'Mesa Centro Vidrio', 'Mesa centro vidrio templado base metálica living', 3, 15800.00, 23500.00, 6, 1, 1, '7790315003265', 'EAN13', 3, 'MES001'),
('DEMO021', 'Biblioteca 6 Estantes', 'Biblioteca melamina 6 estantes libros decoración', 3, 12500.00, 18500.00, 10, 2, 1, '7790315003272', 'EAN13', 3, 'BIB001'),
('DEMO022', 'Sillón Ejecutivo Cuero', 'Sillón ejecutivo cuero ecológico respaldo alto oficina', 3, 38500.00, 56000.00, 5, 1, 1, '7790315003289', 'EAN13', 3, 'SIL002'),

-- CATEGORÍA 4: ELECTRODOMÉSTICOS
('DEMO023', 'Microondas 20L', 'Microondas 20 litros 700W digital timer cocina', 4, 28500.00, 42000.00, 12, 2, 1, '7790315004234', 'EAN13', 4, 'MIC001'),
('DEMO024', 'Licuadora 1.5L', 'Licuadora 1.5L 3 velocidades vaso vidrio reforzado', 4, 8500.00, 12500.00, 25, 4, 1, '7790315004241', 'EAN13', 4, 'LIC001'),
('DEMO025', 'Cafetera Eléctrica', 'Cafetera eléctrica 12 tazas filtro permanente auto', 4, 12500.00, 18500.00, 18, 3, 1, '7790315004258', 'EAN13', 4, 'CAF001'),
('DEMO026', 'Tostadora 2 Rebanadas', 'Tostadora 2 rebanadas 7 niveles tostado bandeja', 4, 6800.00, 9500.00, 30, 5, 1, '7790315004265', 'EAN13', 4, 'TOS001'),
('DEMO027', 'Ventilador Mesa 12"', 'Ventilador mesa 12 pulgadas 3 velocidades oscilante', 4, 5800.00, 8500.00, 35, 6, 1, '7790315004272', 'EAN13', 4, 'VEN001'),
('DEMO028', 'Plancha Vapor 1200W', 'Plancha vapor 1200W suela antiadherente regulable', 4, 4850.00, 7200.00, 40, 8, 1, '7790315004289', 'EAN13', 4, 'PLA001'),

-- CATEGORÍA 5: ACEITES
('DEMO029', 'Aceite Girasol 1.5L', 'Aceite girasol puro 1.5L primera presión frío cocina', 5, 680.00, 950.00, 80, 15, 1, '7790315005234', 'EAN13', 5, 'ACE001'),
('DEMO030', 'Aceite Oliva Extra Virgen', 'Aceite oliva extra virgen 500ml premium gourmet', 5, 1850.00, 2650.00, 45, 8, 1, '7790315005241', 'EAN13', 5, 'ACE002'),
('DEMO031', 'Aceite Mezcla 900ml', 'Aceite mezcla girasol maíz 900ml cocina familiar', 5, 580.00, 820.00, 100, 20, 1, '7790315005258', 'EAN13', 5, 'ACE003'),
('DEMO032', 'Aceite Coco 400ml', 'Aceite coco virgen 400ml natural saludable cocina', 5, 1480.00, 2150.00, 35, 6, 1, '7790315005265', 'EAN13', 5, 'ACE004'),
('DEMO033', 'Aceite Canola 1L', 'Aceite canola bajo colesterol 1L saludable cocina', 5, 750.00, 1100.00, 60, 10, 1, '7790315005272', 'EAN13', 5, 'ACE005'),

-- CATEGORÍA 6: ROPA
('DEMO034', 'Remera Algodón Talle M', 'Remera algodón 100% talle M manga corta unisex', 6, 1850.00, 2750.00, 50, 8, 1, '7790315006234', 'EAN13', 1, 'REM001'),
('DEMO035', 'Jean Clásico Talle 32', 'Jean clásico talle 32 corte recto azul stone wash', 6, 4850.00, 7200.00, 30, 5, 1, '7790315006241', 'EAN13', 1, 'JEA001'),
('DEMO036', 'Campera Abrigo Talle L', 'Campera abrigo talle L capucha forrada invierno', 6, 8500.00, 12500.00, 20, 4, 1, '7790315006258', 'EAN13', 1, 'CAM001'),
('DEMO037', 'Zapatillas Deportivas 42', 'Zapatillas deportivas talle 42 running comodidad', 6, 12500.00, 18500.00, 25, 4, 1, '7790315006265', 'EAN13', 1, 'ZAP001'),
('DEMO038', 'Medias x3 Pares', 'Medias algodón 3 pares surtidos cómodas transpirables', 6, 680.00, 950.00, 80, 15, 1, '7790315006272', 'EAN13', 1, 'MED001'),

-- CATEGORÍA 7: LADRILLOS
('DEMO039', 'Ladrillo Común x100', 'Ladrillos comunes 100 unidades construcción mampostería', 7, 8500.00, 12000.00, 500, 50, 1, '7790315007234', 'EAN13', 3, 'LAD001'),
('DEMO040', 'Ladrillo Hueco x50', 'Ladrillos huecos 50 unidades 18x18x33 construcción', 7, 6800.00, 9500.00, 300, 30, 1, '7790315007241', 'EAN13', 3, 'LAD002'),
('DEMO041', 'Cemento Portland 50kg', 'Cemento portland 50kg bolsa construcción mezcla', 7, 1850.00, 2650.00, 100, 15, 1, '7790315007258', 'EAN13', 3, 'CEM001'),
('DEMO042', 'Arena Fina x1m³', 'Arena fina metro cúbico construcción mezcla revoque', 7, 4500.00, 6500.00, 20, 3, 1, '7790315007265', 'EAN13', 3, 'ARE001'),
('DEMO043', 'Cal Hidratada 25kg', 'Cal hidratada 25kg bolsa construcción pintura albañil', 7, 850.00, 1250.00, 80, 12, 1, '7790315007272', 'EAN13', 3, 'CAL001'),

-- PRODUCTOS ADICIONALES MEZCLADOS
('DEMO044', 'Monitor LED 24"', 'Monitor LED 24 pulgadas Full HD HDMI oficina gaming', 1, 45000.00, 65000.00, 15, 3, 1, '7790315001310', 'EAN13', 1, 'MON001'),
('DEMO045', 'Impresora Multifunción', 'Impresora multifunción WiFi copia escanea imprime', 2, 28500.00, 42000.00, 8, 2, 1, '7790315002310', 'EAN13', 2, 'IMP001'),
('DEMO046', 'Cajonera 4 Cajones', 'Cajonera móvil 4 cajones oficina melamina blanca', 3, 8500.00, 12500.00, 12, 2, 1, '7790315003296', 'EAN13', 3, 'CAJ001'),
('DEMO047', 'Heladera 265L', 'Heladera no frost 265L freezer ahorro energético', 4, 185000.00, 265000.00, 3, 1, 1, '7790315004296', 'EAN13', 4, 'HEL001'),
('DEMO048', 'Aceite Motor 15W40', 'Aceite motor 15W40 4L mineral lubricante automotor', 5, 2850.00, 4200.00, 40, 6, 1, '7790315005289', 'EAN13', 5, 'ACE006'),
('DEMO049', 'Overol Trabajo Talle L', 'Overol trabajo talle L grafa reforzado industrial', 6, 4850.00, 7200.00, 25, 4, 1, '7790315006289', 'EAN13', 1, 'OVE001'),
('DEMO050', 'Hierro 8mm x12m', 'Hierro construcción 8mm barra 12 metros estructural', 7, 3850.00, 5500.00, 60, 10, 1, '7790315007289', 'EAN13', 3, 'HIE001')
ON DUPLICATE KEY UPDATE activo = 1;

-- 3. ACTUALIZAR ESTADÍSTICAS
-- =====================================================
UPDATE productos SET 
    fecha_modificacion = NOW(),
    modificado_por = 1 
WHERE codigo LIKE 'DEMO%';

-- 4. VERIFICAR INSERCIÓN
-- =====================================================
SELECT 
    COUNT(*) as total_productos_demo,
    COUNT(CASE WHEN codigo_barra IS NOT NULL THEN 1 END) as con_codigo_barra,
    COUNT(CASE WHEN proveedor_principal_id IS NOT NULL THEN 1 END) as con_proveedor
FROM productos 
WHERE codigo LIKE 'DEMO%';

-- 5. MOSTRAR RESUMEN POR CATEGORÍA
-- =====================================================
SELECT 
    c.nombre as categoria,
    COUNT(p.id) as productos,
    MIN(p.precio_compra) as precio_min,
    MAX(p.precio_compra) as precio_max,
    SUM(p.stock) as stock_total
FROM categorias c
LEFT JOIN productos p ON c.id = p.categoria_id
WHERE p.codigo LIKE 'DEMO%'
GROUP BY c.id, c.nombre
ORDER BY productos DESC;

-- =====================================================
-- SETUP COMPLETADO - 50 PRODUCTOS DEMO ADAPTADOS
-- =====================================================
