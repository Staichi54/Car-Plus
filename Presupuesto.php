<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["rol"] != "vendedor") {
    header("Location: Index.php");
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
   🔹 CRUD PRESUPUESTOS
   ========================= */

// ➡️ CREATE Presupuesto
if (isset($_POST["crear"])) {
    $idVehiculo = $_POST["idVehiculo"];
    $sql = "INSERT INTO Presupuestos (IdVehiculo) VALUES (?)";
    sqlsrv_query($conn, $sql, [$idVehiculo]);
    header("Location: Presupuesto.php");
    exit();
}

// ➡️ UPDATE Presupuesto (Estado)
if (isset($_POST["editarPresupuesto"])) {
    $idPresupuesto = $_POST["idPresupuesto"];
    $estado = $_POST["estado"];
    $sql = "UPDATE Presupuestos SET Estado=? WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$estado, $idPresupuesto]);
    header("Location: Presupuesto.php?id=$idPresupuesto");
    exit();
}

// ➡️ DELETE Presupuesto
if (isset($_POST["eliminarPresupuesto"])) {
    $idPresupuesto = $_POST["idPresupuesto"];
    $sql = "DELETE FROM DetallePresupuesto WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$idPresupuesto]);
    $sql = "DELETE FROM Presupuestos WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$idPresupuesto]);
    header("Location: Presupuesto.php");
    exit();
}

/* =========================
   🔹 CRUD DETALLES
   ========================= */

// ➡️ CREATE Detalle
if (isset($_POST["agregarDetalle"])) {
    $idPresupuesto = $_POST["idPresupuesto"];
    $tipo = $_POST["tipo"];
    $descripcion = $_POST["descripcion"];
    $cantidad = $_POST["cantidad"];
    $precio = $_POST["precio"];

    $sql = "INSERT INTO DetallePresupuesto (IdPresupuesto, Tipo, Descripcion, Cantidad, PrecioUnitario)
            VALUES (?, ?, ?, ?, ?)";
    sqlsrv_query($conn, $sql, [$idPresupuesto, $tipo, $descripcion, $cantidad, $precio]);

    // Recalcular total
    $sql = "UPDATE Presupuestos
            SET Total = (SELECT SUM(Subtotal) FROM DetallePresupuesto WHERE IdPresupuesto=?)
            WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$idPresupuesto, $idPresupuesto]);

    header("Location: Presupuesto.php?id=$idPresupuesto");
    exit();
}

// ➡️ UPDATE Detalle
if (isset($_POST["editarDetalle"])) {
    $idDetalle = $_POST["idDetalle"];
    $idPresupuesto = $_POST["idPresupuesto"];
    $cantidad = $_POST["cantidad"];
    $precio = $_POST["precio"];

    $sql = "UPDATE DetallePresupuesto SET Cantidad=?, PrecioUnitario=? WHERE IdDetalle=?";
    sqlsrv_query($conn, $sql, [$cantidad, $precio, $idDetalle]);

    // Recalcular total
    $sql = "UPDATE Presupuestos
            SET Total = (SELECT SUM(Subtotal) FROM DetallePresupuesto WHERE IdPresupuesto=?)
            WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$idPresupuesto, $idPresupuesto]);

    header("Location: Presupuesto.php?id=$idPresupuesto");
    exit();
}

// ➡️ DELETE Detalle
if (isset($_POST["eliminarDetalle"])) {
    $idDetalle = $_POST["idDetalle"];
    $idPresupuesto = $_POST["idPresupuesto"];

    $sql = "DELETE FROM DetallePresupuesto WHERE IdDetalle=?";
    sqlsrv_query($conn, $sql, [$idDetalle]);

    // Recalcular total
    $sql = "UPDATE Presupuestos
            SET Total = (SELECT SUM(Subtotal) FROM DetallePresupuesto WHERE IdPresupuesto=?)
            WHERE IdPresupuesto=?";
    sqlsrv_query($conn, $sql, [$idPresupuesto, $idPresupuesto]);

    header("Location: Presupuesto.php?id=$idPresupuesto");
    exit();
}

/* =========================
   🔹 CONSULTAS
   ========================= */

// Presupuesto seleccionado
$idPresupuesto = isset($_GET["id"]) ? $_GET["id"] : null;
$presupuesto = null;
$detalles = null;
if ($idPresupuesto) {
    $sql = "SELECT P.IdPresupuesto, V.Placa, P.FechaPresupuesto, P.Total, P.Estado
            FROM Presupuestos P
            INNER JOIN Vehiculos V ON P.IdVehiculo = V.IdVehiculo
            WHERE P.IdPresupuesto=?";
    $res = sqlsrv_query($conn, $sql, [$idPresupuesto]);
    $presupuesto = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);

    $sql = "SELECT * FROM DetallePresupuesto WHERE IdPresupuesto=?";
    $detalles = sqlsrv_query($conn, $sql, [$idPresupuesto]);
}

// Lista de presupuestos
$lista = sqlsrv_query($conn, "
    SELECT P.IdPresupuesto, V.Placa, P.FechaPresupuesto, P.Total, P.Estado
    FROM Presupuestos P
    INNER JOIN Vehiculos V ON P.IdVehiculo = V.IdVehiculo
    ORDER BY P.FechaPresupuesto DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presupuestos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align:center; }
        th { background: #6c757d; color: white; }
        .btn { padding: 6px 12px; background: #007bff; color: white; border: none; cursor: pointer; margin: 3px; }
        .btn:hover { background: #0056b3; }
        .btn-red { background: red; }
        input, select { margin: 5px; padding: 6px; width: 95%; }
    </style>
</head>
<body>
    <h1>💰 Presupuestos</h1>

    <!-- Crear nuevo presupuesto -->
    <form method="POST">
        <label>Vehículo:</label><br>
        <select name="idVehiculo" required>
            <?php
            $vehiculos = sqlsrv_query($conn, "SELECT IdVehiculo, Placa FROM Vehiculos");
            while ($v = sqlsrv_fetch_array($vehiculos, SQLSRV_FETCH_ASSOC)) {
                echo "<option value='{$v['IdVehiculo']}'>{$v['Placa']}</option>";
            }
            ?>
        </select><br>
        <button type="submit" name="crear" class="btn">➕ Crear Presupuesto</button>
    </form>

    <!-- Lista de presupuestos -->
    <h2>📋 Presupuestos Existentes</h2>
    <table>
        <tr><th>ID</th><th>Vehículo</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Acciones</th></tr>
        <?php while ($row = sqlsrv_fetch_array($lista, SQLSRV_FETCH_ASSOC)) { ?>
            <tr>
                <td><?php echo $row["IdPresupuesto"]; ?></td>
                <td><?php echo $row["Placa"]; ?></td>
                <td><?php echo $row["FechaPresupuesto"]->format("Y-m-d"); ?></td>
                <td><?php echo number_format($row["Total"], 2); ?></td>
                <td><?php echo $row["Estado"]; ?></td>
                <td>
                    <a href="Presupuesto.php?id=<?php echo $row['IdPresupuesto']; ?>" class="btn">🔍 Ver</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar presupuesto?');">
                        <input type="hidden" name="idPresupuesto" value="<?php echo $row["IdPresupuesto"]; ?>">
                        <button type="submit" name="eliminarPresupuesto" class="btn btn-red">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>

    <?php if ($presupuesto) { ?>
        <h2>📝 Presupuesto #<?php echo $presupuesto["IdPresupuesto"]; ?> - Vehículo <?php echo $presupuesto["Placa"]; ?></h2>
        <p>Fecha: <?php echo $presupuesto["FechaPresupuesto"]->format("Y-m-d"); ?> | 
           Estado: <?php echo $presupuesto["Estado"]; ?> | 
           Total: <b><?php echo number_format($presupuesto["Total"], 2); ?></b></p>

        <!-- Editar estado -->
        <form method="POST">
            <input type="hidden" name="idPresupuesto" value="<?php echo $presupuesto["IdPresupuesto"]; ?>">
            <label>Estado:</label>
            <select name="estado">
                <option value="Pendiente" <?php if ($presupuesto["Estado"]=="Pendiente") echo "selected"; ?>>Pendiente</option>
                <option value="Aprobado" <?php if ($presupuesto["Estado"]=="Aprobado") echo "selected"; ?>>Aprobado</option>
                <option value="Cancelado" <?php if ($presupuesto["Estado"]=="Cancelado") echo "selected"; ?>>Cancelado</option>
            </select>
            <button type="submit" name="editarPresupuesto" class="btn">✏️ Actualizar</button>
        </form>

        <!-- Agregar detalle -->
        <form method="POST">
            <input type="hidden" name="idPresupuesto" value="<?php echo $presupuesto["IdPresupuesto"]; ?>">
            <h3>➕ Agregar Detalle</h3>
            <select name="tipo" required>
                <option value="Servicio">Servicio</option>
                <option value="Repuesto">Repuesto</option>
            </select>
            <input type="text" name="descripcion" placeholder="Descripción" required>
            <input type="number" name="cantidad" value="1" min="1" required>
            <input type="number" step="0.01" name="precio" placeholder="Precio unitario" required>
            <button type="submit" name="agregarDetalle" class="btn">Agregar</button>
        </form>

        <!-- Detalle del presupuesto -->
        <h3>📌 Detalle</h3>
        <table>
            <tr><th>ID</th><th>Tipo</th><th>Descripción</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th><th>Acciones</th></tr>
            <?php while ($d = sqlsrv_fetch_array($detalles, SQLSRV_FETCH_ASSOC)) { ?>
                <tr>
                    <td><?php echo $d["IdDetalle"]; ?></td>
                    <td><?php echo $d["Tipo"]; ?></td>
                    <td><?php echo $d["Descripcion"]; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="idDetalle" value="<?php echo $d["IdDetalle"]; ?>">
                            <input type="hidden" name="idPresupuesto" value="<?php echo $presupuesto["IdPresupuesto"]; ?>">
                            <input type="number" name="cantidad" value="<?php echo $d["Cantidad"]; ?>" min="1">
                            <input type="number" step="0.01" name="precio" value="<?php echo $d["PrecioUnitario"]; ?>">
                            <button type="submit" name="editarDetalle" class="btn">✏️</button>
                        </form>
                    </td>
                    <td><?php echo number_format($d["PrecioUnitario"], 2); ?></td>
                    <td><?php echo number_format($d["Subtotal"], 2); ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este detalle?');">
                            <input type="hidden" name="idDetalle" value="<?php echo $d["IdDetalle"]; ?>">
                            <input type="hidden" name="idPresupuesto" value="<?php echo $presupuesto["IdPresupuesto"]; ?>">
                            <button type="submit" name="eliminarDetalle" class="btn btn-red">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>

    <div style="text-align:center; margin-top:20px;">
        <a href="Vendedor.php">⬅ Volver al Panel Vendedor</a>
    </div>
</body>
</html>
