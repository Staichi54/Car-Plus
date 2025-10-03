<?php
// ========================================
// CONEXIÓN A LA BASE DE DATOS
// ========================================
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

// ========================================
// AGREGAR VEHÍCULO + PERSONA
// ========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["agregar"])) {
    // Insertar persona con OUTPUT para recuperar IdPersona
    $sqlPersona = "INSERT INTO Personas (Nombre, Cedula, Correo, Telefono)
                   OUTPUT INSERTED.IdPersona
                   VALUES (?, ?, ?, ?)";
    $paramsPersona = [
        $_POST["nombre_persona"],
        $_POST["cedula_persona"],
        $_POST["correo_persona"],
        $_POST["telefono_persona"]
    ];
    $stmtPersona = sqlsrv_query($conn, $sqlPersona, $paramsPersona);

    if ($stmtPersona) {
        $row = sqlsrv_fetch_array($stmtPersona, SQLSRV_FETCH_ASSOC);
        $idPersona = $row["IdPersona"];

        // Insertar vehículo asociado
        $sqlVehiculo = "INSERT INTO Vehiculos (Placa, Marca, Modelo, Anio, Color, NumeroChasis, NumeroMotor, Observaciones, IdPersona)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $paramsVehiculo = [
            $_POST["placa"], $_POST["marca"], $_POST["modelo"], $_POST["anio"], $_POST["color"],
            $_POST["chasis"], $_POST["motor"], $_POST["observaciones"], $idPersona
        ];
        $stmtVehiculo = sqlsrv_query($conn, $sqlVehiculo, $paramsVehiculo);

        $mensaje = $stmtVehiculo
            ? "✅ Vehículo y propietario agregados correctamente."
            : "❌ Error al agregar vehículo: " . print_r(sqlsrv_errors(), true);
    } else {
        $mensaje = "❌ Error al agregar persona: " . print_r(sqlsrv_errors(), true);
    }
}

// ========================================
// EDITAR VEHÍCULO + PERSONA
// ========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["actualizar"])) {
    $idVehiculo = intval($_POST["id"]);

    // Obtener ID de persona asociada al vehículo
    $sqlGetPersona = "SELECT IdPersona FROM Vehiculos WHERE IdVehiculo=?";
    $res = sqlsrv_query($conn, $sqlGetPersona, [$idVehiculo]);
    $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
    $idPersona = $row["IdPersona"];

    // Actualizar persona
    $sqlPersona = "UPDATE Personas SET Nombre=?, Cedula=?, Correo=?, Telefono=? WHERE IdPersona=?";
    $paramsPersona = [
        $_POST["nombre_persona"],
        $_POST["cedula_persona"],
        $_POST["correo_persona"],
        $_POST["telefono_persona"],
        $idPersona
    ];
    sqlsrv_query($conn, $sqlPersona, $paramsPersona);

    // Actualizar vehículo
    $sqlVehiculo = "UPDATE Vehiculos
                    SET Placa=?, Marca=?, Modelo=?, Anio=?, Color=?, NumeroChasis=?, NumeroMotor=?, Observaciones=?
                    WHERE IdVehiculo=?";
    $paramsVehiculo = [
        $_POST["placa"], $_POST["marca"], $_POST["modelo"], $_POST["anio"], $_POST["color"],
        $_POST["chasis"], $_POST["motor"], $_POST["observaciones"], $idVehiculo
    ];
    $stmt = sqlsrv_query($conn, $sqlVehiculo, $paramsVehiculo);

    $mensaje = $stmt
        ? "✅ Vehículo y propietario actualizados correctamente."
        : "❌ Error al actualizar: " . print_r(sqlsrv_errors(), true);
}

// ========================================
// ELIMINAR VEHÍCULO (+ PERSONA SI YA NO TIENE MÁS VEHÍCULOS)
// ========================================
if (isset($_GET["eliminar"])) {
    $idVehiculo = intval($_GET["eliminar"]);

    // Obtener persona asociada
    $sqlGetPersona = "SELECT IdPersona FROM Vehiculos WHERE IdVehiculo=?";
    $res = sqlsrv_query($conn, $sqlGetPersona, [$idVehiculo]);
    $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
    $idPersona = $row["IdPersona"];

    // Eliminar vehículo
    $sqlDeleteVehiculo = "DELETE FROM Vehiculos WHERE IdVehiculo=?";
    $stmtDel = sqlsrv_query($conn, $sqlDeleteVehiculo, [$idVehiculo]);

    if ($stmtDel) {
        // Verificar si esa persona aún tiene más vehículos
        $sqlCheck = "SELECT COUNT(*) AS Total FROM Vehiculos WHERE IdPersona=?";
        $resCheck = sqlsrv_query($conn, $sqlCheck, [$idPersona]);
        $rowCheck = sqlsrv_fetch_array($resCheck, SQLSRV_FETCH_ASSOC);

        if ($rowCheck["Total"] == 0) {
            // Eliminar persona
            $sqlDeletePersona = "DELETE FROM Personas WHERE IdPersona=?";
            sqlsrv_query($conn, $sqlDeletePersona, [$idPersona]);
        }

        $mensaje = "✅ Vehículo eliminado correctamente.";
    } else {
        $mensaje = "❌ Error al eliminar vehículo: " . print_r(sqlsrv_errors(), true);
    }
}

// ========================================
// OBTENER VEHÍCULO PARA EDICIÓN
// ========================================
$vehiculoEditar = null;
if (isset($_GET["editar"])) {
    $idVehiculo = intval($_GET["editar"]);
    $sql = "SELECT v.*, p.Nombre AS NombrePersona, p.Cedula AS CedulaPersona,
                   p.Correo AS CorreoPersona, p.Telefono AS TelefonoPersona
            FROM Vehiculos v
            LEFT JOIN Personas p ON v.IdPersona = p.IdPersona
            WHERE v.IdVehiculo=?";
    $stmt = sqlsrv_query($conn, $sql, [$idVehiculo]);
    $vehiculoEditar = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

// ========================================
// LISTADO DE VEHÍCULOS + PERSONAS
// ========================================
$sql = "SELECT v.*, p.Nombre AS NombrePersona, p.Cedula AS CedulaPersona,
               p.Correo AS CorreoPersona, p.Telefono AS TelefonoPersona
        FROM Vehiculos v
        LEFT JOIN Personas p ON v.IdPersona = p.IdPersona";
$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Vehículos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { margin-bottom: 30px; background: #f5f5f5; padding: 15px; border-radius: 8px; }
        input, textarea { margin: 5px; padding: 8px; width: 250px; }
        button { padding: 8px 15px; margin-top: 10px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #222; color: white; }
        .msg { margin-bottom: 20px; font-weight: bold; color: green; }
        .error { color: red; }
    </style>
</head>
<body>

<h2>Gestión de Vehículos</h2>

    <a href="Vendedor.php" style="text-decoration:none; background:#28a745; color:white; padding:10px 15px; border-radius:5px;">
        Volver
    </a>

<?php if ($mensaje): ?>
    <div class="msg"><?php echo $mensaje; ?></div>
<?php endif; ?>

<!-- FORMULARIO -->
<form method="POST" action="Vehiculo.php">
    <h3><?php echo $vehiculoEditar ? "Editar Vehículo" : "Agregar Vehículo"; ?></h3>
    <?php if ($vehiculoEditar): ?>
        <input type="hidden" name="id" value="<?php echo $vehiculoEditar['IdVehiculo']; ?>">
    <?php endif; ?>

    <!-- Datos del Vehículo -->
    <input type="text" name="placa" placeholder="Placa" required value="<?php echo $vehiculoEditar['Placa'] ?? ''; ?>">
    <input type="text" name="marca" placeholder="Marca" required value="<?php echo $vehiculoEditar['Marca'] ?? ''; ?>">
    <input type="text" name="modelo" placeholder="Modelo" required value="<?php echo $vehiculoEditar['Modelo'] ?? ''; ?>">
    <input type="number" name="anio" placeholder="Año" required value="<?php echo $vehiculoEditar['Anio'] ?? ''; ?>">
    <input type="text" name="color" placeholder="Color" value="<?php echo $vehiculoEditar['Color'] ?? ''; ?>">
    <input type="text" name="chasis" placeholder="N° Chasis" value="<?php echo $vehiculoEditar['NumeroChasis'] ?? ''; ?>">
    <input type="text" name="motor" placeholder="N° Motor" value="<?php echo $vehiculoEditar['NumeroMotor'] ?? ''; ?>">
    <textarea name="observaciones" placeholder="Observaciones"><?php echo $vehiculoEditar['Observaciones'] ?? ''; ?></textarea>

    <!-- Datos del Propietario -->
    <h4>Datos del Propietario</h4>
    <input type="text" name="nombre_persona" placeholder="Nombre" required value="<?php echo $vehiculoEditar['NombrePersona'] ?? ''; ?>">
    <input type="text" name="cedula_persona" placeholder="Cédula" required value="<?php echo $vehiculoEditar['CedulaPersona'] ?? ''; ?>">
    <input type="email" name="correo_persona" placeholder="Correo" required value="<?php echo $vehiculoEditar['CorreoPersona'] ?? ''; ?>">
    <input type="text" name="telefono_persona" placeholder="Teléfono" value="<?php echo $vehiculoEditar['TelefonoPersona'] ?? ''; ?>">

    <br>
    <button type="submit" name="<?php echo $vehiculoEditar ? "actualizar" : "agregar"; ?>">
        <?php echo $vehiculoEditar ? "Actualizar" : "Agregar"; ?>
    </button>
</form>

<!-- TABLA DE VEHÍCULOS -->
<table>
    <tr>
        <th>Placa</th><th>Marca</th><th>Modelo</th><th>Año</th><th>Color</th>
        <th>Propietario</th><th>Cédula</th><th>Correo</th><th>Teléfono</th><th>Acciones</th>
    </tr>
    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
        <tr>
            <td><?php echo $row["Placa"]; ?></td>
            <td><?php echo $row["Marca"]; ?></td>
            <td><?php echo $row["Modelo"]; ?></td>
            <td><?php echo $row["Anio"]; ?></td>
            <td><?php echo $row["Color"]; ?></td>
            <td><?php echo $row["NombrePersona"]; ?></td>
            <td><?php echo $row["CedulaPersona"]; ?></td>
            <td><?php echo $row["CorreoPersona"]; ?></td>
            <td><?php echo $row["TelefonoPersona"]; ?></td>
            <td>
                <a href="Vehiculo.php?editar=<?php echo $row['IdVehiculo']; ?>">✏️ Editar</a> |
                <a href="Vehiculo.php?eliminar=<?php echo $row['IdVehiculo']; ?>" onclick="return confirm('¿Eliminar este vehículo y su propietario si no tiene más?');">🗑️ Eliminar</a>
            </td>
        </tr>
    <?php } ?>
</table>
<body>

</body>
</html>

