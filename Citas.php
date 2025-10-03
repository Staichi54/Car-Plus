<?php
// Conexión a SQL Server
$serverName = "db28471.public.databaseasp.net"; 
$connectionOptions = [
    "Database" => "db28471",
    "Uid" => "db28471",
    "PWD" => "2Fb%y9-EH_z7",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("❌ Error de conexión: " . print_r(sqlsrv_errors(), true));
}

// Función auxiliar: obtener IdVehiculo desde Placa
function getIdVehiculo($conn, $placa) {
    $sql = "SELECT IdVehiculo FROM Vehiculos WHERE Placa = ?";
    $stmt = sqlsrv_query($conn, $sql, [$placa]);
    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        return $row['IdVehiculo'];
    }
    return null;
}

// ---------- CRUD ----------

// Agregar cita
if (isset($_POST["agregar"])) {
    $placa = $_POST["placa"];
    $idVehiculo = getIdVehiculo($conn, $placa);

    if ($idVehiculo) {
        $fechaInput = $_POST["fecha"];
        $fechaObj = DateTime::createFromFormat('Y-m-d\TH:i', $fechaInput);
        $fecha = $fechaObj ? $fechaObj->format('Y-m-d H:i:s') : null;
        $servicio = $_POST["servicio"];
        $estado = $_POST["estado"];
        $obs = $_POST["observaciones"];

        $sql = "INSERT INTO Citas (IdVehiculo, FechaCita, Servicio, Estado, Observaciones) VALUES (?, ?, ?, ?, ?)";
        $params = [$idVehiculo, [$fecha, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATETIME], $servicio, $estado, $obs];
        sqlsrv_query($conn, $sql, $params) or die(print_r(sqlsrv_errors(), true));
    } else {
        echo "❌ No se encontró un vehículo con la placa: $placa";
    }
}

// Modificar cita
if (isset($_POST["modificar"])) {
    $idCita = $_POST["idCita"];
    $placa = $_POST["placa"];
    $idVehiculo = getIdVehiculo($conn, $placa);

    if ($idVehiculo) {
        $fechaInput = $_POST["fecha"];
        $fechaObj = DateTime::createFromFormat('Y-m-d\TH:i', $fechaInput);
        $fecha = $fechaObj ? $fechaObj->format('Y-m-d H:i:s') : null;
        $servicio = $_POST["servicio"];
        $estado = $_POST["estado"];
        $obs = $_POST["observaciones"];

        $sql = "UPDATE Citas SET IdVehiculo=?, FechaCita=?, Servicio=?, Estado=?, Observaciones=? WHERE IdCita=?";
        $params = [$idVehiculo, [$fecha, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATETIME], $servicio, $estado, $obs, $idCita];
        sqlsrv_query($conn, $sql, $params) or die(print_r(sqlsrv_errors(), true));
    } else {
        echo "❌ No se encontró un vehículo con la placa: $placa";
    }
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
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #1e1e1e;
      color: #f0f0f0;
      text-align: center;
    }

    /* Volver arriba a la izquierda */
    .volver {
      position: absolute;
      top: 15px;
      left: 16px;
      background-color: #444;
      color: #f0f0f0;
      text-decoration: none;
      padding: 8px 12px;
      border-radius: 8px;
      font-weight: bold;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      z-index: 1000;
      transition: all 0.18s ease;
    }
    .volver:hover { background-color: #666; transform: scale(1.03); }

    .logo {
      width: 120px;
      margin: 70px auto 10px; /* leave room for the top-left volver button */
      display: block;
    }

    h1 {
      margin: 8px 0 10px;
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
      margin: 18px 0;
    }

    form {
      width: 90%;
      max-width: 600px;
      margin: 18px auto;
      padding: 18px;
      background: #2a2a2a;
      border-radius: 12px;
      box-shadow: 0 0 12px rgba(0,0,0,0.6);
      text-align: left;
    }

    form label {
      display: block;
      margin: 10px 0 6px;
      font-weight: bold;
      color: #f0f0f0;
    }

    form input {
      width: 100%;
      padding: 8px;
      border-radius: 6px;
      border: none;
      background: #3b3b3b;
      color: #fff;
      margin-bottom: 10px;
      box-sizing: border-box;
    }

    form input:focus {
      outline: none;
      border: 1px solid #ff3b3b;
      background: #444;
    }

    .form-actions {
      text-align: center;
      margin-top: 6px;
    }

    .form-actions button {
      padding: 8px 14px;
      border: none;
      border-radius: 6px;
      margin: 6px 6px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.2s ease;
      color: #fff;
    }

    .btn-agregar { background: #28a745; }
    .btn-agregar:hover { background: #1e7e34; }

    .btn-modificar { background: #007bff; }
    .btn-modificar:hover { background: #0056b3; }

    .btn-eliminar { background: #ff3b3b; }
    .btn-eliminar:hover { background: #cc2e2e; }

    table {
      width: 92%;
      margin: 20px auto 40px;
      border-collapse: collapse;
      background: #2a2a2a;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 0 12px rgba(0,0,0,0.6);
    }

    thead th {
      background: linear-gradient(180deg,#ff3b3b,#e23232);
      color: #fff;
      padding: 12px 10px;
      text-align: center;
      font-weight: bold;
    }

    tbody td {
      padding: 10px;
      border-top: 1px solid #3b3b3b;
      color: #e9e9e9;
    }

    tbody tr:hover {
      background: #333435;
      cursor: pointer;
    }

    /* compact spacing for table cells */
    td, th { box-sizing: border-box; }

    /* small screens adjustments */
    @media (max-width: 640px) {
      .logo { width: 100px; margin-top: 60px; }
      form { padding: 14px; }
      thead th, tbody td { font-size: 13px; padding: 8px; }
      .volver { padding: 6px 10px; top: 10px; left: 8px; }
    }
  </style>
</head>
<body>
  <!-- Volver arriba-izquierda -->
  <a href="Vendedor.php" class="volver al Panel Vendedor">⬅ Volver</a>

  <!-- Logo -->
  <img src="logo.png" alt="Logo Auto Parts" class="logo">

  <h1>Gestión de Citas</h1>

  <!-- Formulario -->
  <form method="post">
      <input type="hidden" name="idCita" id="idCita">

      <label for="placa">Placa:</label>
      <input type="text" name="placa" id="placa" required>

      <label for="fecha">Fecha:</label>
      <input type="datetime-local" name="fecha" id="fecha" required>

      <label for="servicio">Servicio:</label>
      <input type="text" name="servicio" id="servicio" required>

      <label for="estado">Estado:</label>
      <input type="text" name="estado" id="estado" required>

      <label for="observaciones">Observaciones:</label>
      <input type="text" name="observaciones" id="observaciones">

      <div class="form-actions">
        <button type="submit" name="agregar" class="btn-agregar">Agregar</button>
        <button type="submit" name="modificar" class="btn-modificar">Modificar</button>
        <button type="submit" name="eliminar" class="btn-eliminar" onclick="return confirm('¿Seguro que deseas eliminar esta cita?');">Eliminar</button>
      </div>
  </form>

  <!-- Tabla de citas -->
  <h2>Listado de Citas</h2>
  <table>
    <thead>
      <tr>
        <th>ID Cita</th>
        <th>Placa</th>
        <th>Fecha</th>
        <th>Servicio</th>
        <th>Estado</th>
        <th>Observaciones</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $sql = "SELECT c.IdCita, v.Placa, c.FechaCita, c.Servicio, c.Estado, c.Observaciones
              FROM Citas c
              INNER JOIN Vehiculos v ON c.IdVehiculo = v.IdVehiculo
              ORDER BY c.FechaCita DESC";
      $result = sqlsrv_query($conn, $sql);
      while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
          $fechaInput = $row['FechaCita'] ? date_format($row['FechaCita'], 'Y-m-d\TH:i') : "";
          $fechaMostrar = $row['FechaCita'] ? date_format($row['FechaCita'], 'Y-m-d H:i') : "";
          echo "<tr onclick=\"cargarCita('{$row['IdCita']}', '{$row['Placa']}', '{$fechaInput}', '{$row['Servicio']}', '{$row['Estado']}', '{$row['Observaciones']}')\">";
          echo "<td>".$row['IdCita']."</td>";
          echo "<td>".$row['Placa']."</td>";
          echo "<td>".$fechaMostrar."</td>";
          echo "<td>".$row['Servicio']."</td>";
          echo "<td>".$row['Estado']."</td>";
          echo "<td>".$row['Observaciones']."</td>";
          echo "</tr>";
      }
      ?>
    </tbody>
  </table>

  <!-- Script JS -->
  <script>
  function cargarCita(id, placa, fecha, servicio, estado, obs) {
      document.getElementById("idCita").value = id;
      document.getElementById("placa").value = placa;
      document.getElementById("fecha").value = fecha;
      document.getElementById("servicio").value = servicio;
      document.getElementById("estado").value = estado;
      document.getElementById("observaciones").value = obs;
  }
  </script>
</body>
</html>

