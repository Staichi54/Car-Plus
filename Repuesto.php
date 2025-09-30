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
$repuestoEditar = null;

# ✅ INSERTAR REPUESTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["agregar"])) {
    $sql = "INSERT INTO Repuestos (Nombre, Marca, Modelo, Precio, Cantidad, Descripcion, IdVehiculo)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $params = [
        $_POST["nombre"], $_POST["marca"], $_POST["modelo"],
        $_POST["precio"], $_POST["cantidad"], $_POST["descripcion"],
        !empty($_POST["vehiculo"]) ? $_POST["vehiculo"] : null
    ];
    $stmt = sqlsrv_query($conn, $sql, $params);
    $mensaje = $stmt ? "✅ Repuesto agregado correctamente." : "❌ Error al agregar: " . print_r(sqlsrv_errors(), true);
}

# ✅ EDITAR: cargar datos en formulario
if (isset($_GET["editar"])) {
    $id = intval($_GET["editar"]);
    $sql = "SELECT * FROM Repuestos WHERE IdRepuesto = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $repuestoEditar = $row;
    }
}

# ✅ ACTUALIZAR REPUESTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["actualizar"])) {
    $id = intval($_POST["id"]);
    $sql = "UPDATE Repuestos
            SET Nombre=?, Marca=?, Modelo=?, Precio=?, Cantidad=?, Descripcion=?, IdVehiculo=?
            WHERE IdRepuesto=?";
    $params = [
        $_POST["nombre"], $_POST["marca"], $_POST["modelo"],
        $_POST["precio"], $_POST["cantidad"], $_POST["descripcion"],
        !empty($_POST["vehiculo"]) ? $_POST["vehiculo"] : null,
        $id
    ];
    $stmt = sqlsrv_query($conn, $sql, $params);
    $mensaje = $stmt ? "✅ Repuesto actualizado correctamente." : "❌ Error al actualizar: " . print_r(sqlsrv_errors(), true);
}

# ✅ ELIMINAR REPUESTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["eliminar"])) {
    $id = intval($_POST["id"]);
    $sql = "DELETE FROM Repuestos WHERE IdRepuesto = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);
    $mensaje = $stmt ? "🗑 Repuesto eliminado correctamente." : "❌ Error al eliminar: " . print_r(sqlsrv_errors(), true);
}

# ✅ LISTADO
$sql = "SELECT R.*, V.Placa FROM Repuestos R
        LEFT JOIN Vehiculos V ON R.IdVehiculo = V.IdVehiculo";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

# ✅ LISTA DE VEHÍCULOS para el combo
$sqlVeh = "SELECT IdVehiculo, Placa FROM Vehiculos";
$stmtVeh = sqlsrv_query($conn, $sqlVeh);
$vehiculos = [];
while ($row = sqlsrv_fetch_array($stmtVeh, SQLSRV_FETCH_ASSOC)) {
    $vehiculos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Repuestos</title>
    <style>
        body { font-family: Arial, sans-serif; }
        form {
            width: 90%; margin: 20px auto; padding: 15px;
            border: 1px solid #333; border-radius: 6px; background: #f9f9f9;
        }
        input, textarea, select { margin: 5px; padding: 6px; width: 200px; }
        table { border-collapse: collapse; width: 90%; margin: 20px auto; }
        th, td { border: 1px solid #333; padding: 8px; text-align: center; cursor: pointer; }
        th { background-color: #444; color: #fff; }
        .msg { text-align:center; font-weight:bold; color: green; }
        .btn-danger { background:red; color:white; padding:6px 12px; border:none; border-radius:5px; cursor:pointer; }
        .btn-danger:hover { background:darkred; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Gestión de Repuestos</h2>

    <?php if (!empty($mensaje)) echo "<p class='msg'>$mensaje</p>"; ?>

    <!-- Formulario para agregar/editar/eliminar -->
    <form method="POST" action="Repuesto.php">
        <h3><?php echo $repuestoEditar ? "Editar Repuesto" : "Agregar Repuesto"; ?></h3>
        
        <?php if ($repuestoEditar) { ?>
            <input type="hidden" name="id" value="<?php echo $repuestoEditar['IdRepuesto']; ?>">
        <?php } ?>

        <input type="text" name="nombre" placeholder="Nombre" required 
               value="<?php echo $repuestoEditar['Nombre'] ?? ''; ?>">
        <input type="text" name="marca" placeholder="Marca" 
               value="<?php echo $repuestoEditar['Marca'] ?? ''; ?>">
        <input type="text" name="modelo" placeholder="Modelo" 
               value="<?php echo $repuestoEditar['Modelo'] ?? ''; ?>">
        <input type="number" step="0.01" name="precio" placeholder="Precio" required 
               value="<?php echo $repuestoEditar['Precio'] ?? ''; ?>">
        <input type="number" name="cantidad" placeholder="Cantidad" required 
               value="<?php echo $repuestoEditar['Cantidad'] ?? ''; ?>">
        <br>
        <select name="vehiculo">
            <option value="">-- Sin vehículo asociado --</option>
            <?php foreach ($vehiculos as $v) { ?>
                <option value="<?php echo $v['IdVehiculo']; ?>" 
                    <?php echo (isset($repuestoEditar['IdVehiculo']) && $repuestoEditar['IdVehiculo'] == $v['IdVehiculo']) ? "selected" : ""; ?>>
                    <?php echo $v['Placa']; ?>
                </option>
            <?php } ?>
        </select>
        <br>
        <textarea name="descripcion" placeholder="Descripción" rows="2"><?php echo $repuestoEditar['Descripcion'] ?? ''; ?></textarea>
        <br>

        <?php if ($repuestoEditar) { ?>
            <input type="submit" name="actualizar" value="Actualizar Repuesto">
            <button type="submit" name="eliminar" class="btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este repuesto?');">Eliminar Repuesto</button>
            <a href="Repuesto.php">Cancelar</a>
        <?php } else { ?>
            <input type="submit" name="agregar" value="Agregar Repuesto">
        <?php } ?>
    </form>

    <!-- Listado de repuestos -->
    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Precio</th>
            <th>Cantidad</th>
            <th>Vehículo</th>
            <th>Fecha Ingreso</th>
            <th>Descripción</th>
        </tr>
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
            <tr onclick="window.location.href='Repuesto.php?editar=<?php echo $row['IdRepuesto']; ?>'">
                <td><?php echo $row["IdRepuesto"]; ?></td>
                <td><?php echo $row["Nombre"]; ?></td>
                <td><?php echo $row["Marca"]; ?></td>
                <td><?php echo $row["Modelo"]; ?></td>
                <td><?php echo $row["Precio"]; ?></td>
                <td><?php echo $row["Cantidad"]; ?></td>
                <td><?php echo $row["Placa"] ?? "N/A"; ?></td>
                <td><?php echo $row["FechaIngreso"] ? $row["FechaIngreso"]->format("Y-m-d") : ""; ?></td>
                <td><?php echo $row["Descripcion"]; ?></td>
            </tr>
        <?php } ?>
    </table>

    <div style="text-align:center;">
        <a href="Vendedor.php">⬅ Volver al Panel Vendedor</a>
    </div>
</body>
</html>
