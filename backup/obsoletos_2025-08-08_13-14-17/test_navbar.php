<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Navbar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
    <h1>Test del Navbar</h1>

    <?php
    session_start();
    $_SESSION['nombre_usuario'] = 'Administrador Sistema';
    $_SESSION['rol_usuario'] = 'admin';
    include 'config/navbar_code.php';
    ?>

    <div class="container mt-5">
        <div class="alert alert-info">
            <h4>🔍 Diagnóstico del Navbar</h4>
            <p>Esta página verifica que el navbar funcione correctamente.</p>
            <ul>
                <li>✅ Bootstrap CSS cargado</li>
                <li>✅ Bootstrap Icons cargados</li>
                <li>✅ Sesión simulada</li>
                <li>✅ Navbar incluido</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h5>Elementos que deberían aparecer:</h5>
                <ul>
                    <li>Logo "Gestión Administrativa"</li>
                    <li>Dashboard</li>
                    <li>Compras (con dropdown)</li>
                    <li>Productos (con dropdown)</li>
                    <li>Clientes (con dropdown)</li>
                    <li>Pedidos (con dropdown)</li>
                    <li>Facturación (con dropdown)</li>
                    <li>Usuario: "Administrador Sistema"</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h5>Problemas comunes:</h5>
                <ul>
                    <li>CSS no carga → 404 en Bootstrap</li>
                    <li>JavaScript no funciona → Dropdowns no abren</li>
                    <li>Variables de sesión → Aparece "Usuario"</li>
                    <li>HTML malformado → Estructura rota</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>