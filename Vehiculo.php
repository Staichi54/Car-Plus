<?php
session_start();

// Solo permitir acceso si hay sesión iniciada
if (!isset($_SESSION["usuario"])) {
    header("Location: Index.php");
    exit();
}

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

$mensaje = "";
$vehiculoEditar = null;

# ✅ INSERTAR VEHÍCULO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["agregar"])) {
    $sql = "INSERT INTO Vehiculos (Placa, Marca, Modelo, Anio, Color, NumeroChasis, NumeroMotor, Observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $params = [
        $_POST["placa"], $_POST["marca"], $_POST["modelo"], $_POST["anio"],
        $_POST["color"], $_POST["chasis"], $_POST["motor"], $_POST["observaciones"]
    ];
    $stmt = sqlsrv_query($conn, $sql, $params);
    $mensaje = $stmt ? "✅ Vehículo agregado correctamente." : "❌ Error al agregar: " . print_r(sqlsrv_errors(), true);
}

# ✅ EDITAR: cargar datos en formulario
if (isset($_GET["editar"])) {
    $id = intval($_GET["editar"]);
    $sql = "SELECT * FROM Vehiculos WHERE IdVehiculo = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $vehiculoEditar = $row;
    }
}

# ✅ ACTUALIZAR VEHÍCULO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["actualizar"])) {
    $id = intval($_POST["id"]);
    $sql = "UPDATE Vehiculos
            SET Placa=?, Marca=?, Modelo=?, Anio=?, Color=?, NumeroChasis=?, NumeroMotor=?, Observaciones=?
            WHERE IdVehiculo=?";
    $params = [
        $_POST["placa"], $_POST["marca"], $_POST["modelo"], $_POST["anio"],
        $_POST["color"], $_POST["chasis"], $_POST["motor"], $_POST["observaciones"], $id
    ];
    $stmt = sqlsrv_query($conn, $sql, $params);
    $mensaje = $stmt ? "✅ Vehículo actualizado correctamente." : "❌ Error al actualizar: " . print_r(sqlsrv_errors(), true);
}

# ✅ ELIMINAR VEHÍCULO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["eliminar"])) {
    $id = intval($_POST["id"]);
    $sql = "DELETE FROM Vehiculos WHERE IdVehiculo = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);
    $mensaje = $stmt ? "🗑 Vehículo eliminado correctamente." : "❌ Error al eliminar: " . print_r(sqlsrv_errors(), true);
}

# ✅ LISTADO
$sql = "SELECT * FROM Vehiculos";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Vehículos</title>
    <style>
        body { font-family: Arial, sans-serif; }
        form {
            width: 90%; margin: 20px auto; padding: 15px;
            border: 1px solid #333; border-radius: 6px; background: #f9f9f9;
        }
        input, textarea { margin: 5px; padding: 6px; width: 200px; }
        table { border-collapse: collapse; width: 90%; margin: 20px auto; }
        th, td { border: 1px solid #333; padding: 8px; text-align: center; cursor: pointer; }
        th { background-color: #444; color: #fff; }
        .msg { text-align:center; font-weight:bold; color: green; }
        .btn-danger { background:red; color:white; padding:6px 12px; border:none; border-radius:5px; cursor:pointer; }
        .btn-danger:hover { background:darkred; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Gestión de Vehículos</h2>

    <?php if (!empty($mensaje)) echo "<p class='msg'>$mensaje</p>"; ?>

    <!-- Formulario para agregar/editar/eliminar -->
    <form method="POST" action="Vehiculo.php">
        <h3><?php echo $vehiculoEditar ? "Editar Vehículo" : "Agregar Vehículo"; ?></h3>
        
        <?php if ($vehiculoEditar) { ?>
            <input type="hidden" name="id" value="<?php echo $vehiculoEditar['IdVehiculo']; ?>">
        <?php } ?>

        <input type="text" name="placa" placeholder="Placa" required 
               value="<?php echo $vehiculoEditar['Placa'] ?? ''; ?>">
        <input type="text" name="marca" placeholder="Marca" required 
               value="<?php echo $vehiculoEditar['Marca'] ?? ''; ?>">
        <input type="text" name="modelo" placeholder="Modelo" required 
               value="<?php echo $vehiculoEditar['Modelo'] ?? ''; ?>">
        <input type="number" name="anio" placeholder="Año" required 
               value="<?php echo $vehiculoEditar['Anio'] ?? ''; ?>">
        <input type="text" name="color" placeholder="Color" 
               value="<?php echo $vehiculoEditar['Color'] ?? ''; ?>">
        <input type="text" name="chasis" placeholder="N° Chasis" 
               value="<?php echo $vehiculoEditar['NumeroChasis'] ?? ''; ?>">
        <input type="text" name="motor" placeholder="N° Motor" 
               value="<?php echo $vehiculoEditar['NumeroMotor'] ?? ''; ?>">
        <textarea name="observaciones" placeholder="Observaciones" rows="2"><?php echo $vehiculoEditar['Observaciones'] ?? ''; ?></textarea>
        <br>

        <?php if ($vehiculoEditar) { ?>
            <input type="submit" name="actualizar" value="Actualizar Vehículo">
            <button type="submit" name="eliminar" class="btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este vehículo?');">Eliminar Vehículo</button>
            <a href="Vehiculo.php">Cancelar</a>
        <?php } else { ?>
            <input type="submit" name="agregar" value="Agregar Vehículo">
        <?php } ?>
    </form>

    <!-- Listado de vehículos -->
    <table>
        <tr>
            <th>ID</th>
            <th>Placa</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Año</th>
            <th>Color</th>
            <th>Chasis</th>
            <th>Motor</th>
            <th>Fecha Ingreso</th>
            <th>Observaciones</th>
        </tr>
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
            <tr onclick="window.location.href='Vehiculo.php?editar=<?php echo $row['IdVehiculo']; ?>'">
                <td><?php echo $row["IdVehiculo"]; ?></td>
                <td><?php echo $row["Placa"]; ?></td>
                <td><?php echo $row["Marca"]; ?></td>
                <td><?php echo $row["Modelo"]; ?></td>
                <td><?php echo $row["Anio"]; ?></td>
                <td><?php echo $row["Color"]; ?></td>
                <td><?php echo $row["NumeroChasis"]; ?></td>
                <td><?php echo $row["NumeroMotor"]; ?></td>
                <td><?php echo $row["FechaIngreso"] ? $row["FechaIngreso"]->format("Y-m-d") : ""; ?></td>
                <td><?php echo $row["Observaciones"]; ?></td>
            </tr>
        <?php } ?>
    </table>

    <div style="text-align:center;">
        <a href="Vendedor.php">⬅ Volver al Panel Vendedor</a>
    </div>
</body>
</html>
