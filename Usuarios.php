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
    $contrasena = $_POST["contrasena"]; // 🔹 sin hash
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
        $contrasena = $_POST["contrasena"]; // 🔹 sin hash
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
  <title>Gestión de Usuarios</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #1e1e1e;
      color: #f0f0f0;
      text-align: center;
    }

    h1 {
      margin: 20px 0 10px;
      color: #f7cbcb;
      font-weight: bold;
      font-size: 28px;
      text-shadow: -1px -1px 0 #ff3b3b,
                   1px -1px 0 #ff3b3b,
                  -1px  1px 0 #ff3b3b,
                   1px  1px 0 #ff3b3b;
    }

    h2 {
      color: #ff3b3b;
      margin: 20px 0 10px;
    }

    .logo {
      width: 120px;
      margin: 20px auto;
      display: block;
    }

    .volver {
      position: absolute;
      top: 15px;
      left: 20px;
      background-color: #444;
      color: #f0f0f0;
      text-decoration: none;
      padding: 8px 14px;
      border-radius: 8px;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
    }
    .volver:hover {
      background-color: #666;
      transform: scale(1.05);
    }

    form {
      width: 90%;
      max-width: 1000px;
      margin: 20px auto;
      padding: 20px;
      border-radius: 12px;
      background: #2a2a2a;
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.6);
    }

    input, select {
      margin: 6px 0;
      padding: 8px;
      border: none;
      border-radius: 6px;
      background: #3b3b3b;
      color: #f0f0f0;
    }

    input:focus, select:focus {
      outline: none;
      border: 1px solid #ff3b3b;
      background: #444;
    }

    button {
      display: inline-block;
      margin: 6px 4px;
      padding: 8px 12px;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s ease;
      color: #fff;
    }

    .btn { background: #007bff; }
    .btn:hover { background: #0056b3; }

    .btn-red { background: #ff3b3b; }
    .btn-red:hover { background: #cc2e2e; }

    table {
      border-collapse: collapse;
      width: 90%;
      margin: 20px auto;
      background: #2a2a2a;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.6);
    }

    th, td {
      border: 1px solid #444;
      padding: 10px;
      text-align: center;
    }

    th {
      background: #ff3b3b;
      color: #fff;
    }

    tr:hover {
      background: #3b3b3b;
    }
  </style>
</head>
<body>
  <a href="Admin.php" class="volver">⬅ Volver al Panel Admin</a>

  <img src="logo.png" alt="Logo Auto Parts" class="logo">

  <h1>👤 Gestión de Usuarios</h1>

  <!-- Crear nuevo usuario -->
  <form method="POST">
      <h2>➕ Crear Usuario</h2>
      <input type="text" name="usuario" placeholder="Usuario" required>
      <input type="password" name="contrasena" placeholder="Contraseña" required>
      <input type="text" name="contrasena" placeholder="Contraseña" required> <!-- 🔹 visible -->
      <input type="email" name="correo" placeholder="Correo" required>
      <select name="rol" required>
          <option value="admin">Admin</option>
          <option value="vendedor">Vendedor</option>
      </select>
      <button type="submit" name="crear" class="btn-red">Crear</button>
  </form>

  <!-- Lista de usuarios -->
  <h2>📋 Usuarios Registrados</h2>
  <table>
      <tr><th>ID</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Acciones</th></tr>
      <tr><th>ID</th><th>Usuario</th><th>Contraseña</th><th>Correo</th><th>Rol</th><th>Acciones</th></tr>
      <?php while ($row = sqlsrv_fetch_array($usuarios, SQLSRV_FETCH_ASSOC)) { ?>
          <tr>
              <td><?php echo $row["IdUsuario"]; ?></td>
              <td><?php echo $row["Usuario"]; ?></td>
              <td><?php echo $row["Contrasena"]; ?></td> <!-- 🔹 mostrada -->
              <td><?php echo $row["Correo"]; ?></td>
              <td><?php echo $row["Rol"]; ?></td>
              <td>
                  <!-- Editar -->
                  <form method="POST" style="display:inline;">
                      <input type="hidden" name="idUsuario" value="<?php echo $row["IdUsuario"]; ?>">
                      <input type="text" name="usuario" value="<?php echo $row["Usuario"]; ?>" required>
                      <input type="password" name="contrasena" placeholder="Nueva contraseña (opcional)">
                      <input type="text" name="contrasena" placeholder="Nueva contraseña (opcional)">
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
                      <button type="submit" name="eliminar" class="btn-red">🗑️</button>
                  </form>
              </td>
          </tr>
      <?php } ?>
  </table>
</body>
</html>
