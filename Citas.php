<?php
// Conexión a SQL Server
$serverName = "db28471.public.databaseasp.net"; 
    $connectionOptions = [
        "Database" => "db28471",
        "Uid" => "db28471",     // Usuario que creaste en SSMS
        "PWD" => "2Fb%y9-EH_z7",     // Contraseña que le diste
        "CharacterSet" => "UTF-8"
    ];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("❌ Error de conexión: " . print_r(sqlsrv_errors(), true));
}

// ---------- CRUD ----------

// Agregar cita
if (isset($_POST["agregar"])) {
    $idVehiculo = $_POST["idVehiculo"];
    $fechaInput = $_POST["fecha"];
    $fechaObj = DateTime::createFromFormat('Y-m-d\TH:i', $fechaInput);
    $fecha = $fechaObj ? $fechaObj->format('Y-m-d H:i:s') : null;
    $servicio = $_POST["servicio"];
    $estado = $_POST["estado"];
    $obs = $_POST["observaciones"];

    $sql = "INSERT INTO Citas (IdVehiculo, FechaCita, Servicio, Estado, Observaciones) VALUES (?, ?, ?, ?, ?)";
    $params = [
        $idVehiculo,
        [$fecha, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATETIME],
        $servicio,
        $estado,
        $obs
    ];
    sqlsrv_query($conn, $sql, $params) or die(print_r(sqlsrv_errors(), true));
}

// Modificar cita
if (isset($_POST["modificar"])) {
    $idCita = $_POST["idCita"];
    $fechaInput = $_POST["fecha"];
    $fechaObj = DateTime::createFromFormat('Y-m-d\TH:i', $fechaInput);
    $fecha = $fechaObj ? $fechaObj->format('Y-m-d H:i:s') : null;
    $servicio = $_POST["servicio"];
    $estado = $_POST["estado"];
    $obs = $_POST["observaciones"];

    $sql = "UPDATE Citas SET FechaCita=?, Servicio=?, Estado=?, Observaciones=? WHERE IdCita=?";
    $params = [
        [$fecha, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATETIME],
        $servicio,
        $estado,
        $obs,
        $idCita
    ];
    sqlsrv_query($conn, $sql, $params) or die(print_r(sqlsrv_errors(), true));
}

// Eliminar cita
if (isset($_POST["eliminar"])) {
    $idCita = $_POST["idCita"];
    $sql = "DELETE FROM Citas WHERE IdCita=?";
    $params = [$idCita];
    sqlsrv_query($conn, $sql, $params) or die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Citas</title>
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
      color: #f7cbcb;
      font-weight: bold;
      font-size: 28px;
      text-shadow: 
        -1px -1px 0 #ff3b3b,
         1px -1px 0 #ff3b3b,
        -1px  1px 0 #ff3b3b,
         1px  1px 0 #ff3b3b;
    }

    h2 {
      color: #ff3b3b;
      margin: 20px 0 10px;
    }

    /* Logo */
    .logo {
      width: 120px;
      margin: 20px auto;
      display: block;
    }

    /* Formulario */
    form {
      width: 90%;
      max-width: 800px;
      margin: 20px auto;
      padding: 20px;
      border-radius: 12px;
      background: #2a2a2a;
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.6);
    }

    label {
      display: block;
      margin-top: 10px;
      font-weight: bold;
      text-align: left;
      color: #f0f0f0;
    }

    input, textarea, select {
      margin: 6px 0;
      padding: 8px;
      width: 95%;
      border: none;
      border-radius: 6px;
      background: #3b3b3b;
      color: #f0f0f0;
    }

    input:focus, textarea:focus, select:focus {
      outline: none;
      border: 1px solid #ff3b3b;
      background: #444;
    }

    button {
      display: inline-block;
      margin: 8px 5px;
      padding: 10px 16px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s ease;
      color: #fff;
    }

    button[name="agregar"] { background: #ff3b3b; }
    button[name="agregar"]:hover { background: #cc2e2e; }

    button[name="modificar"] { background: #ffaa2e; }
    button[name="modificar"]:hover { background: #e68a00; }

    button[name="eliminar"] { background: #444; }
    button[name="eliminar"]:hover { background: #666; }

    /* Contenedor de botones */
    .btn-group {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 15px;
    }

    /* Tabla */
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

    tr {
      cursor: pointer;
      transition: background 0.3s ease;
    }

    tr:hover {
      background: #3b3b3b;
    }

    /* Enlace volver */
/* Estilos del botón Volver */
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

.logo-btn {
    width: 22px;
    height: 22px;
}

  </style>
</head>
<body>
  <!-- Botón volver -->
  <a href="Vendedor.php" class="volver">⬅ Volver al Panel Vendedor</a>

  <!-- Logo -->
  <img src="logo.png" alt="Logo Auto Parts" class="logo">

  <h1>Gestión de Citas</h1>

  <!-- Formulario -->
  <form method="post">
    <input type="hidden" name="idCita" id="idCita">

    <label>ID Vehículo:</label>
    <input type="text" name="idVehiculo" id="idVehiculo" required>

    <label>Fecha:</label>
    <input type="datetime-local" name="fecha" id="fecha" required>

    <label>Servicio:</label>
    <input type="text" name="servicio" id="servicio" required>

    <label>Estado:</label>
    <input type="text" name="estado" id="estado" required>

    <label>Observaciones:</label>
    <input type="text" name="observaciones" id="observaciones">

    <div class="btn-group">
      <button type="submit" name="agregar">Agregar</button>
      <button type="submit" name="modificar">Modificar</button>
      <button type="submit" name="eliminar" onclick="return confirm('¿Seguro que deseas eliminar esta cita?');">Eliminar</button>
    </div>
  </form>

  <!-- Tabla de citas -->
  <h2>Listado de Citas</h2>
  <table>
    <tr>
      <th>ID Cita</th>
      <th>ID Vehículo</th>
      <th>Fecha</th>
      <th>Servicio</th>
      <th>Estado</th>
      <th>Observaciones</th>
    </tr>
    <?php
    $sql = "SELECT * FROM Citas ORDER BY FechaCita DESC";
    $result = sqlsrv_query($conn, $sql);
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $fechaInput = $row['FechaCita'] ? date_format($row['FechaCita'], 'Y-m-d\TH:i') : "";
        $fechaMostrar = $row['FechaCita'] ? date_format($row['FechaCita'], 'Y-m-d H:i') : "";
        echo "<tr onclick=\"cargarCita('{$row['IdCita']}', '{$row['IdVehiculo']}', '{$fechaInput}', '{$row['Servicio']}', '{$row['Estado']}', '{$row['Observaciones']}')\">";
        echo "<td>".$row['IdCita']."</td>";
        echo "<td>".$row['IdVehiculo']."</td>";
        echo "<td>".$fechaMostrar."</td>";
        echo "<td>".$row['Servicio']."</td>";
        echo "<td>".$row['Estado']."</td>";
        echo "<td>".$row['Observaciones']."</td>";
        echo "</tr>";
    }
    ?>
  </table>

  <!-- Script JS -->
  <script>
  function cargarCita(id, vehiculo, fecha, servicio, estado, obs) {
      document.getElementById("idCita").value = id;
      document.getElementById("idVehiculo").value = vehiculo;
      document.getElementById("fecha").value = fecha;
      document.getElementById("servicio").value = servicio;
      document.getElementById("estado").value = estado;
      document.getElementById("observaciones").value = obs;
  }
  </script>
</body>
</html>
