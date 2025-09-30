<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $contrasena = $_POST["contrasena"];

    // Conexión con SQL Server
    $serverName = "db28471.public.databaseasp.net"; 
    $connectionOptions = [
        "Database" => "db28471",
        "Uid" => "db28471",     // Usuario que creaste en SSMS
        "PWD" => "2Fb%y9-EH_z7",     // Contraseña que le diste
        "CharacterSet" => "UTF-8"
    ];

    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if ($conn === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Validar usuario en tabla Usuarios
    $sql = "SELECT * FROM Usuarios WHERE Usuario = ? AND Contrasena = ?";
    $params = array($usuario, $contrasena);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        $_SESSION["usuario"] = $row["Usuario"];
        $_SESSION["rol"] = $row["Rol"];

        if ($row["Rol"] == "admin") {
            header("Location: Admin.php");
            exit();
        } elseif ($row["Rol"] == "vendedor") {
            header("Location: Vendedor.php");
            exit();
        } else {
            $error = "Rol no reconocido.";
        }
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    /* Estilos generales modo oscuro */
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background-color: #1e1e1e;
      color: #f0f0f0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .login-container {
      background: #2a2a2a;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.7);
      width: 340px;
      text-align: center;
    }

    .logo {
      width: 120px;
      margin-bottom: 15px;
    }

    h2 {
      
      margin: 20px 0 10px;
      color: #f7cbcb; /* rojo principal */
      font-weight: bold;
      font-size: 28px;
      /* Borde blanco con text-shadow */
      text-shadow: 
      -1px -1px 0 #ff3b3b,
      1px -1px 0 #ff3b3b,
      -1px  1px 0 #ff3b3b,
      1px  1px 0 #ff3b3b;

    }

    label {
      display: block;
      text-align: left;
      margin: 10px 0 5px;
      font-size: 14px;
      color: #ddd;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: none;
      border-radius: 8px;
      background-color: #3b3b3b;
      color: #f0f0f0;
      font-size: 14px;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      outline: none;
      background-color: #444;
      border: 1px solid #ff3b3b;
    }

    input[type="submit"] {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      background-color: #ff3b3b;
      color: #fff;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    input[type="submit"]:hover {
      background-color: #cc2e2e;
    }

    .error {
      margin-bottom: 15px;
      color: #ff4d4d;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <!-- Logo -->
    <img src="logo.png" alt="Logo Auto Parts" class="logo">

    <h2>Iniciar Sesión</h2>

    <!-- Mensaje de error en PHP -->
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST" action="Index.php">
      <label for="usuario">Usuario:</label>
      <input type="text" id="usuario" name="usuario" required>

      <label for="contrasena">Contraseña:</label>
      <input type="password" id="contrasena" name="contrasena" required>

      <input type="submit" value="Ingresar">
    </form>
  </div>
</body>
</html>



