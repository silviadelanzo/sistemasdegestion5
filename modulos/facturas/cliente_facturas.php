<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');
echo "Facturas del cliente - En desarrollo<br><a href='clientes.php'>Volver</a>";
?>