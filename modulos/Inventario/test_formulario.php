<?php
require_once '../../config/config.php';

echo "<h1>🧪 Prueba Simple de Formulario</h1>";

try {
    $pdo = conectarDB();
    
    // Verificar que las tablas existen y tienen datos
    echo "<h3>✅ Verificando tablas...</h3>";
    
    $paises = $pdo->query("SELECT * FROM paises WHERE activo = 1")->fetchAll();
    echo "<p>✅ Países encontrados: " . count($paises) . "</p>";
    
    $monedas = $pdo->query("SELECT * FROM monedas WHERE activo = 1")->fetchAll();
    echo "<p>✅ Monedas encontradas: " . count($monedas) . "</p>";
    
    $impuestos = $pdo->query("SELECT * FROM impuestos WHERE activo = 1")->fetchAll();
    echo "<p>✅ Impuestos encontrados: " . count($impuestos) . "</p>";
    
    echo "<h3>📝 Formulario de Prueba Básico:</h3>";
    
    ?>
    
    <form style="max-width: 600px; margin: 20px 0; padding: 20px; border: 1px solid #ddd;">
        <h4>Información Básica</h4>
        <div style="margin: 10px 0;">
            <label>Código del Producto:</label><br>
            <input type="text" name="codigo" value="PROD-<?php echo time(); ?>" style="width: 100%; padding: 5px;">
        </div>
        
        <div style="margin: 10px 0;">
            <label>Nombre del Producto:</label><br>
            <input type="text" name="nombre" placeholder="Ingrese el nombre..." style="width: 100%; padding: 5px;">
        </div>
        
        <h4>Configuración Fiscal</h4>
        <div style="margin: 10px 0;">
            <label>País:</label><br>
            <select name="pais_id" style="width: 100%; padding: 5px;">
                <option value="">-- Seleccionar País --</option>
                <?php foreach ($paises as $pais): ?>
                    <option value="<?php echo $pais['id']; ?>"><?php echo htmlspecialchars($pais['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="margin: 10px 0;">
            <label>Moneda:</label><br>
            <select name="moneda_id" style="width: 100%; padding: 5px;">
                <option value="">-- Seleccionar Moneda --</option>
                <?php foreach ($monedas as $moneda): ?>
                    <option value="<?php echo $moneda['id']; ?>">
                        <?php echo htmlspecialchars($moneda['nombre'] . ' (' . $moneda['simbolo'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="margin: 10px 0;">
            <label>Impuesto:</label><br>
            <select name="impuesto_id" style="width: 100%; padding: 5px;">
                <option value="">-- Seleccionar Impuesto --</option>
                <?php foreach ($impuestos as $impuesto): ?>
                    <option value="<?php echo $impuesto['id']; ?>">
                        <?php echo htmlspecialchars($impuesto['nombre'] . ' - ' . $impuesto['porcentaje'] . '%'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="margin: 20px 0;">
            <button type="button" onclick="alert('Formulario de prueba funcionando correctamente!')" 
                    style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
                🧪 Probar Funcionamiento
            </button>
            
            <a href="../producto_form.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">
                🚀 Ir al Formulario Completo
            </a>
        </div>
    </form>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h4>📊 Datos de Prueba Disponibles:</h4>
        <p><strong>Países:</strong> <?php echo implode(', ', array_column($paises, 'nombre')); ?></p>
        <p><strong>Monedas:</strong> <?php echo count($monedas); ?> disponibles</p>
        <p><strong>Impuestos:</strong> <?php echo count($impuestos); ?> disponibles</p>
    </div>
    
    <?php
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p style='color: red; background: #f8d7da; padding: 10px;'>" . $e->getMessage() . "</p>";
}
?>
