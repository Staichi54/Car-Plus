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
        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px; }
        tr:hover { background-color: #f2f2f2; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Gestión de Citas</h1>

    <!-- Formulario -->
    <form method="post">
        <input type="hidden" name="idCita" id="idCita">

        <label>Placa:</label>
        <input type="text" name="placa" id="placa" required><br><br>

        <label>Fecha:</label>
        <input type="datetime-local" name="fecha" id="fecha" required><br><br>

        <label>Servicio:</label>
        <input type="text" name="servicio" id="servicio" required><br><br>

        <label>Estado:</label>
        <input type="text" name="estado" id="estado" required><br><br>

        <label>Observaciones:</label>
        <input type="text" name="observaciones" id="observaciones"><br><br>

        <button type="submit" name="agregar">Agregar</button>
        <button type="submit" name="modificar">Modificar</button>
        <button type="submit" name="eliminar">Eliminar</button>
    </form>

    <hr>

    <!-- Tabla de citas -->
    <h2>Listado de Citas</h2>
    <table>
        <tr>
            <th>ID Cita</th>
            <th>Placa</th>
            <th>Fecha</th>
            <th>Servicio</th>
            <th>Estado</th>
            <th>Observaciones</th>
        </tr>
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

    <div style="text-align:center;">
        <a href="Vendedor.php">⬅ Volver al Panel Vendedor</a>
    </div>  
</body>
</html>
