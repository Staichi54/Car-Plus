<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["rol"] != "admin") {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .btn {
            display: inline-block;
            margin: 10px;
            padding: 12px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Bienvenido <?php echo $_SESSION["usuario"]; ?> (Admin)</h1>
    <p>Has ingresado al panel de administración.</p>
<br>
    <!-- Botón para ver historial de reparaciones -->
    <a href="Historial.php" class="btn">Historial de Reparaciones</a>
<br>
    <a href="Reportes.php" class="btn">Ver Reportes</a>
    <br>

    <!-- Botón de logout -->
    <a href="Logout.php" class="btn">Cerrar Sesión</a>
</body>
</html>


