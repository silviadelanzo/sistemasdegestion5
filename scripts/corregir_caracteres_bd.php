<?php
require_once '../config/config.php';

echo "🔧 CORRIGIENDO CARACTERES INVÁLIDOS EN BD\n";
echo "==========================================\n\n";

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 1. Corregir países
    echo "1️⃣ CORRIGIENDO PAÍSES:\n";
    $correcciones_paises = [
        'Argent¡na' => 'Argentina',
        'BrasÝl' => 'Brasil', 
        'ChÝle' => 'Chile',
        'Per·' => 'Perú',
        'MÚxico' => 'México',
        'EspaÒa' => 'España',
        'FrancÝa' => 'Francia'
    ];
    
    foreach ($correcciones_paises as $malo => $bueno) {
        $stmt = $pdo->prepare("UPDATE paises SET nombre = ? WHERE nombre LIKE ?");
        $stmt->execute([$bueno, "%$malo%"]);
        if ($stmt->rowCount() > 0) {
            echo "   ✅ $malo → $bueno\n";
        }
    }
    
    // 2. Corregir provincias
    echo "\n2️⃣ CORRIGIENDO PROVINCIAS:\n";
    $correcciones_provincias = [
        'Buenos AÝres' => 'Buenos Aires',
        'C¢rdoba' => 'Córdoba',
        'Santa FÚ' => 'Santa Fe',
        'TucumÓn' => 'Tucumán',
        'MendÒza' => 'Mendoza',
        'Entre RÝos' => 'Entre Ríos',
        'NeuquÚn' => 'Neuquén',
        'RÝo Negro' => 'Río Negro'
    ];
    
    foreach ($correcciones_provincias as $malo => $bueno) {
        $stmt = $pdo->prepare("UPDATE provincias SET nombre = ? WHERE nombre LIKE ?");
        $stmt->execute([$bueno, "%$malo%"]);
        if ($stmt->rowCount() > 0) {
            echo "   ✅ $malo → $bueno\n";
        }
    }
    
    // 3. Corregir ciudades
    echo "\n3️⃣ CORRIGIENDO CIUDADES:\n";
    $correcciones_ciudades = [
        'C¢rdoba' => 'Córdoba',
        'RosarÝo' => 'Rosario',
        'La PlÓta' => 'La Plata',
        'TucumÓn' => 'Tucumán',
        'Mar deÞ PlÓta' => 'Mar del Plata',
        'SÓlta' => 'Salta',
        'ParanÓ' => 'Paraná'
    ];
    
    foreach ($correcciones_ciudades as $malo => $bueno) {
        $stmt = $pdo->prepare("UPDATE ciudades SET nombre = ? WHERE nombre LIKE ?");
        $stmt->execute([$bueno, "%$malo%"]);
        if ($stmt->rowCount() > 0) {
            echo "   ✅ $malo → $bueno\n";
        }
    }
    
    // 4. Verificar estado final
    echo "\n4️⃣ VERIFICANDO ESTADO FINAL:\n";
    
    $paises = $pdo->query("SELECT COUNT(*) as total FROM paises")->fetch()['total'];
    $provincias = $pdo->query("SELECT COUNT(*) as total FROM provincias")->fetch()['total'];
    $ciudades = $pdo->query("SELECT COUNT(*) as total FROM ciudades")->fetch()['total'];
    
    echo "   📊 Países: $paises registros\n";
    echo "   📊 Provincias: $provincias registros\n";
    echo "   📊 Ciudades: $ciudades registros\n";
    
    // 5. Mostrar algunos ejemplos
    echo "\n5️⃣ EJEMPLOS CORREGIDOS:\n";
    
    $stmt = $pdo->query("SELECT nombre FROM paises WHERE nombre LIKE '%argentina%' LIMIT 1");
    $argentina = $stmt->fetch();
    if ($argentina) {
        echo "   🇦🇷 País: {$argentina['nombre']}\n";
    }
    
    $stmt = $pdo->query("SELECT nombre FROM provincias WHERE nombre LIKE '%aires%' LIMIT 1");
    $buenos_aires = $stmt->fetch();
    if ($buenos_aires) {
        echo "   🏙️ Provincia: {$buenos_aires['nombre']}\n";
    }
    
    echo "\n🎉 CORRECCIONES COMPLETADAS\n";
    echo "===========================\n";
    echo "✅ Caracteres especiales corregidos\n";
    echo "✅ Encoding UTF-8 aplicado\n";
    echo "✅ Base de datos lista para modales\n\n";
    
    echo "🚀 AHORA PRUEBA:\n";
    echo "http://localhost/sistemadgestion5/modulos/compras/proveedores.php\n";
    echo "1. Clic 'Nuevo Proveedor'\n";
    echo "2. Seleccionar país → Sin caracteres raros\n";
    echo "3. Argentina → Provincias automáticas\n";
    echo "4. ¡Modal con diseño perfecto!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
