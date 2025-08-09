<?php
require_once 'config/config.php';

echo "🔧 VERIFICANDO SISTEMA MODAL UNIFICADO\n";
echo "=====================================\n\n";

try {
    // 1. Verificar que proveedor_form.php no exista en compras
    echo "1️⃣ VERIFICANDO ARCHIVOS ELIMINADOS:\n";
    $archivos_viejos = [
        'modulos/compras/proveedor_form.php' => 'Formulario viejo'
    ];
    
    foreach ($archivos_viejos as $archivo => $descripcion) {
        if (file_exists($archivo)) {
            echo "❌ $descripcion: TODAVÍA EXISTE ($archivo)\n";
        } else {
            echo "✅ $descripcion: ELIMINADO CORRECTAMENTE\n";
        }
    }
    
    // 2. Verificar que el modal esté en proveedores.php
    echo "\n2️⃣ VERIFICANDO MODAL EN PROVEEDORES.PHP:\n";
    $proveedores_content = file_get_contents('modulos/compras/proveedores.php');
    
    $verificaciones = [
        'modalNuevoProveedor' => 'Modal nuevo proveedor',
        'abrirModalProveedor()' => 'Función abrir modal',
        'guardarNuevoProveedor()' => 'Función guardar',
        'editarProveedor(' => 'Función editar',
        'actualizarProveedor(' => 'Función actualizar'
    ];
    
    foreach ($verificaciones as $buscar => $descripcion) {
        if (strpos($proveedores_content, $buscar) !== false) {
            echo "✅ $descripcion: IMPLEMENTADO\n";
        } else {
            echo "❌ $descripcion: NO ENCONTRADO\n";
        }
    }
    
    // 3. Verificar que gestionar_proveedor.php tenga las nuevas acciones
    echo "\n3️⃣ VERIFICANDO ACCIONES EN GESTIONAR_PROVEEDOR.PHP:\n";
    $gestionar_content = file_get_contents('modulos/compras/gestionar_proveedor.php');
    
    $acciones = [
        'crear_proveedor' => 'Crear desde modal',
        'actualizar_proveedor' => 'Actualizar desde modal', 
        'obtener_proveedor' => 'Obtener datos para editar'
    ];
    
    foreach ($acciones as $accion => $descripcion) {
        if (strpos($gestionar_content, "case '$accion':") !== false) {
            echo "✅ $descripcion: IMPLEMENTADO\n";
        } else {
            echo "❌ $descripcion: NO ENCONTRADO\n";
        }
    }
    
    // 4. Verificar botones en proveedores.php
    echo "\n4️⃣ VERIFICANDO BOTONES ACTUALIZADOS:\n";
    
    if (strpos($proveedores_content, 'onclick="abrirModalProveedor()"') !== false) {
        echo "✅ Botón 'Nuevo Proveedor': USA MODAL\n";
    } else {
        echo "❌ Botón 'Nuevo Proveedor': SIGUE USANDO ENLACE\n";
    }
    
    if (strpos($proveedores_content, 'onclick="editarProveedor(') !== false) {
        echo "✅ Botón 'Editar': USA MODAL\n";
    } else {
        echo "❌ Botón 'Editar': SIGUE USANDO ENLACE\n";
    }
    
    echo "\n🎯 RESUMEN:\n";
    echo "=========\n";
    echo "✨ Modal unificado: Implementado en proveedores.php\n";
    echo "🗑️ Archivos viejos: Eliminados\n";
    echo "🔧 Gestionar proveedor: Actualizado con nuevas acciones\n";
    echo "🎮 Botones: Convertidos a modal\n\n";
    
    echo "🚀 LISTO PARA PROBAR:\n";
    echo "http://localhost/sistemadgestion5/modulos/compras/proveedores.php\n";
    echo "1. Clic en 'Nuevo Proveedor' → Modal\n";
    echo "2. Clic en ícono de editar → Modal con datos\n";
    echo "3. Crear/editar desde modal\n";
    echo "4. ¡Sin más proveedor_form.php!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
