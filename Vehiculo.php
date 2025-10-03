<?php
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

$mensaje = "";

// Crear vehículo + propietario
if (isset($_POST["guardar"])) {
    $nombrePersona = $_POST["nombre_persona"];
    $cedulaPersona = $_POST["cedula_persona"];
    $correoPersona = $_POST["correo_persona"];
    $telefonoPersona = $_POST["telefono_persona"];

    $placa = $_POST["placa"];
    $marca = $_POST["marca"];
    $modelo = $_POST["modelo"];
    $anio = $_POST["anio"];
    $color = $_POST["color"];
    $chasis = $_POST["chasis"];
    $motor = $_POST["motor"];
    $observaciones = $_POST["observaciones"];

    // 1. Verificar si ya existe la persona por cédula
    $sqlCheck = "SELECT IdPersona FROM Personas WHERE Cedula = ?";
    $stmtCheck = sqlsrv_query($conn, $sqlCheck, [$cedulaPersona]);
    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

    if ($rowCheck) {
        $idPersona = $rowCheck["IdPersona"];
    } else {
        $sqlPersona = "INSERT INTO Personas (Nombre, Cedula, Correo, Telefono)
                       OUTPUT INSERTED.IdPersona
                       VALUES (?, ?, ?, ?)";
        $paramsPersona = [$nombrePersona, $cedulaPersona, $correoPersona, $telefonoPersona];
        $stmtPersona = sqlsrv_query($conn, $sqlPersona, $paramsPersona);

        if ($stmtPersona === false) {
            die("❌ Error al agregar persona: " . print_r(sqlsrv_errors(), true));
        }

        $rowPersona = sqlsrv_fetch_array($stmtPersona, SQLSRV_FETCH_ASSOC);
        $idPersona = $rowPersona["IdPersona"];
    }

    // 2. Insertar vehículo
    $sqlVehiculo = "INSERT INTO Vehiculos 
        (Placa, Marca, Modelo, Anio, Color, NumeroChasis, NumeroMotor, Observaciones, IdPersona)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $paramsVehiculo = [$placa, $marca, $modelo, $anio, $color, $chasis, $motor, $observaciones, $idPersona];
    $stmtVehiculo = sqlsrv_query($conn, $sqlVehiculo, $paramsVehiculo);

    $mensaje = $stmtVehiculo ? "✅ Vehículo y propietario agregados correctamente." : "❌ Error al agregar vehículo: " . print_r(sqlsrv_errors(), true);
}

// Editar vehículo
if (isset($_POST["editar"])) {
    $idVehiculo = $_POST["idVehiculo"];
    $sql = "UPDATE Vehiculos SET Placa=?, Marca=?, Modelo=?, Anio=?, Color=?, NumeroChasis=?, NumeroMotor=?, Observaciones=? WHERE IdVehiculo=?";
    $params = [$_POST["placa"], $_POST["marca"], $_POST["modelo"], $_POST["anio"], $_POST["color"], $_POST["chasis"], $_POST["motor"], $_POST["observaciones"], $idVehiculo];
    $stmt = sqlsrv_query($conn, $sql, $params);

    $mensaje = $stmt ? "✅ Vehículo actualizado correctamente." : "❌ Error al actualizar: " . print_r(sqlsrv_errors(), true);
}

// Eliminar vehículo
if (isset($_POST["eliminar"])) {
    $idVehiculo = $_POST["idVehiculo"];
    $sql = "DELETE FROM Vehiculos WHERE IdVehiculo=?";
    $stmt = sqlsrv_query($conn, $sql, [$idVehiculo]);
    $mensaje = $stmt ? "✅ Vehículo eliminado correctamente." : "❌ Error al eliminar: " . print_r(sqlsrv_errors(), true);
}

// Obtener lista de vehículos
$sql = "SELECT v.IdVehiculo, v.Placa, v.Marca, v.Modelo, v.Anio, v.Color, v.NumeroChasis, v.NumeroMotor, v.Observaciones,
               p.Nombre AS Propietario, p.Cedula, p.Correo, p.Telefono
        FROM Vehiculos v
        INNER JOIN Personas p ON v.IdPersona = p.IdPersona";
$stmt = sqlsrv_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Vehículos</title>
<style>
    body { font-family: Arial, sans-serif; background:#1e1e1e; color:#f0f0f0; margin:0; }
    h2 { text-align:center; color:#f7cbcb; margin:20px 0; text-shadow:-1px -1px 0 #ff3b3b,1px -1px 0 #ff3b3b,-1px 1px 0 #ff3b3b,1px 1px 0 #ff3b3b; }
    form {
        width:90%; max-width:900px; margin:20px auto; padding:20px;
        border-radius:10px; background:#2a2a2a; box-shadow:0 0 12px rgba(0,0,0,0.6);
        text-align:center;
    }
    input, textarea { margin:6px; padding:8px; width:200px; border:none; border-radius:6px; background:#3b3b3b; color:#f0f0f0; }
    input:focus, textarea:focus { outline:none; border:1px solid #ff3b3b; background:#444; }
    .btn { display:inline-block; margin:8px 5px; padding:10px 16px; border:none; border-radius:8px; font-weight:bold; cursor:pointer; transition:0.3s ease; }
    .btn-primary { background:#ff3b3b; color:#fff; }
    .btn-primary:hover { background:#cc2e2e; transform:translateY(-2px); }
    .btn-danger { background:#444; color:#f0f0f0; }
    .btn-danger:hover { background:#666; }
    .btn-cancel { background:#777; color:#fff; text-decoration:none; padding:10px 16px; border-radius:8px; }
    .btn-cancel:hover { background:#999; }
    table { border-collapse:collapse; width:95%; margin:20px auto; background:#2a2a2a; border-radius:10px; overflow:hidden; box-shadow:0 0 12px rgba(0,0,0,0.6); }
    th, td { border:1px solid #444; padding:10px; text-align:center; }
    th { background:#ff3b3b; color:#fff; }
    tr { cursor:pointer; transition:background 0.3s ease; }
    tr:hover { background:#3b3b3b; }
    .msg { text-align:center; font-weight:bold; color:#6df76d; }
    .volver { position:absolute; top:15px; left:20px; background:#444; color:#f0f0f0; padding:8px 12px; border-radius:8px; font-weight:bold; text-decoration:none; }
    .volver:hover { background:#666; }
</style>
</head>
<body>

<a href="Vendedor.php" class="volver">⬅ Volver al Panel Vendedor</a>
<h2>Gestión de Vehículos</h2>

<?php if (!empty($mensaje)) echo "<p class='msg'>$mensaje</p>"; ?>

<h3 id="form-title" style="text-align:center;">Registrar Vehículo y Propietario</h3>

<form method="POST" id="vehiculoForm">
    <input type="hidden" name="idVehiculo" id="idVehiculo">
    
    <input type="text" name="nombre_persona" id="nombre_persona" placeholder="Nombre Propietario" required>
    <input type="text" name="cedula_persona" id="cedula_persona" placeholder="Cédula" required>
    <input type="email" name="correo_persona" id="correo_persona" placeholder="Correo" required>
    <input type="text" name="telefono_persona" id="telefono_persona" placeholder="Teléfono" required><br>

    <input type="text" name="placa" id="placa" placeholder="Placa" required>
    <input type="text" name="marca" id="marca" placeholder="Marca" required>
    <input type="text" name="modelo" id="modelo" placeholder="Modelo" required>
    <input type="number" name="anio" id="anio" placeholder="Año" required>
    <input type="text" name="color" id="color" placeholder="Color" required><br>

    <input type="text" name="chasis" id="chasis" placeholder="N° Chasis" required>
    <input type="text" name="motor" id="motor" placeholder="N° Motor" required>
    <textarea name="observaciones" id="observaciones" placeholder="Observaciones" rows="2"></textarea><br>

    <button type="submit" name="guardar" id="guardarBtn" class="btn btn-primary">Guardar</button>
</form>

<h3 style="text-align:center;">Lista de Vehículos</h3>
<table id="tablaVehiculos">
    <tr>
        <th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Año</th><th>Color</th>
        <th>Chasis</th><th>Motor</th><th>Observaciones</th>
        <th>Propietario</th><th>Cédula</th><th>Correo</th><th>Teléfono</th><th>Acciones</th>
    </tr>
    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
        <tr onclick='cargarDatos(<?php echo json_encode($row); ?>)'>
            <td><?php echo $row["IdVehiculo"]; ?></td>
            <td><?php echo $row["Placa"]; ?></td>
            <td><?php echo $row["Marca"]; ?></td>
            <td><?php echo $row["Modelo"]; ?></td>
            <td><?php echo $row["Anio"]; ?></td>
            <td><?php echo $row["Color"]; ?></td>
            <td><?php echo $row["NumeroChasis"]; ?></td>
            <td><?php echo $row["NumeroMotor"]; ?></td>
            <td><?php echo $row["Observaciones"]; ?></td>
            <td><?php echo $row["Propietario"]; ?></td>
            <td><?php echo $row["Cedula"]; ?></td>
            <td><?php echo $row["Correo"]; ?></td>
            <td><?php echo $row["Telefono"]; ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="idVehiculo" value="<?php echo $row['IdVehiculo']; ?>">
                    <button type="submit" name="eliminar" class="btn btn-danger">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php } ?>
</table>

<script>
function cargarDatos(data) {
    document.getElementById("form-title").innerText = "Editar Vehículo";
    document.getElementById("idVehiculo").value = data.IdVehiculo;

    document.getElementById("nombre_persona").value = data.Propietario;
    document.getElementById("cedula_persona").value = data.Cedula;
    document.getElementById("correo_persona").value = data.Correo;
    document.getElementById("telefono_persona").value = data.Telefono;

    document.getElementById("placa").value = data.Placa;
    document.getElementById("marca").value = data.Marca;
    document.getElementById("modelo").value = data.Modelo;
    document.getElementById("anio").value = data.Anio;
    document.getElementById("color").value = data.Color;
    document.getElementById("chasis").value = data.NumeroChasis;
    document.getElementById("motor").value = data.NumeroMotor;
    document.getElementById("observaciones").value = data.Observaciones;

    let btn = document.getElementById("guardarBtn");
    btn.innerText = "Actualizar Vehículo";
    btn.name = "editar";
}
</script>

</body>
</html>
