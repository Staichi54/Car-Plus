<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["rol"] != "vendedor") {
    header("Location: Index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Vendedor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .btn {
            display: inline-block;
            margin: 10px;
            padding: 12px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        .btn:hover {
            background-color: #1e7e34;
        }
    </style>
</head>
<body>
    <h1>Bienvenido <?php echo $_SESSION["usuario"]; ?> (Vendedor)</h1>
    <p>Este es el panel del vendedor.</p>

    <a href="Vehiculo.php" class="btn">Gestionar Vehículos</a>
        <br>
    <a href="Repuesto.php" class="btn">Gestionar Repuestos</a>
<br>
    <a href="Citas.php" class="btn">Gestionar Citas</a>
<br>
    <a href="Presupuesto.php" class="btn">Generar Presupuestos</a>
<br>
    <a href="Facturas.php" class="btn">Facturación</a>
<br>
    <a href="Consultas.php" class="btn">Consultas Rápidas</a>
<br>
    
    <a href="logout.php" class="btn">Cerrar Sesión</a>
</body>
</html>
