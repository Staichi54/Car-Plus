<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["rol"] != "admin") { 
    // Solo admin puede gestionar usuarios
    header("Location: Admin.php");
    exit();
}

// 🔹 Conexión a SQL Server
$serverName = "db28471.public.databaseasp.net"; 
$connectionOptions = [
    "Database" => "db28471",
    "Uid" => "db28471",
    "PWD" => "2Fb%y9-EH_z7",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

/* =========================
   🔹 CRUD USUARIOS
   ========================= */

// ➡️ CREATE
if (isset($_POST["crear"])) {
    $usuario = $_POST["usuario"];
    $contrasena = password_hash($_POST["contrasena"], PASSWORD_DEFAULT);
    $rol = $_POST["rol"];
    $correo = $_POST["correo"];

    $sql = "INSERT INTO Usuarios (Usuario, Contrasena, Rol, Correo) VALUES (?, ?, ?, ?)";
    sqlsrv_query($conn, $sql, [$usuario, $contrasena, $rol, $correo]);

    header("Location: Usuarios.php");
    exit();
}

// ➡️ UPDATE
if (isset($_POST["editar"])) {
    $idUsuario = $_POST["idUsuario"];
    $usuario = $_POST["usuario"];
    $rol = $_POST["rol"];
    $correo = $_POST["correo"];
    
    if (!empty($_POST["contrasena"])) {
        $contrasena = password_hash($_POST["contrasena"], PASSWORD_DEFAULT);
        $sql = "UPDATE Usuarios SET Usuario=?, Contrasena=?, Rol=?, Correo=? WHERE IdUsuario=?";
        sqlsrv_query($conn, $sql, [$usuario, $contrasena, $rol, $correo, $idUsuario]);
    } else {
        $sql = "UPDATE Usuarios SET Usuario=?, Rol=?, Correo=? WHERE IdUsuario=?";
        sqlsrv_query($conn, $sql, [$usuario, $rol, $correo, $idUsuario]);
    }

    header("Location: Usuarios.php");
    exit();
}

// ➡️ DELETE
if (isset($_POST["eliminar"])) {
    $idUsuario = $_POST["idUsuario"];
    $sql = "DELETE FROM Usuarios WHERE IdUsuario=?";
    sqlsrv_query($conn, $sql, [$idUsuario]);

    header("Location: Usuarios.php");
    exit();
}

// ➡️ READ (lista)
$usuarios = sqlsrv_query($conn, "SELECT * FROM Usuarios ORDER BY IdUsuario ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align:center; }
        th { background: #6c757d; color: white; }
        .btn { padding: 6px 12px; background: #007bff; color: white; border: none; cursor: pointer; margin: 3px; }
        .btn:hover { background: #0056b3; }
        .btn-red { background: red; }
        input, select { margin: 5px; padding: 6px; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    </style>
</head>
<body>
    <div class="top-bar">
        <h1>👤 Gestión de Usuarios</h1>
        <a href="Admin.php" class="btn">⬅ Volver a Inicio</a>
    </div>

    <!-- Crear nuevo usuario -->
    <form method="POST">
        <h2>➕ Crear Usuario</h2>
        <input type="text" name="usuario" placeholder="Usuario" required>
        <input type="password" name="contrasena" placeholder="Contraseña" required>
        <input type="email" name="correo" placeholder="Correo" required>
        <select name="rol" required>
            <option value="admin">Admin</option>
            <option value="vendedor">Vendedor</option>
        </select>
        <button type="submit" name="crear" class="btn">Crear</button>
    </form>

    <!-- Lista de usuarios -->
    <h2>📋 Usuarios Registrados</h2>
    <table>
        <tr><th>ID</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Acciones</th></tr>
        <?php while ($row = sqlsrv_fetch_array($usuarios, SQLSRV_FETCH_ASSOC)) { ?>
            <tr>
                <td><?php echo $row["IdUsuario"]; ?></td>
                <td><?php echo $row["Usuario"]; ?></td>
                <td><?php echo $row["Correo"]; ?></td>
                <td><?php echo $row["Rol"]; ?></td>
                <td>
                    <!-- Editar -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="idUsuario" value="<?php echo $row["IdUsuario"]; ?>">
                        <input type="text" name="usuario" value="<?php echo $row["Usuario"]; ?>" required>
                        <input type="password" name="contrasena" placeholder="Nueva contraseña (opcional)">
                        <input type="email" name="correo" value="<?php echo $row["Correo"]; ?>" required>
                        <select name="rol">
                            <option value="admin" <?php if ($row["Rol"]=="admin") echo "selected"; ?>>Admin</option>
                            <option value="vendedor" <?php if ($row["Rol"]=="vendedor") echo "selected"; ?>>Vendedor</option>
                        </select>
                        <button type="submit" name="editar" class="btn">✏️</button>
                    </form>

                    <!-- Eliminar -->
                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este usuario?');">
                        <input type="hidden" name="idUsuario" value="<?php echo $row["IdUsuario"]; ?>">
                        <button type="submit" name="eliminar" class="btn btn-red">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
