<?php
require_once '../../config/config.php';
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

$codigo = isset($_GET['codigo']) ? $_GET['codigo'] : '';
$producto = null;
$errores = [];

if ($codigo) {
    try {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE codigo = ?");
        $stmt->execute([$codigo]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$producto) {
            $errores[] = 'No se encontró el producto con código ' . htmlspecialchars($codigo);
        }
    } catch (PDOException $e) {
        $errores[] = 'Error de base de datos: ' . $e->getMessage();
    }
} else {
    $errores[] = 'No se especificó el código.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver producto test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Ver producto por código</h2>
    <?php if ($errores): ?>
        <div class="alert alert-danger">
            <?php foreach ($errores as $e) echo '<div>'.$e.'</div>'; ?>
        </div>
    <?php elseif ($producto): ?>
        <div class="card p-4">
            <h4 class="mb-3">Datos del producto</h4>
            <table class="table table-bordered">
                <?php foreach ($producto as $campo => $valor): ?>
                    <tr>
                        <th><?php echo htmlspecialchars($campo); ?></th>
                        <td><?php echo htmlspecialchars($valor); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
    <form method="get" class="mt-4">
        <div class="input-group">
            <input type="text" name="codigo" class="form-control" placeholder="PROD-0000066" value="<?php echo htmlspecialchars($codigo); ?>">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>
</div>
</body>
</html>
