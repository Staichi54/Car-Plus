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
</head>
<body>
    <h2>Iniciar Sesión</h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST" action="Index.php">
        <label>Usuario:</label>
        <input type="text" name="usuario" required><br><br>
        
        <label>Contraseña:</label>
        <input type="password" name="contrasena" required><br><br>
        
        <input type="submit" value="Ingresar">
    </form>
</body>
</html>
