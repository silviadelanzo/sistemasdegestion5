<?php
require_once 'config/config.php';

echo "<h1>🏗️ Generador de Remitos de Prueba</h1>";
echo "<p>Este script genera remitos falsos para testing del sistema OCR</p>";

try {
    $pdo = conectarDB();

    // Verificar/crear proveedores de prueba
    $proveedores_test = [
        [
            'id' => null,
            'razon_social' => 'Distribuidora Central Mayorista',
            'nombre_comercial' => 'Central Mayorista',
            'cuit' => '20-12345678-9',
            'direccion' => 'Av. Corrientes 1234, CABA',
            'telefono' => '011-4567-8901',
            'email' => 'ventas@centralmayorista.com.ar'
        ],
        [
            'id' => null,
            'razon_social' => 'Tecnología Avanzada S.R.L.',
            'nombre_comercial' => 'TechShop',
            'cuit' => '30-98765432-1',
            'direccion' => 'San Martín 567, Córdoba',
            'telefono' => '0351-123-4567',
            'email' => 'info@techshop.com.ar'
        ],
        [
            'id' => null,
            'razon_social' => 'Alimentos del Norte S.A.',
            'nombre_comercial' => 'Norte Alimentos',
            'cuit' => '30-11223344-5',
            'direccion' => 'Ruta 9 Km 45, Tucumán',
            'telefono' => '0381-555-0123',
            'email' => 'pedidos@nortealimentos.com'
        ],
        [
            'id' => null,
            'razon_social' => 'Ferretería Industrial Oeste',
            'nombre_comercial' => 'Ferro Oeste',
            'cuit' => '27-55667788-3',
            'direccion' => 'Libertador 890, Mendoza',
            'telefono' => '0261-444-5566',
            'email' => 'ventas@ferrooeste.com.ar'
        ],
        [
            'id' => null,
            'razon_social' => 'Papelera Comercial del Sur',
            'nombre_comercial' => 'Papel Sur',
            'cuit' => '33-77889900-7',
            'direccion' => 'Mitre 234, Bahía Blanca',
            'telefono' => '0291-666-7788',
            'email' => 'info@papelsur.com'
        ]
    ];

    echo "<h3>📋 Verificando/Creando Proveedores de Prueba</h3>";

    // Verificar si existe tabla proveedores
    $stmt = $pdo->query("SHOW TABLES LIKE 'proveedores'");
    if (!$stmt->fetch()) {
        echo "<p style='color: orange;'>⚠️ Tabla 'proveedores' no existe. Creando...</p>";

        $sql_create = "
        CREATE TABLE `proveedores` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `razon_social` varchar(255) NOT NULL,
          `nombre_comercial` varchar(255) DEFAULT NULL,
          `cuit` varchar(15) DEFAULT NULL,
          `direccion` text,
          `telefono` varchar(50) DEFAULT NULL,
          `email` varchar(100) DEFAULT NULL,
          `activo` tinyint(1) NOT NULL DEFAULT 1,
          `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `cuit` (`cuit`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $pdo->exec($sql_create);
        echo "<p style='color: green;'>✅ Tabla 'proveedores' creada</p>";
    }

    // Insertar/verificar proveedores
    foreach ($proveedores_test as &$proveedor) {
        $stmt = $pdo->prepare("SELECT id FROM proveedores WHERE cuit = ?");
        $stmt->execute([$proveedor['cuit']]);
        $existing = $stmt->fetch();

        if ($existing) {
            $proveedor['id'] = $existing['id'];
            echo "<p>✅ Proveedor ya existe: {$proveedor['razon_social']} (ID: {$proveedor['id']})</p>";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO proveedores (razon_social, nombre_comercial, cuit, direccion, telefono, email, activo) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $proveedor['razon_social'],
                $proveedor['nombre_comercial'],
                $proveedor['cuit'],
                $proveedor['direccion'],
                $proveedor['telefono'],
                $proveedor['email']
            ]);
            $proveedor['id'] = $pdo->lastInsertId();
            echo "<p style='color: green;'>✅ Proveedor creado: {$proveedor['razon_social']} (ID: {$proveedor['id']})</p>";
        }
    }

    echo "<h3>📄 Generando Remitos PDF y JPG</h3>";

    // Datos de productos para diferentes tipos de remitos
    $productos_por_tipo = [
        'electronica' => [
            ['codigo' => 'TECH001', 'descripcion' => 'Smartphone Samsung Galaxy A54', 'cantidad' => 25, 'precio' => 350.00],
            ['codigo' => 'TECH002', 'descripcion' => 'Tablet iPad Air 10.9"', 'cantidad' => 12, 'precio' => 599.00],
            ['codigo' => 'TECH003', 'descripcion' => 'Auriculares Bluetooth Sony', 'cantidad' => 50, 'precio' => 89.99],
            ['codigo' => 'TECH004', 'descripcion' => 'Cargador Inalámbrico Rápido', 'cantidad' => 30, 'precio' => 45.50]
        ],
        'alimentos' => [
            ['codigo' => 'ALI001', 'descripcion' => 'Aceite de Girasol 900ml', 'cantidad' => 100, 'precio' => 2.50],
            ['codigo' => 'ALI002', 'descripcion' => 'Harina de Trigo 000 1kg', 'cantidad' => 200, 'precio' => 1.80],
            ['codigo' => 'ALI003', 'descripcion' => 'Azúcar Refinada 1kg', 'cantidad' => 150, 'precio' => 1.20],
            ['codigo' => 'ALI004', 'descripcion' => 'Fideos Spaghetti 500g', 'cantidad' => 80, 'precio' => 1.50],
            ['codigo' => 'ALI005', 'descripcion' => 'Conserva Tomate 400g', 'cantidad' => 120, 'precio' => 2.20]
        ],
        'ferreteria' => [
            ['codigo' => 'FER001', 'descripcion' => 'Taladro Percutor Black&Decker', 'cantidad' => 5, 'precio' => 125.00],
            ['codigo' => 'FER002', 'descripcion' => 'Juego Destornilladores 12 pzas', 'cantidad' => 15, 'precio' => 35.50],
            ['codigo' => 'FER003', 'descripcion' => 'Tornillos Autoperforantes x100', 'cantidad' => 50, 'precio' => 8.75],
            ['codigo' => 'FER004', 'descripcion' => 'Cinta Métrica 5m Stanley', 'cantidad' => 20, 'precio' => 18.90]
        ],
        'oficina' => [
            ['codigo' => 'OF001', 'descripcion' => 'Resma Papel A4 75g 500 hojas', 'cantidad' => 100, 'precio' => 8.50],
            ['codigo' => 'OF002', 'descripcion' => 'Tinta Impresora HP 664 Negro', 'cantidad' => 25, 'precio' => 22.00],
            ['codigo' => 'OF003', 'descripcion' => 'Carpeta A4 3 Anillos', 'cantidad' => 60, 'precio' => 4.20],
            ['codigo' => 'OF004', 'descripcion' => 'Calculadora Científica Casio', 'cantidad' => 10, 'precio' => 45.80],
            ['codigo' => 'OF005', 'descripcion' => 'Clips Metálicos N°1 x100', 'cantidad' => 200, 'precio' => 1.50]
        ],
        'textil' => [
            ['codigo' => 'TEX001', 'descripcion' => 'Remera Algodón Talle M', 'cantidad' => 40, 'precio' => 15.00],
            ['codigo' => 'TEX002', 'descripcion' => 'Jean Clásico Talle 32', 'cantidad' => 20, 'precio' => 35.00],
            ['codigo' => 'TEX003', 'descripcion' => 'Zapatillas Deportivas N°40', 'cantidad' => 15, 'precio' => 75.00],
            ['codigo' => 'TEX004', 'descripcion' => 'Campera Impermeable L', 'cantidad' => 8, 'precio' => 120.00]
        ]
    ];

    // Configuración de remitos
    $remitos_config = [
        [
            'proveedor' => $proveedores_test[1], // TechShop
            'productos' => $productos_por_tipo['electronica'],
            'numero_remito' => 'TS-2025-001234',
            'estilo' => 'moderno_tecnologia',
            'fecha' => '2025-08-01'
        ],
        [
            'proveedor' => $proveedores_test[2], // Norte Alimentos
            'productos' => $productos_por_tipo['alimentos'],
            'numero_remito' => 'NA-0001567',
            'estilo' => 'clasico_alimentos',
            'fecha' => '2025-08-02'
        ],
        [
            'proveedor' => $proveedores_test[3], // Ferro Oeste
            'productos' => $productos_por_tipo['ferreteria'],
            'numero_remito' => 'FO-25-0892',
            'estilo' => 'industrial_ferreteria',
            'fecha' => '2025-07-30'
        ],
        [
            'proveedor' => $proveedores_test[4], // Papel Sur
            'productos' => $productos_por_tipo['oficina'],
            'numero_remito' => 'PS/2025/445',
            'estilo' => 'minimalista_oficina',
            'fecha' => '2025-08-03'
        ],
        [
            'proveedor' => $proveedores_test[0], // Central Mayorista
            'productos' => $productos_por_tipo['textil'],
            'numero_remito' => 'CM-240803-789',
            'estilo' => 'mayorista_textil',
            'fecha' => '2025-08-03'
        ]
    ];

    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h4>🎯 Configuración de Remitos:</h4>";
    echo "<ul>";
    foreach ($remitos_config as $i => $config) {
        $num = $i + 1;
        echo "<li><strong>Remito {$num}:</strong> {$config['proveedor']['nombre_comercial']} - {$config['numero_remito']} - " . count($config['productos']) . " productos</li>";
    }
    echo "</ul>";
    echo "</div>";

    // Crear directorio para remitos
    $remitos_dir = 'assets/remitos_prueba';
    if (!is_dir($remitos_dir)) {
        mkdir($remitos_dir, 0777, true);
        echo "<p style='color: green;'>✅ Directorio '$remitos_dir' creado</p>";
    }

    echo "<h4>🚀 Generando archivos...</h4>";
    echo "<p><button onclick='generarRemitos()' class='btn btn-primary'>Generar 5 Remitos PDF + JPG</button></p>";
    echo "<p><em>Los archivos se guardarán en: <code>$remitos_dir/</code></em></p>";

    echo "<div id='resultado'></div>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='menu_principal.php' class='btn btn-secondary'>🏠 Volver al Menú</a></p>";
echo "<p><a href='modulos/compras/nueva_compra.php' class='btn btn-info'>🛒 Probar Carga OCR</a></p>";
?>

<script>
    function generarRemitos() {
        document.getElementById('resultado').innerHTML = '<p style="color: blue;">🔄 Generando remitos... Esto puede tomar unos segundos.</p>';

        fetch('generar_remitos_worker.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('resultado').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('resultado').innerHTML = '<p style="color: red;">❌ Error al generar remitos: ' + error + '</p>';
            });
    }
</script>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background: #f5f5f5;
    }

    h1,
    h3,
    h4 {
        color: #333;
    }

    .btn {
        padding: 10px 20px;
        margin: 5px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #007bff;
        color: white;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-info {
        background: #17a2b8;
        color: white;
    }

    .btn:hover {
        opacity: 0.8;
    }
</style>