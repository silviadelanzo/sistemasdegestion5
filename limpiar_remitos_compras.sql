-- Script para limpiar remitos de la tabla compras
-- Solo eliminar registros que tengan códigos tipo REMI-XXXXXX

-- Ver los remitos que están en compras
SELECT codigo, proveedor_id, fecha_entrega_estimada, estado 
FROM compras 
WHERE codigo LIKE 'REMI-%';

-- Eliminar los detalles primero (por la clave foránea)
DELETE FROM compra_detalles 
WHERE compra_id IN (
    SELECT id FROM compras WHERE codigo LIKE 'REMI-%'
);

-- Eliminar los remitos de la tabla compras
DELETE FROM compras 
WHERE codigo LIKE 'REMI-%';

-- Verificar que se eliminaron
SELECT COUNT(*) as remitos_restantes 
FROM compras 
WHERE codigo LIKE 'REMI-%';
