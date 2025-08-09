-- =====================================================
-- SETUP COMPLETO PARA DEMO OCR - PRODUCTOS Y PROVEEDORES
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

-- 2. LIMPIAR PRODUCTOS EXISTENTES (OPCIONAL)
-- =====================================================
-- DELETE FROM productos WHERE codigo LIKE 'DEMO%';

-- 3. INSERTAR PRODUCTOS DEMO CON CÓDIGOS DE BARRAS REALISTAS
-- =====================================================

-- CATEGORIA: ALMACÉN Y DESPENSA
INSERT INTO productos (codigo, nombre, descripcion, categoria_id, precio_compra, precio_venta, stock, stock_minimo, activo, codigo_barra, tipo_codigo_barra, proveedor_principal_id, codigo_proveedor) VALUES
('DEMO001', 'Arroz Largo Fino 1kg', 'Arroz blanco largo fino premium calidad extra', 1, 85.50, 120.00, 150, 20, 1, '7790315001234', 'EAN13', 1, 'ARR001'),
('DEMO002', 'Fideos Spaghetti 500g', 'Pasta spaghetti trigo candeal cocción perfecta', 1, 95.00, 135.00, 200, 25, 1, '7790315001241', 'EAN13', 1, 'FID001'),
('DEMO003', 'Aceite Girasol 900ml', 'Aceite puro de girasol primera presión en frío', 1, 180.00, 245.00, 80, 15, 1, '7790315001258', 'EAN13', 2, 'ACE001'),
('DEMO004', 'Azúcar Común 1kg', 'Azúcar blanca refinada especial dulce cristalizada', 1, 75.00, 105.00, 120, 20, 1, '7790315001265', 'EAN13', 1, 'AZU001'),
('DEMO005', 'Sal Fina 500g', 'Sal marina fina refinada mesa iodada enriquecida', 1, 45.00, 68.00, 180, 30, 1, '7790315001272', 'EAN13', 1, 'SAL001'),
('DEMO006', 'Harina 0000 1kg', 'Harina de trigo 0000 panadería repostería premium', 1, 95.00, 128.00, 100, 15, 1, '7790315001289', 'EAN13', 2, 'HAR001'),
('DEMO007', 'Lentejas 500g', 'Lentejas secas seleccionadas cocción rápida nutritivas', 1, 120.00, 165.00, 90, 12, 1, '7790315001296', 'EAN13', 2, 'LEN001'),
('DEMO008', 'Atún en Lata 170g', 'Atún natural lomitos aceite girasol conserva gourmet', 1, 195.00, 275.00, 150, 20, 1, '7790315001303', 'EAN13', 1, 'ATU001'),

-- CATEGORIA: LIMPIEZA E HIGIENE
('DEMO009', 'Detergente Líquido 750ml', 'Detergente concentrado ropa colores blancos enzimas', 2, 145.00, 198.00, 75, 12, 1, '7790315002234', 'EAN13', 3, 'DET001'),
('DEMO010', 'Jabón en Polvo 800g', 'Jabón polvo ropa lavado perfecto quitamanchas activo', 2, 165.00, 225.00, 60, 10, 1, '7790315002241', 'EAN13', 3, 'JAB001'),
('DEMO011', 'Lavandina 1L', 'Lavandina concentrada desinfectante blanqueador cloro', 2, 88.00, 125.00, 100, 15, 1, '7790315002258', 'EAN13', 3, 'LAV001'),
('DEMO012', 'Limpiador Multiuso 500ml', 'Limpiador multiuso desengrasante bactericida hogar', 2, 95.00, 135.00, 85, 12, 1, '7790315002265', 'EAN13', 3, 'LIM001'),
('DEMO013', 'Paper Higiénico 4u', 'Papel higiénico doble hoja suave resistente familiar', 2, 235.00, 320.00, 50, 8, 1, '7790315002272', 'EAN13', 3, 'PAP001'),
('DEMO014', 'Servilletas x100', 'Servilletas descartables absorción rápida resistentes', 2, 85.00, 115.00, 120, 20, 1, '7790315002289', 'EAN13', 3, 'SER001'),

-- CATEGORIA: BEBIDAS Y REFRESCOS
('DEMO015', 'Gaseosa Cola 2.25L', 'Bebida cola gasificada sabor original familiar grande', 3, 195.00, 285.00, 45, 8, 1, '7790315003234', 'EAN13', 4, 'GAS001'),
('DEMO016', 'Agua Mineral 2L', 'Agua mineral natural sin gas pureza cristalina fuente', 3, 125.00, 175.00, 80, 15, 1, '7790315003241', 'EAN13', 4, 'AGU001'),
('DEMO017', 'Jugo Naranja 1L', 'Jugo natural naranja exprimida vitamina C sin conservantes', 3, 165.00, 235.00, 35, 6, 1, '7790315003258', 'EAN13', 4, 'JUG001'),
('DEMO018', 'Cerveza Lata 473ml', 'Cerveza rubia lager premium malta lúpulo seleccionado', 3, 145.00, 215.00, 120, 20, 1, '7790315003265', 'EAN13', 4, 'CER001'),
('DEMO019', 'Vino Tinto 750ml', 'Vino tinto reserva malbec cosecha seleccionada bodega', 3, 385.00, 525.00, 25, 5, 1, '7790315003272', 'EAN13', 4, 'VIN001'),
('DEMO020', 'Energizante 250ml', 'Bebida energizante cafeína taurina guaraná rendimiento', 3, 125.00, 185.00, 60, 10, 1, '7790315003289', 'EAN13', 4, 'ENE001'),

-- CATEGORIA: SNACKS Y GOLOSINAS
('DEMO021', 'Papas Fritas 150g', 'Papas fritas clásicas sal marina crujientes naturales', 4, 95.00, 145.00, 75, 12, 1, '7790315004234', 'EAN13', 5, 'PAP002'),
('DEMO022', 'Chocolate c/Leche 100g', 'Chocolate leche cremoso cacao premium tableta dulce', 4, 165.00, 235.00, 90, 15, 1, '7790315004241', 'EAN13', 5, 'CHO001'),
('DEMO023', 'Galletitas Dulces 200g', 'Galletitas dulces vainilla merienda familia crujientes', 4, 125.00, 175.00, 110, 18, 1, '7790315004258', 'EAN13', 5, 'GAL001'),
('DEMO024', 'Caramelos Duros x50', 'Caramelos duros frutales surtidos colores sabores', 4, 85.00, 125.00, 150, 25, 1, '7790315004265', 'EAN13', 5, 'CAR001'),
('DEMO025', 'Chicles s/Azúcar x20', 'Chicles sin azúcar menta fresca aliento duradero', 4, 95.00, 135.00, 80, 12, 1, '7790315004272', 'EAN13', 5, 'CHI001'),

-- CATEGORIA: FIAMBRES Y LÁCTEOS
('DEMO026', 'Jamón Cocido 200g', 'Jamón cocido magro premium feteado delicatessen calidad', 5, 385.00, 525.00, 35, 6, 1, '7790315005234', 'EAN13', 2, 'JAM001'),
('DEMO027', 'Queso Cremoso 300g', 'Queso cremoso untable suave textura cremosa familiar', 5, 295.00, 415.00, 40, 8, 1, '7790315005241', 'EAN13', 2, 'QUE001'),
('DEMO028', 'Salame Milan 150g', 'Salame tipo milán condimentado curado tradicional', 5, 465.00, 625.00, 25, 5, 1, '7790315005258', 'EAN13', 2, 'SAL002'),
('DEMO029', 'Leche Entera 1L', 'Leche entera pasteurizada vitaminas calcio proteínas', 5, 185.00, 265.00, 60, 10, 1, '7790315005265', 'EAN13', 2, 'LEC001'),
('DEMO030', 'Yogur Natural 200g', 'Yogur natural cremoso probióticos digestión saludable', 5, 95.00, 135.00, 80, 15, 1, '7790315005272', 'EAN13', 2, 'YOG001'),

-- CATEGORIA: VERDURAS Y FRUTAS ENVASADAS
('DEMO031', 'Tomate Lata 400g', 'Tomate perita entero conserva natural cocción listo', 6, 125.00, 175.00, 95, 15, 1, '7790315006234', 'EAN13', 1, 'TOM001'),
('DEMO032', 'Arvejas Lata 300g', 'Arvejas tiernas conserva natural verdura lista consumo', 6, 115.00, 165.00, 85, 12, 1, '7790315006241', 'EAN13', 1, 'ARV001'),
('DEMO033', 'Choclo Lata 300g', 'Choclo desgranado dulce conserva natural granos tiernos', 6, 135.00, 185.00, 70, 10, 1, '7790315006258', 'EAN13', 1, 'CHO002'),
('DEMO034', 'Duraznos Lata 820g', 'Duraznos mitades almíbar liviano fruta conserva dulce', 6, 185.00, 265.00, 45, 8, 1, '7790315006265', 'EAN13', 1, 'DUR001'),

-- CATEGORIA: KIOSCO Y CIGARRILLOS
('DEMO035', 'Cigarrillos Box x20', 'Cigarrillos rubios suaves filtro caja tradicional', 7, 485.00, 685.00, 30, 5, 1, '7790315007234', 'EAN13', 5, 'CIG001'),
('DEMO036', 'Encendedor Común', 'Encendedor desechable butano llama regulable seguro', 7, 165.00, 235.00, 100, 20, 1, '7790315007241', 'EAN13', 5, 'ENC001'),
('DEMO037', 'Pilas AA x2', 'Pilas alcalinas AA larga duración dispositivos electrónicos', 7, 195.00, 285.00, 75, 12, 1, '7790315007258', 'EAN13', 5, 'PIL001'),
('DEMO038', 'Tarjeta Celular $500', 'Tarjeta prepaga recarga celular crédito telefonía móvil', 7, 485.00, 500.00, 50, 10, 1, '7790315007265', 'EAN13', 5, 'TAR001'),

-- CATEGORIA: FARMACIA Y SALUD
('DEMO039', 'Ibuprofeno 400mg x20', 'Ibuprofeno analgésico antiinflamatorio dolor comprimidos', 8, 285.00, 395.00, 40, 8, 1, '7790315008234', 'EAN13', 3, 'IBU001'),
('DEMO040', 'Alcohol en Gel 250ml', 'Alcohol gel desinfectante manos bactericida antiséptico', 8, 125.00, 185.00, 65, 10, 1, '7790315008241', 'EAN13', 3, 'ALC001'),
('DEMO041', 'Vendas Elásticas x3', 'Vendas elásticas adhesivas primeros auxilios lesiones', 8, 195.00, 285.00, 35, 6, 1, '7790315008258', 'EAN13', 3, 'VEN001'),
('DEMO042', 'Termómetro Digital', 'Termómetro digital fiebre medición rápida precisa', 8, 485.00, 685.00, 20, 4, 1, '7790315008265', 'EAN13', 3, 'TER001'),

-- CATEGORIA: PRODUCTOS ESPECIALES
('DEMO043', 'Carbón Vegetal 3kg', 'Carbón vegetal asado parrilla alta calidad combustión', 9, 385.00, 525.00, 25, 5, 1, '7790315009234', 'EAN13', 5, 'CAR002'),
('DEMO044', 'Hielo Seco 2kg', 'Hielo artificial eventos fiestas bebidas enfriamiento', 9, 285.00, 395.00, 15, 3, 1, '7790315009241', 'EAN13', 5, 'HIE001'),
('DEMO045', 'Velas Aromáticas x6', 'Velas aromáticas relajación decoración fragancia ambiente', 9, 195.00, 285.00, 45, 8, 1, '7790315009258', 'EAN13', 5, 'VEL001'),

-- PRODUCTOS ADICIONALES PARA MAYOR VARIEDAD
('DEMO046', 'Café Molido 250g', 'Café molido tostado intenso aroma desayuno merienda', 1, 225.00, 315.00, 55, 10, 1, '7790315001310', 'EAN13', 2, 'CAF001'),
('DEMO047', 'Mermelada Frutilla 400g', 'Mermelada frutilla casera desayuno tostadas dulce', 1, 165.00, 235.00, 70, 12, 1, '7790315001327', 'EAN13', 2, 'MER001'),
('DEMO048', 'Mayonesa 500g', 'Mayonesa cremosa huevos frescos condimento sandwich', 1, 185.00, 265.00, 85, 15, 1, '7790315001334', 'EAN13', 1, 'MAY001'),
('DEMO049', 'Ketchup 500g', 'Salsa ketchup tomate condimento hamburguesa papas', 1, 175.00, 245.00, 90, 15, 1, '7790315001341', 'EAN13', 1, 'KET001'),
('DEMO050', 'Mostaza 250g', 'Mostaza dijon picante condimento carnes gourmet', 1, 145.00, 205.00, 65, 10, 1, '7790315001358', 'EAN13', 1, 'MOS001')
ON DUPLICATE KEY UPDATE activo = 1;

-- 4. ACTUALIZAR ESTADÍSTICAS
-- =====================================================
UPDATE productos SET 
    fecha_modificacion = NOW(),
    modificado_por = 1 
WHERE codigo LIKE 'DEMO%';

-- 5. VERIFICAR INSERCIÓN
-- =====================================================
SELECT 
    COUNT(*) as total_productos_demo,
    COUNT(CASE WHEN codigo_barra IS NOT NULL THEN 1 END) as con_codigo_barra,
    COUNT(CASE WHEN proveedor_principal_id IS NOT NULL THEN 1 END) as con_proveedor
FROM productos 
WHERE codigo LIKE 'DEMO%';

-- 6. MOSTRAR RESUMEN POR PROVEEDOR
-- =====================================================
SELECT 
    p.razon_social as proveedor,
    COUNT(pr.id) as productos,
    MIN(pr.precio_compra) as precio_min,
    MAX(pr.precio_compra) as precio_max,
    SUM(pr.stock) as stock_total
FROM proveedores p
LEFT JOIN productos pr ON p.id = pr.proveedor_principal_id
WHERE pr.codigo LIKE 'DEMO%'
GROUP BY p.id, p.razon_social
ORDER BY productos DESC;

-- =====================================================
-- SETUP COMPLETADO - 50 PRODUCTOS DEMO LISTOS PARA OCR
-- =====================================================
-- Productos creados: 50
-- Proveedores: 5 
-- Códigos de barra: EAN-13 realistas
-- Categorías: 9 diferentes
-- Precios: Variables realistas
-- Stock: Niveles variables para demo
-- =====================================================
