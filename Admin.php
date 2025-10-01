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
    /* Fondo general en modo oscuro */
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #1e1e1e;
      color: #f0f0f0;
      text-align: center;
    }

    /* Encabezado */
    h1 {
      margin: 20px 0 10px;
      color: #f7cbcb; /* rojo principal */
      font-weight: bold;
      font-size: 28px;
      /* Borde rojo con text-shadow */
      text-shadow: 
        -1px -1px 0 #ff3b3b,
         1px -1px 0 #ff3b3b,
        -1px  1px 0 #ff3b3b,
         1px  1px 0 #ff3b3b;
    }

    p {
      margin-bottom: 25px;
      font-size: 16px;
      color: #bbb;
    }

    /* Logo */
    .logo {
      width: 120px;
      margin: 20px auto;
      display: block;
    }

    /* Contenedor principal */
    .panel {
      background-color: #2a2a2a;
      width: 80%;
      max-width: 600px;
      margin: auto;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.6);
    }

    /* Botones */
    .btn {
      display: block;
      width: 50%;
      margin: 9px auto; /* centra horizontal */
      padding: 9px;
      background-color: #ff3b3b;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      transition: all 0.3s ease;
      text-align: center;
    }

    .btn:hover {
      background-color: #cc2e2e;
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
    }

    /* Botón de cerrar sesión distinto */
    .btn-logout {
      background-color: #444;
      color: #f0f0f0;
    }

    .btn-logout:hover {
      background-color: #666;
      color: #fff;
    }
  </style>
</head>
<body>
  <div class="panel">
    <!-- Logo -->
    <img src="logo.png" alt="Logo Auto Parts" class="logo">

    <h1>Bienvenido <?php echo $_SESSION["usuario"]; ?> al panel Admin</h1>
    <p>Selecciona tu opción:</p>

    <a href="Historial.php" class="btn">Historial de Reparaciones</a>
    <a href="Reportes.php" class="btn">Ver Reportes</a>
    <a href="Usuarios.php" class="btn">Gestionar Usuarios</a>
    <a href="Configuracion.php" class="btn">Configuración</a>
    <a href="Logout.php" class="btn btn-logout">Cerrar Sesión</a>
  </div>
</body>
</html>




